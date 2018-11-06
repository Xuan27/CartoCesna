<?php
$lat = $_POST["lat"];
$lng = $_POST["lng"];
$epochStart = $_POST["timeStart"];
$epochEnd = $_POST["timeEnd"];
$date = $_POST["date"];
$unit = $_POST["unit"];
if(isset($_POST["summary"]))
    $summary = $_POST["summary"];
else
    $summary = 'off';
$epochStart = floor($epochStart/1000);
$epochEnd = floor($epochEnd/1000);
$utcStart = date('c', $epochStart);
$utcEnd = date('c', $epochEnd);

//AccuWeather
$accuWeatherKey = "vC5DI9vjAVMHp3CPP7V8TGWFUxDeKQVr";
$accuURL = "http://dataservice.accuweather.com";
$accuLocationURL = $accuURL."/locations/v1/cities/geoposition/search?apikey=".$accuWeatherKey."&q=".$lat."%2C".$lng."&details=true&toplevel=true";

//Dark Sky
$darkURL = "https://api.darksky.net";
$darkKey = "0636d15bdd497c62a7b1bad8bb6d790b";
switch ($unit) {
    case "Imperial":
        $darkWeatherURL = $darkURL . "/forecast/" . $darkKey . "/" . $lat . "," . $lng . "," . $epochStart . "?exclude=currently,flag&units=us";
        break;
    case "Metric":
        $darkWeatherURL = $darkURL . "/forecast/" . $darkKey . "/" . $lat . "," . $lng . "," . $epochStart . "?exclude=currently,flag&units=si";
        break;
}

//Aeris
$aerisURL = "https://api.aerisapi.com/observations/archive/";
$aerisKey = "n7rdglaABbQqkidwKoLsgQsuhUbVoaxXi3LTAcki";
$aerisID = "QxcLERgBaMeTjYOghPIzr";
$earisWeatherURL = $aerisURL.$lat.",".$lng."?&from=".$date."&format=json&filter=allstations&limit=1&client_id=".$aerisID."&client_secret=".$aerisKey;
$weatherInfo = array();
function WeatherRequest($url,$apiKey, $source, $recurse){
    $curl = curl_init();
    $options = array(CURLOPT_HTTPHEADER=>array("Authorization: ".$apiKey), CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER=> 0, CURLOPT_RETURNTRANSFER=>1, CURLOPT_URL => $url);
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    if($response === false)
        print_r('Curl error: ' . curl_error($curl));
    $weather = json_decode($response, true);
    $weatherInfo = array();

    switch ($source){
        case "darkSky":
            $darkBool = false;
            foreach($weather["hourly"]["data"] as $data){
                if($data["time"] >= $GLOBALS['epochStart'] && $data["time"] <= $GLOBALS['epochEnd']) {
                    $temp1 = naturalLanguage($data, 'temp');
                    $wind1 = naturalLanguage($data, 'wind');
                    $prec1 = naturalLanguage($data, 'precip');
                    $weatherInfo["Content"] = 'true';
                    $weatherInfo["Source"] = 'Dark Weather';
                    if($GLOBALS['summary'] == 'on')
                        $weatherInfo["Summary"] = $data["summary"];
                    $weatherInfo["Temperature"]=$data[(string)$temp1[0]];
                    $weatherInfo["Wind"]=$data[(string)$wind1[0]];
                    $weatherInfo["Precipitation"]=$data[(string)$prec1[0]];
                    $darkBool = true;
                }
            }
            if (!$darkBool)
                $weatherInfo[0]["Content"] = false;
            break;
        case "aeris":
            $aerisBool = false;
            foreach($weather['response']['periods'] as $data){
                if($data['ob']['timestamp'] >= $GLOBALS['epochStart'] && $data['ob']['timestamp'] <= ($GLOBALS['epochStart']+320)){
                    switch ($GLOBALS['unit']) {
                        case "Metric":
                            $units = array('C', 'KPH', 'MM');
                            break;
                        case "Imperial":
                            $units = array('F', 'MPH', 'IN');
                            break;
                    }
                    $temp2 = naturalLanguage($data['ob'], 'temp'.$units[0]);
                    $wind2 = naturalLanguage($data['ob'], 'wind'.$units[1]);
                    $prec2 = naturalLanguage($data['ob'], 'precip'.$units[2]);
                    $weatherInfo["Content"] = 'true';
                    $weatherInfo["Source"] = 'Aeris Weather';
                    if($GLOBALS['summary'] == 'on')
                        $weatherInfo["Summary"] = $data['ob']['weather'];
                    $weatherInfo["Temperature"]=$data['ob'][(string)$temp2[0]];
                    $weatherInfo["Wind"]=$data['ob'][(string)$wind2[0]];
                    $weatherInfo["Precipitation"]=$data['ob'][(string)$prec2[0]];
                    $aerisBool = true;
                }

            }
            if (!$aerisBool)
                $weatherInfo["Content"] = 'false';
            break;
        case ("accuWeather"):

            if($recurse){
                if (array_key_exists('Key', $weather)) {
                    $accuBool = false;
                    $locationKey = $weather["Key"];
                    $accuWeatherURL = $GLOBALS['accuURL']."/currentconditions/v1/".$locationKey."/historical/24?apikey=".$GLOBALS['accuWeatherKey']."&details=true";
                    $weatherInfo = WeatherRequest($accuWeatherURL, null, "accuWeather", 0);
                }
                else
                    $weatherInfo["Content"] = 'false';

            }
            else {
                    $temp3 = naturalLanguage($weather[0], 'temp');
                    $wind3 = naturalLanguage($weather[0], 'wind');
                    //print_r($weather[0]);
                    $pastHours = time_elapsed_string('@'.$GLOBALS['epochStart']);
                    $weatherInfo["Content"] = 'true';
                    $weatherInfo["Source"] = 'AccuWeather';
                    $weatherInfo["Summary"] = '';
                    $weatherInfo["Temperature"] = $weather[0][(string)$temp3[0]][$GLOBALS['unit']]['Value'];
                    $weatherInfo["Wind"] = $weather[0][(string)$wind3[0]]['Speed'][$GLOBALS['unit']]['Value'];
                    $weatherInfo["Precipitation"] = $weather[0]["PrecipitationSummary"][$pastHours][$GLOBALS['unit']]['Value'];
               return $weatherInfo;
            }

            break;
    }
    curl_close($curl);
    return $weatherInfo;
}

function naturalLanguage($data,$expression){
    $keys = array_keys($data);
    $matches = array();
    $expression = "/".$expression."\\w*/i";
    foreach ($keys as $key){
        if(preg_match($expression, $key)){
            array_push($matches, $key);
        }
    }
    return $matches;
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $string = array(
        'h' => '',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = 'Past'. ($diff->$k > 1 ? $diff->$k. $v. 'Hours' : 'Hour');
        } else {
            $v = 'Past24Hours';
        }
    }
    return $string['h'];
}

$darkWeather = WeatherRequest($darkWeatherURL, null, "darkSky", 0);
$aerisWeather = WeatherRequest($earisWeatherURL, null, "aeris", 0);
$accuWeather = WeatherRequest($accuLocationURL, null, "accuWeather", 1);

$weatherInfo[0] = $darkWeather;
$weatherInfo[1] = $aerisWeather;
$weatherInfo[2] = $accuWeather;

//print_r($weatherInfo);
$temPost = 0;
$tempCount = 0;
$windCount = 0;
$windPost = 0;
$precPost = 0;
$precCount = 0;
$summPost = '';
foreach ($weatherInfo as $key=>$v) {
    if($v['Content'] == 'true' && $v['Temperature'] != ''){
        $temPost += $v['Temperature'];
        $tempCount++;
    }
    if ($v['Content'] == 'true' && $v['Wind'] != '') {
        $windPost += $v['Wind'];
        $windCount++;
    }
    if ($v['Content'] == 'true' && $v['Precipitation'] != '') {
        $precPost += $v['Precipitation'];
        $precCount++;
    }
    if ($v['Content'] == 'true' && $summary == 'on') {
        if($v['Summary'] != $summPost){
            $summPost .= $v['Summary']." ";
            $summaryPost = $summPost;
        }

    }
}


if($tempCount!=0)
    $info['Temperature'] = $temPost/$tempCount;
else
    $info['Temperature'] = 0;

if($windCount != 0)
    $info['Wind'] = $windPost/$windCount;
else
    $info['Wind'] = 0;

if($precCount!=0)
    $info['Precipitation'] = $precPost/$precCount;
else
    $info['Precipitation'] = 0;
if($summary!='off')
    $info['Summary'] = $summaryPost;
echo json_encode($info);
?>
<?php
$lat = $_POST["lat"];
$lng = $_POST["lng"];
$epochStart = $_POST["timeStart"];
$epochEnd = $_POST["timeEnd"];
$date = $_POST["date"];
$unit = $_POST["unit"];
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

//Hour Glass
$aerisURL = "https://api.aerisapi.com/observations/archive/";
$aerisKey = "n7rdglaABbQqkidwKoLsgQsuhUbVoaxXi3LTAcki";
$aerisID = "QxcLERgBaMeTjYOghPIzr";
$earisWeatherURL = $aerisURL.$lat.",".$lng."?&from=".$date."&format=json&filter=allstations&limit=1&client_id=".$aerisID."&client_secret=".$aerisKey;

function WeatherRequest($url,$apiKey, $source, $recurse){

    $curl = curl_init();
    $options = array(CURLOPT_HTTPHEADER=>array("Authorization: ".$apiKey), CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER=> 0, CURLOPT_RETURNTRANSFER=>1, CURLOPT_URL => $url);
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    if($response === false)
        print_r('Curl error: ' . curl_error($curl));
    $weather = json_decode($response, true);

    switch ($source){
        case "darkSky":
            foreach($weather["hourly"]["data"] as $data){
                if($data["time"] >= $GLOBALS['epochStart'] && $data["time"] <= $GLOBALS['epochEnd']) {
                    $temp1 = naturalLanguage($data, 'temp');
                    $wind1 = naturalLanguage($data, 'wind');
                    $prec1 = naturalLanguage($data, 'precip');
                    print_r("Dark weather Temperature: ".$data[(string)$temp1[0]]." ");
                    print_r("Dark weather Wind: ".$data[(string)$wind1[0]]." ");
                    print_r("Dark weather Precipitation: ".$data[(string)$prec1[0]]." ");
                }
            }
            break;
        case "aeris":
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

                    print_r("Aeris temperature: ".$data['ob'][(string)$temp2[0]]." ");
                    print_r("Aeris wind: ".$data['ob'][(string)$wind2[0]]." ");
                    print_r("Aeris precipitation: ".$data['ob'][(string)$prec2[0]]." ");
                }

            }
            break;
        case ("accuWeather"):
            if($recurse){
                $locationKey = $weather["Key"];
                $accuWeatherURL = $GLOBALS['accuURL']."/currentconditions/v1/".$locationKey."/historical/24?apikey=".$GLOBALS['accuWeatherKey']."&details=true";
                WeatherRequest($accuWeatherURL, null, "accuWeather", 0);
            }
            else {
                //print_r($weather);
                $temp3 = naturalLanguage($weather[0], 'temp');
                $wind3 = naturalLanguage($weather[0], 'wind');
                //$prec3 = naturalLanguage($weather[0], 'precip');

                $pastHours = time_elapsed_string('@'.$GLOBALS['epochStart']);

                print_r("Accu Weather Temperature: ".$weather[0][(string)$temp3[0]][$GLOBALS['unit']]['Value']." ");
                print_r("Accu Weather Wind: ".$weather[0][(string)$wind3[0]]['Speed'][$GLOBALS['unit']]['Value']." ");
                print_r("Accu Weather Precipitation: ".$weather[0]["PrecipitationSummary"][$pastHours][$GLOBALS['unit']]['Value']." ");
            }
                break;
    }
    curl_close($curl);
    return $weather;
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

?>
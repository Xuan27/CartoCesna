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
print_r(" ");
//Dark Sky
$darkURL = "https://api.darksky.net";
$darkKey = "0636d15bdd497c62a7b1bad8bb6d790b";
switch ($unit) {
    case "Imperial":
        $darkWeatherURL = $darkURL . "/forecast/" . $darkKey . "/" . $lat . "," . $lng . "," . $epochStart . "?exclude=currently,flag&units=us";
        print_r($darkWeatherURL);
        break;
    case "Metric":
        $darkWeatherURL = $darkURL . "/forecast/" . $darkKey . "/" . $lat . "," . $lng . "," . $epochStart . "?exclude=currently,flag&units=si";
        print_r($darkWeatherURL);
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
                    print_r($data);
                }
            }
            break;
        case "aeris":
            foreach($weather['response']['periods'] as $data){
                if($data['ob']['timestamp'] >= $GLOBALS['epochStart'] && $data['ob']['timestamp'] <= ($GLOBALS['epochStart']+320))
                    print_r($data);
            }
            break;
        case ("accuWeather"):
            if($recurse){
                $locationKey = $weather["Key"];
                $accuWeatherURL = $GLOBALS['accuURL']."/currentconditions/v1/".$locationKey."/historical/24?apikey=".$GLOBALS['accuWeatherKey']."&details=true";
                WeatherRequest($accuWeatherURL, null, "accuWeather", 0);
            }
            else
                print_r($weather[0]);
                break;
    }
    curl_close($curl);
    return $weather;
}

function naturalLanguage($expression){

}

$darkWeather = WeatherRequest($darkWeatherURL, null, "darkSky", 0);
$aerisWeather = WeatherRequest($earisWeatherURL, null, "aeris", 0);
$accuWeather = WeatherRequest($accuLocationURL, null, "accuWeather", 1);


?>
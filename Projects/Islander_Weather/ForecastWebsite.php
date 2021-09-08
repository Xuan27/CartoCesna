<?php

class Forecast{
    private $name;
    private $index;
    private $key;
    private $url;
    /*private $lat;
    private $long;
    private $date;
    private $unit;*/

    //Constuct Weahter forecast website url
    public function __construct($name, $index, $key){
        $this->name = $name;
        $this->index = $index;
        $this->key = $key;
        /*$this->lat = $_POST['lat'];
        $this->long = $_POST['lng'];
        $this->date = $_POST['date'];
        $this->unit= $unit;*/
        self::generateURL($index, $key);
    }

    private function generateURL($index, $key){
        if($this->name == "AccuWeather")
            $this->url = $index."/locations/v1/cities/geoposition/search?apikey=".$key."&q=".$_POST['lat']."%2C".$_POST['lng']."&details=true&toplevel=true";
        elseif($this->name == "DarkSky"){
            switch ($_POST['unit']) {
                case "Imperial":
                    $this->url = $index . "/forecast/" . $key . "/" . $_POST['lat'] . "," . $_POST['lng'] . "," . $_POST['timeStart'] . "?exclude=currently,flag&units=us";
                    break;
                case "Metric":
                    $this->url = $index . "/forecast/" . $key . "/" . $_POST['lat'] . "," . $_POST['lng'] . "," . $_POST['timeStart'] . "?exclude=currently,flag&units=si";
                    break;
            }
        }
        else{
            $id = "QxcLERgBaMeTjYOghPIzr";
            $this->url = $index.$_POST['lat'].",".$_POST['lng']."?&from=".$_POST['date']."&format=json&filter=allstations&limit=1&client_id=".$id."&client_secret=".$key;
        }
    }

    public function getURL(){
        return $this->url;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Juan
 * Date: 10/13/2018
 * Time: 3:39 PM
 */?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Title</title>
        <link rel="stylesheet" href="../../Master/css/indexBody.css">
        <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />

        <!--<script src='https://api.tiles.mapbox.com/mapbox-gl-js/v0.50.0/mapbox-gl.js'></script>-->

        <!--leaflet 1.3.4-->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>

        <!--Leaflet control Geocoder-->
        <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
        <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
        <!--<script src="../../Master/libraries/leaflet_1.3.4/leaflet.js"></script>
        <link rel="stylesheet" href="../../Master/libraries/leaflet_1.3.4/leaflet.css">-->

        <!--JQuery-->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

    </head>
    <body>
        <!--MAP CONTAINER-->
        <div id="map" style="width:50%; height: 80%;"></div>
        <form id="form" action="" style="font-size: 20px;">
            <div>
                <p>Summary <input id="summary" type="checkbox" name="summary" checked></p>
            </div>
            <div style="position: absolute;left:20%;bottom: 11%;">
                <p>Unit <select name="unit">
                        <option>Metric</option>
                        <option>Imperial</option>
                    </select>
                </p>
            </div>
            <div style="position: absolute;left:40%;bottom: 11%;">
                <p>Time <select name="time" id="time">
                        <option value="1">1 hour ago</option>
                        <option value="3">3 hours ago</option>
                        <option value="6">6 hours ago</option>
                        <option value="9">9 hours ago</option>
                        <option value="12">12 hours ago</option>
                        <option value="18">18 hours ago</option>
                        <option value="24">24 hours ago</option>
                    </select>
                </p>
            </div>


            <input type="text" id="lat" name="lat" hidden required>
            <input type="text" id="lng" name="lng" hidden required>
            <input type="text" id="timeStart" name="timeStart" hidden>
            <input type="text" id="timeEnd" name="timeEnd" hidden>
            <input type="text" id="date" name="date" hidden>

        </form>
        <button id="search"  onclick="submit()" style="position: absolute;left:60%;bottom: 14.2%;">Search</button>
    <div id="weatherInfo" style="position: relative; margin-left: 65%; bottom: 70%;font-size: 20px;">
        <p id="summaryInfo"></p>
        <p id="tempInfo"></p>
        <p id="windInfo"></p>
        <p id="precInfo"></p>
    </div>

    </body>
    <script type="text/javascript">
        var dt = new Date();
        $(document).ready(function(event) {
            var timeStart = dt.getTime() - 3600000;
            var timeEnd = dt.getTime();
            var dd = new Date(timeStart);
            var date = (dd.getMonth() + 1 )+ "/" + dd.getDate() + "/" + dd.getFullYear();
            $("#timeStart").val(timeStart);
            $("#timeEnd").val(timeEnd);
            $("#date").val(date);
        });

        var map = L.map('map').setView([27.7286568,-97.3790408], 4);
        "https://api.mapbox.com/styles/v1/xuan27/cjnckbdnz6y1q2soehdb2rxut.html?fresh=true&title=true&access_token=pk.eyJ1IjoieHVhbjI3IiwiYSI6IktzT0hVNjAifQ.v97O2GRYRJ8ZxhLHtTn30g#12.0/48.866500/2.317600/0"
        L.tileLayer('https://api.mapbox.com/styles/v1/xuan27/cjnckbdnz6y1q2soehdb2rxut/tiles/256/{z}/{x}/{y}?access_token=pk.eyJ1IjoieHVhbjI3IiwiYSI6IktzT0hVNjAifQ.v97O2GRYRJ8ZxhLHtTn30g', {
            attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
            maxZoom: 10,
            id: 'mapbox.streets',
            accessToken: 'pk.eyJ1IjoieHVhbjI3IiwiYSI6IktzT0hVNjAifQ.v97O2GRYRJ8ZxhLHtTn30g'
        }).addTo(map);

        L.Control.geocoder({
            expand: "click",
            showResultIcons: true
        }).on('markgeocode', function(e){
            var locationLat = e.geocode.properties.lat;
            var locationLng = e.geocode.properties.lon;
            $("#lat").val(locationLat);
            $("#lng").val(locationLng);
        }).addTo(map);

        function submit(event){
            var data = $("form").serializeArray();
            var lat = $("#lat").val();
            var lng = $("#lng").val();
            if(lat == '' || lng == ''){
                alert("Click and type a city name in the map control search");
                return null;
            }
            $.ajax({
                type: "POST",
                url: "Sources_Request.php",
                data: data,
                success:function(data) {
                    data = JSON.parse(data);
                    console.log(data);
                    if(document.getElementById('summary').checked)
                        $("#summaryInfo").html("Weather Summary: "+data.Summary.value);
                    else
                        $("#summaryInfo").html("");
                    $("#tempInfo").html("Temperature: "+data.Temperature.value.toFixed(2)+ " " + data.Temperature.unit);
                    $("#windInfo").html("Wind: "+data.Wind.value.toFixed(2)+ " " + data.Wind.unit);
                    $("#precInfo").html("Precipitation: "+data.Precipitation.value.toFixed(2)+ " " + data.Precipitation.unit);
                },
                error:function(requestObject) {
                    alert(requestObject.status);
                }
            });
        }

        var p = document.getElementsByTagName("p");
        $("#time").change(function () {
            var hour = $("#time option:selected").val();
            var milliStart =  parseInt(hour)* 3600000;
            var epochStart = dt.getTime() - milliStart;
            var epochEnd = epochStart + 3600000;
            $("#timeStart").val(epochStart);
            $("#timeEnd").val(epochEnd);
        })

    </script>
</html>

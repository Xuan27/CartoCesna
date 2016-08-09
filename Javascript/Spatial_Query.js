//Global Variables
var queryRectangle = 0;															//stores the current user drawn rectangle, used to query that area
var rectangleArray = new Array(); 											//contains all the rectangles created from a succesful query
var markerArray = new Array();													//contains all the markers created from a succesful query
var spatialQuerySelection = "intersects";										//string of used to decide what spatial query technique will be used 
var dateQuerySelection = "allYears";											//string used to decide what date query technique will be used 
var dateQuerySQL = "";															//string of SQL statement to refine search by date
var highlight = {fillOpacity: .1, color: "#FF0000", weight: 1};			//higlight properties of markers/rectangles.
var defaultColor = {fillOpacity:0, color: "#28b463", weight: 3};		//default properties of markers/rectangles.
var Clust = "";																			//Global Variable that will hold the Marker Cluster layer

//This function is called when the user hits the "Submit Query" button. When this function is called
//it gathers all the information it needs to send to submitQuery.php and creates a JSON called "queryObject".
//queryObject contains all the information the php script will need to execute the desired query on our database.
//On a succesful AJAX call, drawResults() is called with the information returned from submitQuery.php as a parameter.
function submitQuery(queryRectangle)
{	
	getDateRange();
	var authorSQL = generateAuthorInputSQLStatement();

	if(queryRectangle == null)
	{
		deleteTable("resultsTable");
		for(var i = rectangleArray.length; i > 0; i--)
		{
			map.removeLayer(rectangleArray[i-1]);
			map.removeLayer(Clust);
		}
		rectangleArray = [];
		markerArray = [];
		return;
	}

	for(var i = 0; i < rectangleArray.length; i++)
	{
		map.removeLayer(rectangleArray[i]);
		map.removeLayer(Clust);
	}
	rectangleArray = [];
	markerArray = [];
	
	queryObject = addQueryObject(queryRectangle.getBounds()._southWest.lat,
														queryRectangle.getBounds()._southWest.lng, 
														queryRectangle.getBounds()._northEast.lat, 
														queryRectangle.getBounds()._northEast.lng,
														spatialQuerySelection, dateQuerySQL, authorSQL);

	deleteTable("resultsTable");
	for(var i = 0; i < rectangleArray.length; i++)
	{
		map.removeLayer(rectangleArray[i]);
		map.removeLayer(Clust);
		
	}
	
	$.ajax({
		type: 'post',
		url: '../PHP/submitQuery.php',
		data:{'queryObject':JSON.stringify(queryObject)},
		success: function(data){
			if(data == "0 results[]")
			{
				alert("No Matches Found");
				document.getElementById("subHeader").innerHTML = "documents found: 0";
				return;				
			}
			Clust = drawResults(JSON.parse(data));
			displayLinks(JSON.parse(data));
		}
	});
}

//This function is called by submitQuery() and takes the results from the AJAX call in submitQuery as a parameter.
//Using this information from the paramter "results", the function draws all the markers, rectangles, and creates click events for them. 
function drawResults(results)
{	
	var markerCluster = new L.markerClusterGroup({
			spiderLegPolylineOptions: {weight: 3, color:"#", opacity: 0.25}
		});
	Maki_Icon = icon = L.MakiMarkers.icon({color: "#FF0000" , size: "m"});
	Maki_Icon = defaultIcon = L.MakiMarkers.icon({color: "#58d68d" , size: "m"});
	//var rectangleArray = new Array();
	//var markerArray = new Array();
	
	//iterates through every instance of the results array
	for(var i = 0; i < results.length; i++)
	{	
		//creates rectangle object 
		var rectangle = L.rectangle([[results[i].y1, results[i].x1],[results[i].y3, results[i].x3]],
												{fillOpacity: 0, color: "#28b463 ", weight: 3});
		
		//adds rectangle to map 
		rectangleArray.push(rectangle);
		map.addLayer(rectangleArray[i]);
		rectangle.bringToBack();
		
		//calculates center of rectangle for marker placement 
		center = (results[0].x1 + results[0].x2);
		var centerY = (parseFloat(results[i].y1) + parseFloat(results[i].y3))*.5;
		var centerX = (parseFloat(results[i].x1) + parseFloat(results[i].x3))*.5;
		
		//creates marker object 
		var marker = L.marker([centerY, centerX]);
		marker.bindPopup("" + i + "").openPopup();
		marker.setIcon(defaultIcon);
		
		//adds marker to the map 
		markerArray.push(marker);	
		//map.addLayer(markerArray[i]);
		table = document.getElementById("resultsTable");
		
		//creates onclick event for the marker
		markerArray[i].on('click', function(e)
		{
			this.closePopup();	
		
			var popup = this.getPopup();

			for(var i = 0; i < markerArray.length; i++)
			{
				rectangleArray[i].setStyle(defaultColor);
				markerArray[i].setIcon(defaultIcon);
				table.rows[i+1].style.backgroundColor = "#e5f1fd";
			}
			
			rectangleArray[popup.getContent()].setStyle(highlight)
			this.setIcon(icon);
			
			highlightTable(popup.getContent());
			map.fitBounds(rectangleArray[popup.getContent()].getLatLngs(), {padding: [50, 50]}, {animate: true});

		});

		markerCluster.addLayer(markerArray[i]);
		console.log(markerCluster);
		
	}
map.addLayer(markerCluster);
	return markerCluster;
}

//This function takes the results from submitQuery.php as a paramter, and uses the information to create our results table.
function displayLinks(results)
{
	table = document.getElementById("resultsTable");
	
	//iterates through every instance of the results array 
	for(var i = 0; i < results.length; i++)
	{
		var fileName = results[i].fileName;
		var row = table.insertRow(-1)
		var cell0 = row.insertCell(0);
		var cell1 = row.insertCell(1);
		var cell2 = row.insertCell(2);
		var cell3 = row.insertCell(3);
		cell0.innerHTML = results[i].Author;
		cell1.innerHTML = fileName.substring(0, fileName.length-4);
		cell2.innerHTML = "<a href = '" + results[i].kmz + "'>" + "kmz" + "</a>" + ", <a href = '" + results[i].GeoTIFF + "'>" + "GeoTIFF" + "</a>";	
		cell3.innerHTML = "<button id = 'showOnMapButton' onclick = 'highlightMapMarker(" + i + ")'>Show On Map</button>";
	}
	document.getElementById("subHeader").innerHTML = "documents found: " + results.length;
}

//This function is used by the "Show On Map" button created by displayLinks(). 
//The purpose of this function is to highlight the marker that was clicked on and zooms the viewer
//to the extent of its corresponding rectangle while also highlighting both of them in red.
function highlightMapMarker(index)
{
	for(var i = 0; i < markerArray.length; i++)
	{
		rectangleArray[i].setStyle(defaultColor);
		markerArray[i].setIcon(defaultIcon);
		table.rows[i+1].style.backgroundColor = "#e5f1fd";
	}
	rectangleArray[index].setStyle(highlight);
	markerArray[index].setIcon(icon);
	
	highlightTable(index);
	map.fitBounds(rectangleArray[index].getLatLngs(), {padding: [50, 50]}, {animate: true});
}

//This function is called within a results marker's click event created in drawResults().
//The purpose of this function is to highlight the entry in the results table that corresponds to the
//results marker that was clicked on.
function highlightTable(index)
{ 
	index++;
	table = document.getElementById("resultsTable");
	table.rows[index].style.backgroundColor = '#FFFFE0';
}

//This function recursivly deletes the results table. This function is called when a query is submitted
//to clear the slate from the old query so that they don't interfere with each other.
function deleteTable(id)
{
	var table = document.getElementById(id);
	
	//recursive base case 
	if(table.rows.length == 1)
		return;
	
	table.deleteRow(table.rows.length-1);
	document.getElementById("subHeader").innerHTML = "";
	
	//recursive call 
	deleteTable(id);
}

//This function is used by the dropdown menus when they are clicked on.
function openDropdown(id) {
    document.getElementById(id).classList.toggle("show");
}

//This function us used to close the dropdown menu if the user clicks outside of it
window.onclick = function(event) {
  if (!event.target.matches('.dropbtn')) {

    var dropdowns = document.getElementsByClassName("dropdown-content");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('show')) {
        openDropdown.classList.remove('show');
      }
    }
  }
}

//This function's purpose is to create our "spatialQuerySelection" variable that will be used by submitQuery().
//It also adjusts the dropdown menu based on the selection made.
function spatialQueryOptions(selection)
{
	spatialQuerySelection = selection;
	if(spatialQuerySelection == "contains")
	{
		document.getElementById("spatialQueryDropdown").innerHTML = "Contains";
		document.getElementById("contains").innerHTML = "Contains &#10004;";
		document.getElementById("intersects").innerHTML = "Intersect";
		document.getElementById("containsCentroid").innerHTML = "Contains Centroid";
	}
	if(spatialQuerySelection == "intersects")
	{
		document.getElementById("spatialQueryDropdown").innerHTML = "Intersects";
		document.getElementById("contains").innerHTML = "Contains ";
		document.getElementById("intersects").innerHTML = "Intersect &#10004;";
		document.getElementById("containsCentroid").innerHTML = "Contains Centroid";
	}
	if(spatialQuerySelection == "containsCentroid")
	{
		document.getElementById("spatialQueryDropdown").innerHTML = "Contains Centroid";
		document.getElementById("contains").innerHTML = "Contains ";
		document.getElementById("intersects").innerHTML = "Intersect";
		document.getElementById("containsCentroid").innerHTML = "Contains Centroid &#10004;";
	}
	submitQuery(queryRectangle);
}

//Same as spatialQueryOptions but for the date dropdown menu.
function getDateRange()
{
	var values = $( "#slider-range" ).slider( "values" );
	min = values[0];
	max = values[1];
	
	dateQuerySQL = "AND LEFT(table1.Date, 4) >= '" + min + "' AND LEFT(table1.Date, 4) <= '" + max + "'";

}

//Currently is used just to implement the ability to refine search results by author but 
//should be expanded to handle all text fields that we want the user to be able to refine there search by.
function generateAuthorInputSQLStatement()
{
	if(document.getElementById("authorInput").value == "")
	{
		return "";		
	}

	authorSQL = "AND Author LIKE '" + document.getElementById("authorInput").value + "'";
	return authorSQL;
}

//This function is for the date slider interface. It submits a query on any change event acted on it. 
$( function() {
	$( "#slider-range" ).slider({
		range: true,
		min: 1800,
		max: 2016,
		values: [ 1800, 2016 ],
		slide: function( event, ui ) {
			$( "#amount" ).val( " " + ui.values[ 0 ] + " - " + ui.values[ 1 ] );
		},
		change: function(event, ui){
			submitQuery(queryRectangle);
		}
	});
	$( "#amount" ).val( " " + $( "#slider-range" ).slider( "values", 0 ) +
	" - " + $( "#slider-range" ).slider( "values", 1 ) );
});

//This function fits the extent of the viewer to show all the results rectangles 
function fitExtentToResults()
{
	var group = new L.featureGroup(rectangleArray);
	map.fitBounds(group.getBounds());
}

//Object Construction Functions
function addQueryObject(x1, y1, x2, y2, spatialQuerySelection, dateQuerySQL, authorSQL)
{
	queryObject =  new queryObjectConstructor(x1, y1, x2, y2, spatialQuerySelection, dateQuerySQL, authorSQL)
	return queryObject;
}

function queryObjectConstructor(x1, y1, x2, y2, spatialQuerySelection, dateQuerySQL, authorSQL)
{
	this.x1 = x1;
	this.y1 = y1;
	this.x2 = x2;
	this.y2 = y2;
	this.spatialQuerySelection = spatialQuerySelection;
	this.dateQuerySQL = dateQuerySQL;
	this.authorSQL = authorSQL;
}


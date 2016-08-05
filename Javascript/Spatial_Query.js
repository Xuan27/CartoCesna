//Global Variables
rectangleArray = new Array();
markerArray = new Array();
var spatialQuerySelection = "intersects";
var dateQuerySelection = "allYears";
var dateQuerySQL = "";
var highlight = {color: "#FF0000", weight: 1};
var defaultColor = {color: "#58d68d", weight: 1};	

function deleteRectangle()
{	
	if(markerCount == 2)
	{
		map.removeLayer(tempRectangleArray[tempRectangleArray.length-1]);
		tempRectangleArray = [];
		rectangleCoords.splice(rectangleCount-1, 2);
		rectangleCount--;
		
		map.removeLayer(tempMarkerArray[markerCount-1]);
		map.removeLayer(tempMarkerArray[markerCount-2]);
		tempMarkerArray.splice(markerCount-2, 2);
		markerCount = markerCount-2;
	}
	else if(markerCount == 1)
	{
		rectangleCoords.splice(0, 1)
		
		map.removeLayer(tempMarkerArray[markerCount-1]);
		tempMarkerArray.splice(-1,1 );
		markerCount--;
	}
	else
		return null;
}

function submitQuery(boundingBox)
{	
	var authorSQL = generateAuthorInputSQLStatement();

	if(tempRectangle == null)
	{
		deleteTable("resultsTable");
		for(var i = rectangleArray.length; i > 0; i--)
		{
			map.removeLayer(rectangleArray[i-1]);
			map.removeLayer(markerArray[i-1]);
		}
		rectangleArray = [];
		markerArray = [];
		return;
	}

	for(var i = 0; i < rectangleArray.length; i++)
	{
		map.removeLayer(rectangleArray[i]);
		map.removeLayer(markerArray[i]);
	}
	rectangleArray = [];
	markerArray = [];
	
	diagonalCoords = addDiagonalObject(boundingBox.getBounds()._southWest.lat,
														boundingBox.getBounds()._southWest.lng, 
														boundingBox.getBounds()._northEast.lat, 
														boundingBox.getBounds()._northEast.lng,
														spatialQuerySelection, dateQuerySQL, authorSQL);
	deleteRectangle();
	deleteTable("resultsTable");
	for(var i = 0; i < rectangleArray.length; i++)
	{
		map.removeLayer(rectangleArray[i]);
		map.removeLayer(markerArray[i]);
	}
	
	$.ajax({
		type: 'post',
		url: '../PHP/submitQuery.php',
		data:{'diagonalCoords':JSON.stringify(diagonalCoords)},
		success: function(data){
			if(data == "0 results[]")
			{
				alert("No Matches Found");
				document.getElementById("subHeader").innerHTML = "documents found: 0";
				return;				
			}
			drawResults(JSON.parse(data));
			displayLinks(JSON.parse(data));
		}
	});
}

function drawResults(results)
{
	Maki_Icon = icon = L.MakiMarkers.icon({color: "#FF0000" , size: "m"});
	Maki_Icon = defaultIcon = L.MakiMarkers.icon({color: "#58d68d" , size: "m"});
	//var rectangleArray = new Array();
	//var markerArray = new Array();
	
	for(var i = 0; i < results.length; i++)
	{	
		var rectangle = L.rectangle([[results[i].y1, results[i].x1],
		[results[i].y3, results[i].x3]],{fillOpacity: .05, color: "#58d68d", weight: 3});
		rectangleArray.push(rectangle);
		map.addLayer(rectangleArray[i]);
		rectangle.bringToBack();
		
		center = (results[0].x1 + results[0].x2);
		
		var centerY = (parseFloat(results[i].y1) + parseFloat(results[i].y3))*.5;
		var centerX = (parseFloat(results[i].x1) + parseFloat(results[i].x3))*.5;
		
		var marker = L.marker([centerY, centerX]);
		marker.bindPopup("" + i + "").openPopup();
		marker.setIcon(defaultIcon);
		
		markerArray.push(marker);	
		map.addLayer(markerArray[i]);
		table = document.getElementById("resultsTable");
		
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

	}
}

function displayLinks(results)
{
	table = document.getElementById("resultsTable");
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
	
	//map.setView(map.unproject(map.project(markerArray[index].getLatLng())),10, {animate: true});
	highlightTable(index);
	map.fitBounds(rectangleArray[index].getLatLngs(), {padding: [50, 50]}, {animate: true});
}

function highlightTable(index)
{ 
	index++;
	table = document.getElementById("resultsTable");
	table.rows[index].style.backgroundColor = '#FFFFE0';
}

function deleteTable(id)
{
	var table = document.getElementById(id);
	
	//recursive base case 
	if(table.rows.length == 1)
		return;
	
	table.deleteRow(table.rows.length-1);
	
	//recursive call 
	deleteTable(id);
}

function openDropdown(id) {
    document.getElementById(id).classList.toggle("show");
}

// Close the dropdown menu if the user clicks outside of it
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
}

function populateYearSelector()
{
	var yearField = document.getElementById('year');
	for(var i = 0; i <= 216; i++)
	{
		yearField.options[i] = new Option(i + 1800);
	}
}

function dateQueryOptions(selection)
{
	dateQuerySelection = selection;
	
	if(selection == "allYears")
	{
		dateQuerySQL = "";
		document.getElementById("dateQueryDropdown").innerHTML = "All Years";
		document.getElementById("allYears").innerHTML = "All Years &#10004;";
		document.getElementById("before").innerHTML = "Before";
		document.getElementById("during").innerHTML = "During";
		document.getElementById("after").innerHTML = "After";
	}
	if(selection  == "before")
	{
		document.getElementById("dateQueryDropdown").innerHTML = "Before";
		dateQuerySQL = "AND LEFT(table1.Date , 4) < " + document.getElementById("year").value;
		document.getElementById("allYears").innerHTML = "All Years" 
		document.getElementById("before").innerHTML = "Before &#10004;";
		document.getElementById("during").innerHTML = "During";
		document.getElementById("after").innerHTML = "After";
	}
	if(selection  == "during")
	{
		document.getElementById("dateQueryDropdown").innerHTML = "During";
		dateQuerySQL = "AND LEFT(table1.Date , 4) = " + document.getElementById("year").value;
		document.getElementById("allYears").innerHTML = "All Years" 
		document.getElementById("before").innerHTML = "Before";
		document.getElementById("during").innerHTML = "During &#10004";
		document.getElementById("after").innerHTML = "After";
	}
	if(selection  == "after")
	{
		document.getElementById("dateQueryDropdown").innerHTML = "After";
		dateQuerySQL = "AND LEFT(table1.Date , 4) > " + document.getElementById("year").value;
		document.getElementById("allYears").innerHTML = "All Years" 
		document.getElementById("before").innerHTML = "Before ";
		document.getElementById("during").innerHTML = "During";
		document.getElementById("after").innerHTML = "After &#10004";
	}
}

function generateAuthorInputSQLStatement()
{
	if(document.getElementById("authorInput").value == "")
	{
		return "";		
	}

	authorSQL = "AND Author LIKE '" + document.getElementById("authorInput").value + "'";
	return authorSQL;
}

//Object Construction Functions
function addDiagonalObject(x1, y1, x2, y2, spatialQuerySelection, dateQuerySQL, authorSQL)
{
	diagonalObject =  new diagonalObjectConstructor(x1, y1, x2, y2, spatialQuerySelection, dateQuerySQL, authorSQL)
	return diagonalObject;
}

function diagonalObjectConstructor(x1, y1, x2, y2, spatialQuerySelection, dateQuerySQL, authorSQL)
{
	this.x1 = x1;
	this.y1 = y1;
	this.x2 = x2;
	this.y2 = y2;
	this.spatialQuerySelection = spatialQuerySelection;
	this.dateQuerySQL = dateQuerySQL;
	this.authorSQL = authorSQL;
}




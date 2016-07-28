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
		
		map.removeLayer(markerArray[markerCount-1]);
		markerArray.splice(-1,1 );
		markerCount--;
	}
	else
		return null;
}

function submitQuery(boundingBox)
{	
	deleteTable("resultsTable");
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
														boundingBox.getBounds()._northEast.lng)
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
		[results[i].y2, results[i].x2]],{fillOpacity: .05, color: "#58d68d", weight: 3});
		rectangleArray.push(rectangle);
		map.addLayer(rectangleArray[i]);
		
		center = (results[0].x1 + results[0].x2);
		
		var centerY = (parseFloat(results[i].y1) + parseFloat(results[i].y2))*.5;
		var centerX = (parseFloat(results[i].x1) + parseFloat(results[i].x2))*.5;
		
		var marker = L.marker([centerY, centerX]);
		marker.bindPopup("" + i + "").openPopup();
		marker.setIcon(defaultIcon);
		
		markerArray.push(marker);	
		map.addLayer(markerArray[i]);
		table = document.getElementById("resultsTable");
		
		markerArray[i].on('click', function(e)
		{
			this.closePopup();
			var highlight = {color: "#FF0000", weight: 1};
			var defaultColor = {color: "#58d68d", weight: 1};		
		
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
		var cell1 = row.insertCell(0);
		var cell2 = row.insertCell(1);
		var cell3 = row.insertCell(2);
		cell1.innerHTML = fileName.substring(0, fileName.length-4) + "<br><br>";
		cell2.innerHTML = "<a href = '" + results[i].kmz + "'>" + "kmz" + "</a>" + ", <a href = '" + results[i].GeoTIFF + "'>" + "GeoTIFF" + "</a>"  + "<br><br>"	;		
	}
}

function highlightTable(index)
{ 
	index++;
	table = document.getElementById("resultsTable");
	table.rows[index].style.backgroundColor = '#FFFFE0';
}

function addDiagonalObject(x1, y1, x2, y2)
{
	diagonalObject =  new diagonalObjectConstructor(x1, y1, x2, y2)
	return diagonalObject;
}

function diagonalObjectConstructor(x1, y1, x2, y2)
{
	this.x1 = x1;
	this.y1 = y1;
	this.x2 = x2;
	this.y2 = y2;
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

//global variables
rectangleArray = new Array();
markerArray = new Array();









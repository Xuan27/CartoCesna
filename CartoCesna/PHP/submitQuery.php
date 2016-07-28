<?php
$diagonalCoords = json_decode($_POST['diagonalCoords']);
$x1 = $diagonalCoords -> x1;
$y1 = $diagonalCoords -> y1;
$x2 = $diagonalCoords -> x2;
$y2 = $diagonalCoords -> y2;
$spatialQuerySelection = $diagonalCoords -> spatialQuerySelection;

$servername = "localhost";
$username = "neilgibeaut";
$password = "Sadiedog1995";
$database = "spatialSearch_db";

//Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully";

if($spatialQuerySelection == "intersects")
{
$sql = "SELECT fileName, x1, y1, x3, y3, kmz
			FROM table1
			WHERE MBRIntersects(
			GeomFromText( 'LINESTRING($y1 $x1, $y2 $x2)' ),
			table1.diagonal) ";

$result = $conn->query($sql);

$GeoJson = array();
if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
		$GeoJson[] = $row;		
    }
} else {
    echo "0 results";
}
echo json_encode($GeoJson);

$conn->close();	
}

if($spatialQuerySelection == "contains")
{
$sql = "SELECT fileName, x1, y1, x3, y3, kmz
			FROM table1
			WHERE MBRContains(
			GeomFromText( 'LINESTRING($y1 $x1, $y2 $x2)' ),
			table1.diagonal) ";

$result = $conn->query($sql);

$GeoJson = array();
if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
		$GeoJson[] = $row;		
    }
} else {
    echo "0 results";
}
echo json_encode($GeoJson);

$conn->close();	
}

if($spatialQuerySelection == "containsCentroid")
{
$sql = "SELECT fileName, x1, y1, x3, y3, kmz
			FROM table1
			WHERE MBRContains(
			GeomFromText( 'LINESTRING($y1 $x1, $y2 $x2)' ),
			table1.centroid) ";

$result = $conn->query($sql);

$GeoJson = array();
if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
		$GeoJson[] = $row;		
    }
} else {
    echo "0 results";
}
echo json_encode($GeoJson);

$conn->close();	
}

?>
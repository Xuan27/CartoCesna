<?php
$queryObject = json_decode($_POST['queryObject']);
$x1 = $queryObject -> x1;
$y1 = $queryObject -> y1;
$x2 = $queryObject -> x2;
$y2 = $queryObject -> y2;
$spatialQuerySelection = $queryObject -> spatialQuerySelection;
$dateQuerySQL = $queryObject -> dateQuerySQL;
$authorSQL = $queryObject -> authorSQL;

$servername = "localhost";
$username = "root";
$password = "";
$database = "spatial_query";

//Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully";

if($spatialQuerySelection == "intersects")
{
$sql = "SELECT fileName, x1, y1, x3, y3, kmz, Author
			FROM table1
			WHERE MBRIntersects(
			GeomFromText( 'LINESTRING($y1 $x1, $y2 $x2)' ),
			table1.diagonal) $dateQuerySQL $authorSQL";

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
$sql = "SELECT fileName, x1, y1, x3, y3, kmz, Author
			FROM table1
			WHERE MBRContains(
			GeomFromText( 'LINESTRING($y1 $x1, $y2 $x2)' ),
			table1.diagonal) $dateQuerySQL $authorSQL";

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
$sql = "SELECT fileName, x1, y1, x3, y3, kmz, Author
			FROM table1
			WHERE MBRContains(
			GeomFromText( 'LINESTRING($y1 $x1, $y2 $x2)' ),
			table1.centroid) $dateQuerySQL $authorSQL";

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
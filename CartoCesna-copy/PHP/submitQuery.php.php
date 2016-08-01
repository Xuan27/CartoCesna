<?php
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
/*$Table = "CREATE TABLE geom2 (Filename VARCHAR(30) NOT NULL, g GEOMETRY)";
if ($conn->query($Table) === TRUE) {
    echo "Table MyGuests created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}*/

/*$Insert = "INSERT INTO geom2 (Filename, g) VALUES ('11.tif', ST_GeomFromText('POINT(-97.5858916 27.5570899)'))";
if ($conn->query($Insert) === TRUE) {
   // echo "New record created successfully";
} else {
    echo "Error: " . $Insert . "<br>" . $conn->error;
}*/


$sql = "SELECT ST_AsGeoJSON(g) FROM table1";

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
?>
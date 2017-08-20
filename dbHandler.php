<?php
$settingsFile = fopen("settings.json", "r");
$settingsObj = json_decode(fread($settingsFile, filesize("settings.json")));
fclose($settingsFile);

date_default_timezone_set($settingsObj->timezone);

if(isset($_GET['action'])) {
  if($_GET['action'] == "viewTable") {
    echo json_encode(getTrafficsData(openConn($settingsObj)));
  } else if($_GET['action'] == "purgeTable") {
    if(purgeTrafficsTable(openConn($settingsObj),
      getExpirySecs($settingsObj->dcTimeConstraint,
      $settingsObj->dcTimeUnit))) {
        echo "Records older than $settingsObj->dcTimeConstraint "
        ."$settingsObj->dcTimeUnit have been removed from the traffics"
        ."table.";
      } else {
        throw new Exception("purgeTrafficsTable() returned false in dbHandler.php.");
      }
  }
}

function openConn($settingsObj) {
	$servername = $settingsObj->db_servername;
	$username = $settingsObj->db_username;
	$password = $settingsObj->db_pass;
	$dbname = $settingsObj->db_schema;

	// Create connection
	$result = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($result->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
  
	return $result;
}

function getTrafficsData($conn) {
	$sql = "SELECT * FROM traffics";
	$result = $conn->query($sql);
	$conn->close();

  if ($result->num_rows > 0) {
    $temp = [];
    while($row = $result->fetch_assoc()) {
      array_push($temp, $row);
    }
    $result = $temp;
  } else {
    $result = "Traffics table is empty.";
  }
	return $result;
}

function purgeTrafficsTable($conn, $expirySecs) {
  $expirationThreshold = time() - $expirySecs;
  $expirationThreshold = date('Y-m-d H:i:s', $expirationThreshold);
  $sql = "DELETE FROM traffics WHERE trafficdate < '".$expirationThreshold."'";
  $result = $conn->query($sql);
  $conn->close();
  return $result;
}

function getExpirySecs($timeFactor, $timeMultiplierStr) {
  switch($timeMultiplierStr) {
    case "minutes": return $timeFactor * 60;
    case "hours": return $timeFactor * 60 * 60;
    case "days": return $timeFactor * 60 * 60 * 24;
    case "weeks": return $timeFactor * 60 * 60 * 24 * 7;
    default: throw new Exception("Unknown time unit: " . $timeMultiplierStr);
  }
}
?>

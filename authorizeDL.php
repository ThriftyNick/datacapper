<?php
	$settingsFile = fopen("datacapper/settings.json", "r");
	$settingsObj = json_decode(fread($settingsFile, filesize("datacapper/settings.json")));
	fclose($settingsFile);
	date_default_timezone_set($settingsObj->timezone);
	$GLOBALS['dataCap'] = $settingsObj->dcAmt *
		getDCMultiplier($settingsObj->dcUnit);

	if (isset($_SERVER['PHP_AUTH_USER'])) {
		$GLOBALS['username'] = $_SERVER['PHP_AUTH_USER'];
	} else if (isset($_SERVER['REMOTE_USER'])) {
		$GLOBALS['username'] = $_SERVER['REMOTE_USER'];
	} else if (isset($_SERVER['REDIRECT_REMOTE_USER'])) {
		$GLOBALS['username'] = $_SERVER['REDIRECT_REMOTE_USER'];
	} else {
		throw new Exception("Unable to fetch username");
	}

	function authorizeDownload($filename, $settingsObj) {
		$servername = $settingsObj->db_servername;
		$username = $settingsObj->db_username;
		$password = $settingsObj->db_pass;
		$dbname = $settingsObj->db_schema;

		$timestamp = date('Y-m-d H:i:s');

		// Create connection
		$conn = new mysqli($servername, $username, $password, $dbname);
		// Check connection
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}

		$arr = explode("/", $filename);
		$filename = $arr[count($arr) - 1];

		if (!file_exists($filename)) {
			if($filename == "") {return "authorized";}
			throw new Exception($filename . " Doesn't exist.");
		}
		$filesize = filesize($filename);

		$stmt = $conn->prepare("SELECT trafficused, trafficdate FROM traffics WHERE userid = ?");
		$stmt->bind_param("s", $GLOBALS['username']);
		$stmt->execute();
		$stmt->bind_result($trafficused, $trafficdate);
		$stmt->fetch();
		$stmt->close();
		if (!$trafficdate) { //add new record into traffics if user hasn't yet downloaded anything.
			$stmt = $conn->prepare("INSERT INTO traffics (userid, trafficdate, trafficused) VALUES (?, ?, ?)");
			$stmt->bind_param("ssi", $GLOBALS['username'], $timestamp, $filesize);
			$stmt->execute();
			$stmt->close();
			$conn->close();
			return "authorized"; //we can return since a single file should never be enough to surpass the dataCap.
		}

		//reset trafficused if last traffic is past expiration period.
		$lastTrafficDate = strtotime($trafficdate);
		//if ($lastTrafficDate < time() - (1 * 60 * 60 * 24)) {
		if ($lastTrafficDate < time() - ($settingsObj->dcTimeConstraint *
			getTimeMultiplier($settingsObj->dcTimeUnit))) {
			$stmt = $conn->prepare("UPDATE traffics SET trafficused = 0 WHERE userid = ?");
			$stmt->bind_param("s", $GLOBALS['username']);
			$stmt->execute();
			$stmt->close();
			$trafficused = 0;
		}

		//check if file size will put user over their daily limit
		$totalTrafficUsed = $filesize + $trafficused;
		if ($totalTrafficUsed > $GLOBALS['dataCap']) {
			$conn->close();
			return "unauthorized";
		}

		//Update the traffic usage for the user
		$stmt = $conn->prepare("UPDATE traffics SET trafficused = $totalTrafficUsed, trafficdate = ? WHERE userid = ?");
		$stmt->bind_param("ss", $timestamp, $GLOBALS['username']);
		$stmt->execute();
		$stmt->close();
		$conn->close();
		return "authorized";
	}

	function getDCMultiplier($unitAbbrv) {
		switch($unitAbbrv) {
			case "B": return 1;
			case "KB": return 1024;
			case "MB": return 1024 * 1024;
			case "GB": return 1024 * 1024 * 1024;
			case "TB": return 1024 * 1024 * 1024 * 1024;
			default: throw new Exception("Unknown unit of data measure: "
				. $unitAbbrv);
		}
	}

	function getTimeMultiplier($timeMultiplierStr) {
		switch($timeMultiplierStr) {
			case "minutes": return 60;
			case "hours": return 60 * 60;
			case "days": return 60 * 60 * 24;
			case "weeks": return 60 * 60 * 24 * 7;
			default: throw new Exception("Unknown time unit: " . $timeMultiplierStr);
		}
	}
?>

<!DOCTYPE html>
<html lang="en-US">
<head><title>Authorize Download</title>
<meta charset="UTF-8">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
	var result = $("#input").text();
	if (result == 'authorized') {
		var url_string = document.URL;
		var url = new URL(url_string);
		var file = url.searchParams.get('file');
		window.location.assign(file);
	} else if (result == 'unauthorized') {
		$("#output").text("Datacap Exceeded.  Your datacap will be reset in " + <?php echo $settingsObj->dcTimeConstraint . " " . $settingsObj->dcTimeUnit . "."; ?>);
	} else {
		$("#output").text(result);
	}
});
</script>
</head>
<body>
<h1 id="input"><?php echo authorizeDownload($_GET['file'], $settingsObj); ?></h1>
<h1 id="output"><h1>
</body>

</html>

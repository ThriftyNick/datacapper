<!DOCTYPE html>
<html lang="en-US">
<meta charset="UTF-8">
<head><title>DataCapper Install</title>
<?php
if ($_POST != null) {
	install();
}

function install() {
		echo "<p class='statusMessage'>Setting htaccess rules</p>";
		generateHtAccessFile(getAllFileTypes());
		echo "<p class='statusMessage'>Building traffics table</p>";

		if (isset($_SERVER['HTTPS'])){
			$dbServer = $_POST['servername'];
			$dbUser = $_POST['username'];
			$dbPass = $_POST['pass'];
			$dbSchema = $_POST['schema'];
		} else {
			//TODO: CHANGE THE 4 VARIABLES BELOW TO THE CORRECT VALUES!  Keep single quotes around each value.
			$dbServer = 'localhost';
			$dbUser = 'root';
			$dbPass = 'sesame';
			$dbSchema = 'data_capper';
		}
		$dbInfo = array('dbServer'=>$dbServer, 'dbUser'=>$dbUser, 'dbPass'=>$dbPass, 'dbSchema'=>$dbSchema);
		createTrafficsTable($dbInfo);
		echo "<p class='statusMessage'>Initializing Config settings file</p>";
		initConfigFile($dbInfo);

		if (file_exists("authorizeDL.php")) {
			echo "<p class='statusMessage'>Moving authorizeDL.php to parent directory</p>";
			moveAuthDLToParent();
		} else if(!file_exists("../authorizeDL.php")) {
			throw new Exception("Missing authorizeDL.php.  Please re-upload this file into this directory and restart the installation.");
		}

		echo "<p class='statusMessage'>Installation Complete.</p>";
		echo "<p style='font-weight:bold;'>Please ensure the following:</p>";
		echo "<p><ul>
		<li>.htaccess file exists in your target directory.</li>
		<li>authorizeDL.php exists in your target directory.</li>
		<li>traffics table has been created in your target database.</li>
		</ul></p>";
		echo "<a href='cPanel.php'>DataCapper Control Panel</a>";
}

function initConfigFile($dbInfo) {
	$data = [
		"timezone" => $_POST['timezone'],
		"dcAmt" => $_POST['dcAmt'],
		"dcUnit" => $_POST['dcUnit'],
		"dcTimeConstraint" => $_POST['timeConstraint'],
		"dcTimeUnit" => $_POST['timeConstraintUnit'],
		"db_servername" => $dbInfo['dbServer'],
		"db_schema" => $dbInfo['dbSchema'],
		"db_username" => $dbInfo['dbUser'],
		"db_pass" => $dbInfo['dbPass'],
		"filetypes" => getAllFileTypes()
	];

	$x = json_encode($data);

	$fileHandler = fopen("settings.json", "w");
	fwrite($fileHandler, $x);
	fclose($fileHandler);
}

function getAllFileTypes() {
	$result = [];
	foreach ($_POST as $field => $val) {
		if (substr($field, 0, 8) == "filetype") {
			array_push($result, $val);
		}
	}
	return $result;
}

function generateHtAccessFile($fileTypes) {
	$existing_contents = "";
	if (file_exists("../.htaccess")) {
		//open as r+
		$htaccess_file = fopen("../.htaccess", "r+") or die("Unable to open file");
		//get the existing contents
		rewind($htaccess_file);
		while(!feof($htaccess_file)) {
			$existing_contents .= fgets($htaccess_file);
		}
	} else {
		//open as w+
		$htaccess_file = fopen("../.htaccess", "w+") or die("Unable to open file");
	}

	//create fileTypes string from array of all filetypes
	$fileTypesStr = "\\.(";
	if(count($fileTypes) === 0) {
		$fileTypesStr = "!\\.(php|html)$";
	}	else {
		foreach ($fileTypes as $fileType) {
			$fileTypesStr .= $fileType . "|";
		}
		$fileTypesStr = chop($fileTypesStr, "|");
		$fileTypesStr .= ")$";
	}
	$fileTypesStr = " " . $fileTypesStr;

	//create the new rules
	$htaccess_string = "\nRewriteEngine on\n"
		. "RewriteBase /\n\n"
		. "#Allow if referred from authorizeDL.php\n"
		. "RewriteCond %{HTTP_REFERER} ^" . getRefererStringRE() . ".*$ [NC]\n"
		. "RewriteRule ^ - [L]\n\n"
		. "#Redirect to authorizeDL.php if coming from anywhere else\n"
		. "RewriteRule".$fileTypesStr." http://" . getScriptPath() . "?file=%{REQUEST_URI} [R,L]\n";

	//if existing contents is identical to new rules except for last line: only replace last line
	$lastLineStartPattern = "RewriteRule";
	$strPosStartLocX = strpos($htaccess_string, "anywhere else\n");
	$strPosStartLocY = strpos($existing_contents, "anywhere else\n");
	$x = substr($htaccess_string, 0, strpos($htaccess_string, $lastLineStartPattern, $strPosStartLocX));
	$y = $existing_contents;
	if(strpos($existing_contents, $lastLineStartPattern, $strPosStartLocY) > 0) {
		$startPos = strpos($existing_contents, "\nRewriteEngine on\n");
		$endPos = strpos($existing_contents, $lastLineStartPattern, $strPosStartLocY);
		$y = substr($existing_contents, $startPos, $endPos - $startPos);
	}

	if ($x == $y) {
		//only replace last line
		$lastLine = substr($htaccess_string, strpos($htaccess_string, $lastLineStartPattern, $strPosStartLocX));
		$existingContentsLastLine = substr($existing_contents, strpos($existing_contents, $lastLineStartPattern, $strPosStartLocY));
		$offset = 0 - strlen($existingContentsLastLine);
		fseek($htaccess_file, $offset, SEEK_END);
		fwrite($htaccess_file, $lastLine);
		$newSize = strlen($existing_contents) + $offset + strlen($lastLine);
		ftruncate($htaccess_file, $newSize);
	} else if(!stristr($existing_contents, $htaccess_string)){
		fwrite($htaccess_file, $htaccess_string);
	}

	fclose($htaccess_file);
}

function getRefererStringRE() {
	$result = "http://(www\.)?";
	$arr = explode(".", getScriptPath());
	$result .= implode("\.", $arr);

	return $result;
}

function getScriptPath() {
	$result = $_SERVER['SERVER_NAME'];
	$result .= $_SERVER['REQUEST_URI'];
	$result = substr($result, 0, strpos($result, "datacapper/install.php"));
	$result .= "authorizeDL.php";

	return $result;
}

function createTrafficsTable($dbInfo) {
	// Create connection
	$conn = new mysqli($dbInfo['dbServer'], $dbInfo['dbUser'], $dbInfo['dbPass'], $dbInfo['dbSchema']);
	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "DROP TABLE IF EXISTS `traffics`";
	$conn->query($sql);
	$sql = "CREATE TABLE `traffics` (`userid` varchar(20) NOT NULL, `trafficdate` datetime NOT NULL, `trafficused` int(11) DEFAULT NULL)";
	$conn->query($sql);
	$conn->close();
}

/*Moves authorizeDL.php out of admin protected directory and into target directory*/
function moveAuthDLToParent() {
	rename("authorizeDL.php", "../authorizeDL.php");
}

function getDCUnitStr($dcUnitAbbrv) {
	switch($dcUnitAbbrv) {
		case "B": return "* 1";
		case "KB": return "* 1024";
		case "MB": return "* 1024 * 1024";
		case "GB": return "* 1024 * 1024 * 1024";
		case "TB": return "* 1024 * 1024 * 1024 * 1024";
		default: throw new Exception("Unknown unit of data measure: " . $dcUnit);
	}
}

function getTimeConstraintUnitStr($timeConstraintUnit) {
	switch($timeConstraintUnit) {
		case "minutes": return "* 60";
		case "hours": return "* 60 * 60";
		case "days": return "* 60 * 60 * 24";
		case "weeks": return "* 60 * 60 * 24 * 7";
		default: throw new Exception("Unknown time constraint unit: " . $timeConstraintUnit);
	}
}
?>
<style>
p.statusMessage {
	color: powderblue;
	background-color: black;
	font-weight: bold;
	padding: 2px 5px;
}
</style>
</head>
</html>

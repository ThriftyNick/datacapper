<!DOCTYPE html>
<html lang="en-US">
<meta charset="UTF-8">
<head><title>DataCapper Config</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
	var fileTypesField = $("input[list='filetypes']");
	var addButton = fileTypesField.next().next();
	var allFiletypesCkBx = addButton.next();
	var fileTypes = new Set();
	var datalistOpen = false;

	var addFileType = (function(){
		var fileExt = fileTypesField.val();
		fileTypesField.val("");
		if(/^$|\s/.test(fileExt)) {return;}

		//strip leading "." if present
		fileExt = fileExt.replace(".", "");

		if(!fileTypes.has(fileExt)) {
			fileTypes.add(fileExt);
			var newPill = $("<div class='pill'></div>");
			//var newPillContents = $("<p></p>").text(fileExt);
			newPill.append($("<p></p>").text(fileExt));
			newPill.append($("<p></p>").html("&times;"));
			$("#filetypeListContainer").append(newPill);
			var formField = "filetype" + fileTypes.size;
			$("#filetypeListContainer").append($("<input type='hidden' name='"+formField+"' value='"+fileExt+"'>"));

			newPill.children("p:nth-child(2)").on('click', function(){
				removeFileType($(this).parent(), $("input[value="+fileExt+"]"));
			});
		}
	});

	var removeFileType = (function(pill, param){
		fileTypes.delete(pill.children("p:first-child").text());
		pill.remove();
		param.remove();
	});

	fileTypesField.on('keydown', function(e){
		if (e.keyCode === 40 || e.keyCode === 38) { //down or up arrow
			datalistOpen = true;
		} else if (datalistOpen && e.keyCode === 13) { //"Enter" is pressed and datalist is open
			datalistOpen = false; //do the default action and select the highlighted item
		}
		else if (!datalistOpen && e.keyCode === 13) { //"Enter" is pressed and datalist is not open
			e.preventDefault(); //do not submit form
			addFileType();
		}
	});

	addButton.click(function(){
		addFileType();
	});

	allFiletypesCkBx.change(function(){
		fileTypesField.attr("disabled", this.checked);
		addButton.attr("disabled", this.checked);
		if (this.checked) {
			fileTypes.clear();
			$("#filetypeListContainer").empty();
		}
	});
});
</script>

<style>
#filetypeListContainer {
	display:inline-flex;
	display: -webkit-inline-flex;
	flex-wrap: wrap;
	width: 25%;
	margin-bottom: 10px;
}

.pill {
	border: 1px solid grey;
	border-radius: 3px;
	background-color: powderblue;
	margin: 3px;
	padding: 2px 5px;
}

.pill p {
	display: inline-flex;
	display: -webkit-inline-flex;
	margin: 0;
}

.pill p:first-child {

	padding: 0 4px 0 0;
	border-right: 1px solid grey;
}

.pill p:nth-child(2) {
	font-weight: bold;
	margin-left: 4px;
	padding: 0 4px;
	border-radius: 0 3px 3px 0;
	background-color: rgba(60, 60, 60, 0.5);
	color: powderblue;
	cursor: pointer;
}

.pill p:nth-child(2):hover {
	background-color: rgba(60, 60, 60, 1);
	color: red;
}

</style>
</head>
<body>
<h1>DataCapper Config/Installation</h1>
<p>Please ensure the following has been completed before you continue:</p>
<ol>
<li>You've arrived at this page as per the instructions in README.txt</li>
<li>A schema has been identified where the traffics table will reside.</li>
<li>the .htaccess file has been updated with the admin username and uploaded into the same directory as config.php (this page).</li>
</ol>
<form action="install.php" method="post">
	<label>Timezone:</label>
	<select name="timezone">
		<?php
		$allZones = timezone_identifiers_list();
		$assumedZone = str_replace(" ", "_", date_default_timezone_get());
		foreach($allZones as $zone) {
			$optionStr = $zone == $assumedZone ? '<option value="'.$zone.'" selected>'.$zone.'</option>' : '<option value="'.$zone.'">'.$zone.'</option>';
			echo $optionStr;
		}
		?>
	</select><br><br>
	<?php
	if (isset($_SERVER['HTTPS'])) {
		echo '
		<fieldset><legend>Database</legend>
		<label>Server Name:</label> <input type="text" name="servername" value="localhost"><br><br>
		<label>Username:</label> <input type="text" name="username"><br><br>
		<label>Password:</label> <input type = "password" name="pass"><br><br>
		<label>Schema Name:</label> <input type = "text" name="schema">
		</fieldset><br>';
	} else {
		echo '
		<fieldset><legend>Database - SSL not detected.  Please enter this information into config.php manually as instructed in README.txt</legend>
		<label>Server Name:</label> <input type="text" name="servername" value="localhost" disabled><br><br>
		<label>Username:</label> <input type="text" name="username" disabled><br><br>
		<label>Password:</label> <input type = "password" name="pass" disabled><br><br>
		<label>Schema Name:</label> <input type = "text" name="schema" disabled>
		</fieldset><br>';
	}
	?>
	<label>Set datacap on the following filetypes: </label>
	<input list="filetypes" required>
	<datalist id="filetypes">
		<!--Image-->
		<option value="gif">
		<option value="jpg">
		<option value="jpeg">
		<option value="png">
		<option value="bmp">
		<option value="tiff">
		<option value="svg">
		<!--Audio-->
		<option value="aac">
		<option value="aiff">
		<option value="mp3">
		<option value="m4a">
		<option value="m4b">
		<option value="m4p">
		<option value="wav">
		<option value="ogg">
		<option value="oga">
		<option value="ra">
		<option value="wma">
		<!--Video-->
		<option value="3gp">
		<option value="3g2">
		<option value="mpg">
		<option value="mpeg">
		<option value="m2v">
		<option value="mp4">
		<option value="wmv">
		<option value="avi">
		<option value="flv">
		<option value="f4v">
		<option value="mov">
		<option value="webm">
		<option value="mkv">
		<option value="ogv">
		<option value="rm">
		<option value="rmvb">
		<option value="asf">
		<!--Archive/Compression-->
		<option value="zip">
		<option value="7z">
		<option value="rar">
		<option value="bin">
		<!--Other-->
		<option value="exe">
		<option value="jar">
		<option value="tar">
		<option value="iso">
	</datalist>
	<input type="button" value="Add">
	<input type="checkbox">All Filetypes</input><br>
	<div id="filetypeListContainer"></div><br>
	<label>Datacap amount:</label> <input type="number" min="1" name="dcAmt" value="1" required>
		<select name="dcUnit">
			<option value="B">B</option>
			<option value="KB">Kb</option>
			<option value="MB">Mb</option>
			<option value="GB" selected>Gb</option>
			<option value="TB">Tb</option>
		</select><br><br>
	<label>Reset Data Usage Every: </label><input type="number" min="1" name="timeConstraint" value="1" required>
		<select name="timeConstraintUnit">
			<option value="minutes">Minutes</option>
			<option value="hours">Hours</option>
			<option value="days" selected>Days</option>
			<option value="weeks">Weeks</option>
		</select><br><br>
	<input type="submit" value="Install">
</form>
</body>
</html>

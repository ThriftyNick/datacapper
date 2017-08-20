<!DOCTYPE html>
<html lang="en-US">
<meta charset="UTF-8">
<head>
<style>
table, th, td {
	border: 1px solid black;
	border-collapse: collapse;
	padding: 0 10px;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
	$("#viewTableBtn").click(function(){
		$.get("dbHandler.php?action=viewTable", function(data, status){
			$("#actionResult").html(constructHtmlTable(data));
		});
	});

	$("#purgeTableBtn").click(function(){
		$.get("dbHandler.php?action=purgeTable", function(data, status){
			$("#actionResult").html("\""+data+"\"");
		})
	});
});

function constructHtmlTable(jsonData) {
	if(jsonData == "\"Traffics table is empty.\"") {
		return jsonData;
	}
	var result = "<table>";
	result += "<tr><th>userid</th><th>trafficdate</th><th>trafficused (Bytes)</th></tr>";
	try {
		var tableData = JSON.parse(jsonData);
	} catch (err) {
		return jsonData;
	}
	var i;
	for(i = 0; i < tableData.length; i++) {
		var row = tableData[i];
		result += "<tr><td>"+row.userid+"</td><td>"+row.trafficdate+"</td><td>"+row.trafficused+"</td></tr>"
	}
	//result += "</table>";
	return result + "</table>";
}
</script>
</head>
<body>
<h1>Manage traffics Table</h1>
<button id="viewTableBtn">View Table</button>
<button id="purgeTableBtn">Purge Expired Records</button>
<br><br>
<div id="actionResult"></div>
<br><br>
<a href='cPanel.php'>DataCapper Control Panel</a>
</body>
</html>

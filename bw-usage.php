<?php
$router_ip = "192.168.1.254";
$community = "public";

$snmp['sysName']		= snmpget($router_ip, $community, "RFC1213-MIB::sysName.0");
$snmp['sysDescr']		= snmpget($router_ip, $community, "RFC1213-MIB::sysDescr.0");
$snmp['sysLocation']	= snmpget($router_ip, $community, "RFC1213-MIB::sysLocation.0");

$snmp['AttainableRate_Downstream'] = snmpget($router_ip, $community, ".1.3.6.1.2.1.10.94.1.1.3.1.8.2.1");
$snmp['AttainableRate_Downstream'] = eregi_replace("Gauge32: ","",$snmp['AttainableRate_Downstream']);
$snmp['AttainableRate_Upstream'] = snmpget($router_ip, $community, ".1.3.6.1.2.1.10.94.1.1.2.1.8.2.1");
$snmp['AttainableRate_Upstream'] = eregi_replace("Gauge32: ","",$snmp['AttainableRate_Upstream']);

$snmp['Rate_Downstream'] = snmpget($router_ip, $community, ".1.3.6.1.2.1.10.94.1.1.5.1.3.2.1");
$snmp['Rate_Downstream'] = eregi_replace("Gauge32: ","",$snmp['Rate_Downstream']) * 1024;
$snmp['Rate_Upstream'] = snmpget($router_ip, $community, ".1.3.6.1.2.1.10.94.1.1.5.1.2.2.1");
$snmp['Rate_Upstream'] = eregi_replace("Gauge32: ","",$snmp['Rate_Upstream']) * 1024;

?>
<html>
<head>

<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/redmond/jquery-ui.css">

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script>
google.load("jquery", "1.7");
google.load("jqueryui", "1");
google.load("webfont", "1.0.26");
google.load('visualization', '1', { packages:['gauge'] });
//google.setOnLoadCallback(drawChart);
</script>
<script type="text/javascript" src="_assets/js/ajaxPoll/ajaxPoll.js"></script>

<link href='http://fonts.googleapis.com/css?family=Droid+Sans' rel='stylesheet' type='text/css'>

<script>
var speed = new Array();
var bw_percent = new Array();

$(document).ready(function() {

$.ajaxPollSettings.pollingType = "interval";
$.ajaxPollSettings.interval = 5000;
$.ajaxPollSettings.maxInterval = 5000;
$.ajaxPollSettings.durationUntilMaxInterval = 2000;

$.ajaxPoll({
  // setup the request
  url: 'bw-rates.php',
  crossDomain: true,
  dataType: 'jsonp',
  jsonp: true,
  jsonpCallback: 'callback', // "supply" the jsonp function (pseudo-defined)

  complete: function(data){
//console.log(data);
  },

  successCondition: function(data){
    $.each(data, function(key, rawvalue){
			switch(key){
			case "average_in":
				bw_percent['downstream'] = (rawvalue/<?php echo $snmp['Rate_Downstream'];?>)*100;
				speed['downstream'] = rawvalue;
				break;	
			case "average_out":
				bw_percent['upstream'] = (rawvalue/<?php echo $snmp['Rate_Upstream'];?>)*100;
				speed['upstream'] = rawvalue;
				break;	
			default:
//				bw_percent['thing'] = 0;
			}
    });

// Success, non-repeat
		speed_label = ' kbps';

    $("#average_in.progressbar").progressbar({ value: bw_percent['downstream'] });
    $("#average_in.progressbar .label-value").text( Math.round(speed['downstream']/1024) + speed_label );
    $("#average_out.progressbar").progressbar({ value: bw_percent['upstream'] });
    $("#average_out.progressbar .label-value").text(Math.round(speed['upstream']/1024) + speed_label);

		var dataDownstream = new google.visualization.DataTable();
		dataDownstream.addColumn('string', 'Label');
		dataDownstream.addColumn('number', 'Value');
		dataDownstream.addRows([	['Incoming', 25]	]);

		var dataUpstream = new google.visualization.DataTable();
		dataUpstream.addColumn('string', 'Label');
		dataUpstream.addColumn('number', 'Value');
		dataUpstream.addRows([	['Outgoing', 75]	]);

		upMax = Math.round(<?php echo $snmp['Rate_Downstream'];?>/1024/1024);
		redFromLow = upMax * 0.9;
		YellowFromLow = upMax * 0.75;
		var optionsDownstream = {
			width: 250, height: 250,
			greenFrom:0, greenTo: YellowFromLow,
			yellowFrom:YellowFromLow, yellowTo: redFromLow,
			redFrom: redFromLow, redTo: upMax,
			minorTicks: 5,
			min: 0, max: upMax,
			animation:{
				easing: 'inAndOut',
				duration: 1000
			}
		};

		upMax = Math.round(<?php echo $snmp['Rate_Upstream'];?>/1024/1024);
		redFromLow = upMax * 0.75;
		YellowFromLow = upMax * 0.50;
		var optionsUpstream = {
			width: 250, height: 250,
			greenFrom:0, greenTo: YellowFromLow,
			yellowFrom:YellowFromLow, yellowTo: redFromLow,
			redFrom: redFromLow, redTo: upMax,
			minorTicks: 5,
			min: 0,
			max: upMax,
			animation:{
				easing: 'inAndOut',
				duration: 1000
			}
		};

if(typeof(chart_download) == 'undefined')
		var chart_download = new google.visualization.Gauge(document.getElementById('chart_download'));

if(typeof(chart_upload) == 'undefined')
		var chart_upload = new google.visualization.Gauge(document.getElementById('chart_upload'));
		
		dataspeedDS = parseFloat((speed['downstream']/1024/1024).toFixed(2));
		dataspeedUS = parseFloat((speed['upstream']/1024/1024).toFixed(2));
				
		dataDownstream.setValue(0, 1, dataspeedDS );
		dataUpstream.setValue(0, 1, dataspeedUS );

chart_download.clearChart();
chart_upload.clearChart();
		
		chart_download.draw(dataDownstream, optionsDownstream);
		chart_upload.draw(dataUpstream, optionsUpstream);

		var date = new Date(data.timestamp * 1000);
		var hours = date.getHours();
		var minutes = date.getMinutes();
		var seconds = date.getSeconds();
		var formattedTime = hours + ':' + minutes + ':' + seconds;
		$("#timestamp").text(date.toLocaleString());

  },
  error: function(j,t,e){
    alert('AJAX Error');
  }
});

var attrateDS = <?php echo $snmp['Rate_Downstream'];?>/1024;
var attrateUS = <?php echo $snmp['Rate_Upstream'];?>/1024;

$("#average_in.progressbar .label-max").text(attrateDS);
$("#average_out.progressbar .label-max").text(attrateUS);


var dropbox;  
dropbox = document.getElementById("dropbody");  
dropbox.addEventListener("dragenter", dragenter, false);  
dropbox.addEventListener("dragover", dragover, true);  
dropbox.addEventListener("drop", drop, false);  
dropbox.addEventListener("dragleave", dragleave, false);

});

function transfertime(filetime)	{
	var transferTime = "";
	hourmod = filetime % 3600;
	hour = Math.floor(filetime / 3600);
	minute = Math.floor(hourmod / 60);
	second = Math.floor(filetime % 60);
	if (hour > 0)
		transferTime = hour + "h ";

	if (minute > 0)
		transferTime += minute + "m ";

	if (second > 0)
		transferTime += second + "s";

	return transferTime;
}


function calc(factor)	{
	var filesize = parseFloat(document.bwcalc.filesize.value);

	var filetime_download = (((factor * filesize))*8) / <?php echo $snmp['Rate_Downstream'];?>;
	var filetime_upload = (((factor * filesize))*8) / <?php echo $snmp['Rate_Upstream'];?>;

	var filetime_real_download = (((factor * filesize))*8) / (<?php echo $snmp['Rate_Downstream'];?>) * 2;
	var filetime_real_upload = (((factor * filesize))*8) / (<?php echo $snmp['Rate_Upstream'];?>) * 2;

	var filetime_real_bdownload = (((factor * filesize))*8) / (<?php echo $snmp['Rate_Downstream'];?> - speed["downstream"]) * 2;
	var filetime_real_bupload = (((factor * filesize))*8) / (<?php echo $snmp['Rate_Upstream'];?> - speed["upstream"]) * 2;

	transferTimeDown = transfertime(filetime_download);
	transferTimeUp	 = transfertime(filetime_upload);

	transferRealTimeDown = transfertime( filetime_real_download );
	transferRealTimeUp	 = transfertime( filetime_real_upload );

	transferRealBTimeDown = transfertime( filetime_real_bdownload );
	transferRealBTimeUp	 = transfertime( filetime_real_bupload );

	$("#result-download").text(transferTimeDown);
	$("#result-upload").text(transferTimeUp);

	$("#real-download").text( transferRealTimeDown );
	$("#real-upload").text( transferRealTimeUp );

	$("#real-bdownload").text( transferRealBTimeDown );
	$("#real-bupload").text( transferRealBTimeUp );

}


function updateSize() {  
  var nBytes = 0,  
      oFiles = document.getElementById("uploadInput").files,  
      nFiles = oFiles.length;  
  for (var nFileId = 0; nFileId < nFiles; nFileId++) {  
    nBytes += oFiles[nFileId].size;  
  }  
  var sOutput = nBytes + " bytes";  
  // optional code for multiples approximation  
  for (var aMultiples = ["KiB", "MiB", "GiB", "TiB", "PiB", "EiB", "ZiB", "YiB"], nMultiple = 0, nApprox = nBytes / 1024; nApprox > 1; nApprox /= 1024, nMultiple++) {  
    sOutput = nApprox.toFixed(3) + " " + aMultiples[nMultiple] + " (" + nBytes + " bytes)";  
  }  
  // end of optional code  
  document.getElementById("fileNum").innerHTML = nFiles;  
  document.getElementById("fileSize").innerHTML = sOutput;  
}


function dragenter(e) {  
console.log("dragenter");
$("#dropfileinfowindow").text("Don't be shy, drop it!").animate({ opacity: 0.50, }, 100, function() {});
$("#dropfileinfowindow").show();
  e.stopPropagation();  
  e.preventDefault();  
}  
  
function dragover(e) {
console.log("dragover");
$("#dropfileinfowindow").text("Don't be shy, drop it!");
  e.stopPropagation();  
  e.preventDefault();  
}

function dragleave(e) {  
console.log("dragleave");
//$("#dropfileinfowindow").text("Oh, never mind then :(");
  e.stopPropagation();  
  e.preventDefault();  
}

function drop(e) {  
console.log("drop");
$("#dropfileinfowindow").animate({ opacity: 1, }, 1000, function() {}).text("Ta :)");
$("#result").show();
//window.location = "#calculator";

  e.stopPropagation();  
  e.preventDefault();  
  
  var dt = e.dataTransfer;  
  var files = dt.files;  
  handleFiles(files);  
}

function handleFiles(files)	{
var totalfilesize = 0;
	for (var i = 0; i < files.length; i++) { 
		var file = files[i];
		totalfilesize += file.size;
	}
	$("#dropbox").text(totalfilesize +" bytes");
	$("#bwcalc #filesize").val(totalfilesize);
	calc(1);
}

</script>

<style>
BODY {
  font-family: 'Droid Sans', sans-serif;
  margin-bottom: 100px;
}

#wrapper	{
	width: 800px;
	margin: 0 auto;
	
	min-height: 100%;
	height: 100%;
}

#header	{
background: -moz-linear-gradient(#fafafa,#eaeaea);
background: -webkit-linear-gradient(#fafafa,#eaeaea);
-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr='#fafafa',endColorstr='#eaeaea')";
position: relative;
z-index: 10;
border-bottom: 1px solid #cacaca;
box-shadow: 0 1px 0 rgba(255,255,255,0.4),0 0 10px rgba(0,0,0,0.1);

}


H1, H2, H3	{
	text-align: center;
	margin-top: 0;
	margin-bottom: 0.5em;
}

#gauges	{
	width: 800px;
	height: 250px;
	margin: 0 auto;
	margin-bottom: 20px;
}



#result	{
/*	display: none;	*/
	margin-top: 0px;
	float: left;
	margin-left: 10px;
	width: 390px;
	margin-bottom: 50px;
}

.ui-progressbar { position: relative;	}
.ui-progressbar .label-value	{ position: absolute; top: 8px; left: 8px; }
.ui-progressbar .label-max		{ position: absolute; top: 8px; right: 8px; }

#channels {
  width: 600px;
}
#channels .meter {
  width: 30px !important;
  float: left;
}

#feel-o-meter {
	font-size: 8pt; width: 246px;
	position: relative;
	top: 30px;
}
#feel-o-meter CAPTION	{
font-size: medium;
font-weight: bold;	
}

P	{
	margin-top: 0;
	margin-bottom: 0.5em;	
}

.hint	{
	font-size: x-small;
		
}

</style>

</head>
<body o!nload="updateSize();" id="dropbody">

<div id="wrapper">

<div id="header">
[]
</div>


	<h1>Office Bandwidth</h1>
	
	<p>Every minute this page will show you how much 'Internet' the Office is using.<br>
	This is shown as "Incoming" and "Outgoing" as two gauges.</p>
	
	<p>Remember that you'll be able to get files (<em>download</em>) <strong><?php echo round( $snmp['Rate_Downstream'] / $snmp['Rate_Upstream'] ) ;?> times</strong> faster than you can send files (<em>upload</em>).</p>
	
<!--	<p>Timestamp: <span id="timestamp"></span> </p> 
-->

	<div id="gauges">
		<div id="chart_download" style="float: left">O</div>
		<div id="chart_upload" style="float: left">O</div>

		<table id="feel-o-meter" border="0" cellpadding="5" cellspacing="5">
		<caption>Feel-o-meter</caption>
		<tbody>
			<tr><td style="background-color: green; color: white">Green</td><td>Everybody will be able get and send files without slowing down others</td></tr>
			<tr><td style="background-color: Orange; color: white">Orange</td><td>Some people like web developers might feel the Internet a little unresponsive</td></tr>
			<tr><td style="background-color: red; color: white">Red</td><td>Somebody's likely getting or sending something big. Most people may feel the Internet quite sluggish.</td></tr>
		</tbody>
		</table>
	</div>

	<br style="clear: both">

	
	<!--
	<div id="channels" style="width: 415px;">
	  <div id="average_in"	class="progressbar"><div class="label-value">A</div><div class="label-max">[MAX_DOWNSTREAM]</div></div>
	  <div id="average_out" class="progressbar"><div class="label-value">B</div><div class="label-max">[MAX_UPSTREAM]</div></div>
	</div>
	-->
	
<div id="filecalc">
	<h2 id="calculator">File transfer calculator</h2>

	<div style="
	display: block;
	width: 390px;
	min-height: 100px;
	height: 200px;
	float: left;
	margin-right: 10px;
	">
		<div id="dropfileinfowindow" style="
	min-height: 100px;
	height: 100px;
	width: 390px;
	background-color: #DDDDDD;
	border: 3px dashed #BBBBBB;
	padding: 10px;
	text-align: center;
	display: table-cell;
	vertical-align: middle;
	left: 0px;
	border-radius: 15px;
		">
			<p>Drag your file(s) in to this window to instantly work out the transfer time</p>
			<p class="hint">For best results please use files only, preferable a single ZIP file as folder don't work unfortunately.</p>
		</div>
	
		<form name="bwcalc" id="bwcalc" style="
	width: 390px;
	margin-top: 20px;
	">
		<p class="hint">Alternatively you can just type the file size value and select one of the "unit" buttons</p>
		<fieldset>
			<div style="float: left">
				<label for="filesize">File size/thing</label>
				<input type="text" name="filesize" id="filesize">
			</div>
			<div style="float: left">
				<label accesskey="b"><input onclick="calc(1)" type="button" value="Bytes" class="button"></label>
				<label accesskey="k"><input onclick="calc(1024)" type="button" value="kB" class="button"></label>
				<label accesskey="m"><input onclick="calc(1048576)" type="button" value="MB" class="button"></label>
				<label accesskey="g"><input onclick="calc(1073741824)" type="button" value="GB" class="button"></label>
	
				<label accesskey="g"><input onclick="calc(681574400)" type="button" value="CD" class="button"></label>
				<label accesskey="g"><input onclick="calc(5046586570)" type="button" value="DVD" class="button"></label>
			</div>
		</fieldset>
		</form>
	
	</div>

	<div id="result">

<h3>Transfer times</h3>

<table border="0" cellpadding="5" cellspacing="0" width="390">
<caption> </caption>
<tr>
	<th width="60%">If you're sending it'll take</th>
	<th width="40%">or getting</th>
</tr>
<tr>
	<td align="center"><span id="real-bupload">----------</span></td>
	<td align="center"><span id="real-bdownload">----------</span></td>
</tr>
</table>
<p class="hint">This is a pratical approximation based on the remainder of the bandwidth currently available and the transfer running at half-speed.</p>

<!--
<table border="0" cellpadding="5" cellspacing="0" width="390">
<caption>The fastest time possible...</caption>
<tr>
	<th width="60%">... sending it'll take</th>
	<th width="40%">... getting it'll take</th>
</tr>
<tr>
	<td align="center"><span id="result-download">----------</span></td>
	<td align="center"><span id="result-upload">----------</span></td>
</tr>
</table>
<p class="hint">This assumes nobody else is using the Office's bandband. Most online bandwidth calculators will give you this mathematically perfect but slightly impratical figure.</p>
-->
</div>


	</div>
</div>

</body>
</html>

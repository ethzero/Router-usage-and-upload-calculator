<?php
/*
PA: This is a massive pile of yuck, but it works

*/

header('Content-type: text/javascript');


?>
<?php
//This is the only variable you might need to change.
$dir = "/var/www/mrtg";

$logfile = ($_REQUEST['log']) ? $_REQUEST['log'] : 'router_10.log'; 
$logfile = $dir .'/'. $logfile;
$callback = ($_GET['callback']) ? $_GET['callback'] : 'callback';

$extime = gmDate("D") . "," . gmDate("d M Y H:i:s") . "GMT";

if (stristr($logfile,"log") <> "" ) {
	$handle = fopen($logfile, "r");
	if ($handle) {
		$buffer = fgetcsv($handle, 4096, ' '); // Eat the first line...
		$buffer = fgetcsv($handle, 4096, ' '); // ... the latest rates are what we want

		$rate['timestamp']		= $buffer[0];

/* These are tagged as "average" but are in fact sampled every 60secs,
   so only the MRTG graphs will show the actual 5min average from 5 samples.
   This can therefore be interpreted more as "current" speed
*/
		$rate['average_in'] 	= (($buffer[1]*8));
		$rate['average_out']	= (($buffer[2]*8));

		$rate['max_in']				= (($buffer[3]*8));
		$rate['max_out']			= (($buffer[4]*8));

    fclose($handle);
	} else {
		echo "No handle?";
	}
}

$json = json_encode($rate);
echo $callback . '(' . $json . ');';

/*
	Max	Average	Current
	In:		19.0 Mb/s (53.9%)		1243.7 kb/s (3.5%)	655.6 kb/s (1.9%)
	Out:	3220.1 kb/s (97.7%)	359.1 kb/s (10.9%)	3082.3 kb/s (93.5%)
*/

?>

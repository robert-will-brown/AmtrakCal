<?php
/*
  rob.brown@ioko.com - Sat Mar 26 2010

  Version $ver 

  This scripts purpose is to take two arguments which are Amtrak stations, and 
  then lookup on the Amtrak website the next trains between the origin and 
  destination.  When it has that data it outputs in ical format so it can be 
  read into a calendaring application rendering a dynamically updating "next
  train leaving" type thing.  Wicked.

  By hitting this script with a webbrowser and no arguments you will get
  a detailed description of how to use this script.

*/

$ver = "1.7";
date_default_timezone_set('America/Los_Angeles');
$thisScriptName =  "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

// Check that the file with the station full names exists and source it.
$stations_file = './amtrak-stations.dat';
if (file_exists($stations_file)) {
	include("./amtrak-stations.dat");
} else {
	echo "The station file $stations_file cannot be found.";
	exit;
}

$trainASCII = "
      __        __________
     /  \         ========   _____________
      ||          =      =  / robert.will]
  ___==============      = /   .brown@   ]
  \_[            ========= [  gmail.com  ]
    [=====================^==============
___//_(_)_(_)_(_)___\__/_____(_)_(_)_(_)
========================================

choo choo - Rob Brown 2010...";

// The error text to be printed when the correct arguments have not specified.
$errorText = "<strong>ERROR</strong>
	<p>
	  This script has not been called with the correct arguments.  You need to set a 
	  destination and origin station in order to generate a usable calendar.
	  This example shows all the trains from San Diego to Solana Beach:
	</p>
	<a href=\"$thisScriptName?origin=SAN&destination=SOL\">
      $thisScriptName?origin=SAN&destination=SOL</a>
	</p>
	<p>
	  A list of the three letter station name abreviations can be found 
	  <a href=\"http://en.wikipedia.org/wiki/List_of_Amtrak_stations#A\">here</a>.
	</p>
	<pre>" . $trainASCII . "</pre>";


/* Check we have the origin and destinations arguments passed to us. */
if (isset($_GET["origin"],$_GET["destination"]))
{
//	header('Content-type: text/plain');
	header('Content-type: text/Calendar');

/*
  Read in the station Origin and Destinations.
  Once read in they will be of the short three letter abbreviated 
  station name.  We then open an external file that has an array
  of the stations short names and it's long equivilent and convert
  the names.
*/
	$origin = $_GET["origin"];
	$destination = $_GET["destination"];

	$scodeOrigin = urldecode($origin);
	$scodeDestination = urldecode($destination);


	$niceOrigin = $station[$scodeOrigin];
	$niceDestination = $station[$scodeDestination];

}else{
	header('Content-type: text/html');

	echo $errorText;
	exit;
}

/*
  This gets todays date into the format needed for the post to the Amtrack 
  website.
*/
$date = date("D%2C+M+d%2C+Y");
$url = "http://tickets.amtrak.com/itd/amtrak";

$ch = curl_init();									// initialize curl handle

curl_setopt($ch, CURLOPT_URL,$url);				// set url to post to
curl_setopt($ch, CURLOPT_FAILONERROR, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);	// allow redirects
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);	// return into a variable
curl_setopt($ch, CURLOPT_TIMEOUT, 30);			// times out after 30s
curl_setopt($ch, CURLOPT_POST, 1);				// set POST method
curl_setopt($ch, CURLOPT_POSTFIELDS, "requestor=amtrak.presentation.handler.page.rail.AmtrakRailFareFinderPageHandler&xwdf_origin=%2FsessionWorkflow%2FproductWorkflow%5B@product%3D%27Rail%27%5D%2FtravelSelection%2FjourneySelection%5B1%5D%2FdepartLocation%2Fsearch&wdf_origin=" . "$origin" . "&xwdf_destination=%2FsessionWorkflow%2FproductWorkflow%5B@product%3D%27Rail%27%5D%2FtravelSelection%2FjourneySelection%5B1%5D%2FarriveLocation%2Fsearch&wdf_destination=" . "$destination" . "&%2FsessionWorkflow%2FproductWorkflow%5B@product%3D%27Rail%27%5D%2FtripRequirements%2FjourneyRequirements%5B1%5D%2FdepartDate.date=" . "$date" . "&%2FsessionWorkflow%2FproductWorkflow%5B@product%3D%27Rail%27%5D%2FtripRequirements%2FjourneyRequirements%5B1%5D%2FdepartTime.hourmin=&_handler%3Damtrak.presentation.handler.request.rail.AmtrakCMSRailSchedulesSearchRequestHandler%2F_xpath%3D%2FsessionWorkflow%2FproductWorkflow%5B@product%3D%27Rail%27%5D=&_handler%3Damtrak.presentation.handler.request.rail.AmtrakCMSRailSchedulesSearchRequestHandler%2F_xpath%3D%2FsessionWorkflow%2FproductWorkflow%5B@product%3D%27Rail%27%5D.x=105&_handler%3Damtrak.presentation.handler.request.rail.AmtrakCMSRailSchedulesSearchRequestHandler%2F_xpath%3D%2FsessionWorkflow%2FproductWorkflow%5B@product%3D%27Rail%27%5D.y=9"); // add POST fields
$page = curl_exec($ch);							// run the whole process

/* Extract the table cells that we are interested in. */
preg_match_all('|<td class="depart">(.*?)</td>|s', $page, $departMatches);
preg_match_all('|<td class="arrive">(.*?)</td>|s', $page, $arriveMatches);
preg_match_all('|<td class="connect">(.*?)</td>|s', $page, $connectMatches);
preg_match_all('|<td class="duration">(.*?)</td>|s', $page, $durationMatches);
preg_match_all('|<td class="routes">(.*?)</td>|s', $page, $routesMatches);
preg_match_all('|<td class="trains">(.*?)</td>|s', $page, $trainsMatches);

/* 
  Count the amount of departures, which will give us the total journeys left
  today. 
*/
$entryCount = count($departMatches[1]);


/* 
  Output the calendar header using the origin and destination stations as the
  calendar name to make it easy to see what your looking at.
*/
echo "BEGIN:VCALENDAR
VERSION:2.0
PRODID:$_SERVER[PHP_SELF]-robert.will.brown@gmail.com
X-WR-CALNAME:$niceOrigin to $niceDestination
CALSCALE:GREGORIAN
METHOD:PUBLISH
TZID:PST
";

// Iterate through each Journey populating the variables from the array.
for($i=0;$i<$entryCount;$i++)
{
	$departTime = trim($departMatches[1][$i]);
	$arriveTime = trim($arriveMatches[1][$i]);
	$connection = strip_tags($connectMatches[1][$i]);
	$connection = trim($connection);
	$duration = trim($durationMatches[1][$i]);
	$trainNumber = trim($trainsMatches[1][$i]);

	$departTime24 = date("Hi", strtotime($departTime));
	$arriveTime24 = date("Hi", strtotime($arriveTime));

	$eventStartDate = date("Ymd\T" . $departTime24 . "00");
	$eventEndDate = date("Ymd\T" . $arriveTime24 . "00");

	$UID = "$eventStartDate$eventEndDate$trainNumber@" . $_SERVER['PHP_SELF'];

	echo "\nBEGIN:VEVENT\n";
	echo "TRANSP:TRANSPARENT\n";
	echo "DTSTAMP:$eventStartDate\n";
	echo "DTSTART:$eventStartDate\n";
	echo "DTEND:$eventEndDate\n";
	echo "UID:$UID\n";
	echo "LOCATION:from $niceOrigin train station\n";
	echo "SUMMARY:$niceOrigin to $niceDestination\n";
	echo "DESCRIPTION:Train $trainNumber from $niceOrigin to $niceDestination\rDuration - $duration\rArrives - $arriveTime.\r\r$url\rVersion: $ver\n";
	echo "END:VEVENT\n";
}

echo "\nEND:VCALENDAR\n";

curl_close($ch); 

?> 

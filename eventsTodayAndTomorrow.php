<?php



// Get config
$configFile = $argv[1];
if (!$configFile  || !file_exists($configFile)) {
	die("You must provide a config file!\n");
}

// load config file, and check
$config = parse_ini_file($configFile);
foreach(array('site_url','slack_incoming_webhook_url') as $var) {
	if (!isset($config[$var]) || !$config[$var]) {
		die("Missing config variable: ".$var."\n");
	}
}

// Get JSON URL
$url = $config['site_url'];
if (substr($url, -1) != '/') {
	$url .= '/';
}
$url .= 'api1';

if (isset($config['area_slug']) && $config['area_slug']) {
	$url .= '/area/'.$config['area_slug'];
} 

$url .= "/events.json";

// Get the Data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'OpenACalendar Slack');
$dataString = curl_exec($ch);
$response = curl_getinfo( $ch );
curl_close($ch);

if ($response['http_code'] != 200) {
	die("Not a 200 response from ".$url."\n");
}

$data = json_decode($dataString);

// which events to include?
$datetime = new DateTime("",new DateTimeZone("UTC"));
$datetime->setTime(0,0,0);
$todayStarts = $datetime->getTimestamp();
$datetime->setTime(23,59,59);
$todayEnds = $datetime->getTimestamp();
$datetime->add(new \DateInterval("P1D"));
$tomorrowEnds = $datetime->getTimestamp();
$datetime->setTime(0,0,0);
$tomorrowStarts = $datetime->getTimestamp();

$dataToIncludeToday = array();
$dataToIncludeTomorrow = array();
foreach($data->data as $event) {
	if (!$event->deleted) {
		// TODAY?
		$include = false;
		// starts today?
		if ($event->start->timestamp >= $todayStarts && $event->start->timestamp <= $todayEnds) {
			$include = true;
		}
		// ends today
		if ($event->end->timestamp >= $todayStarts && $event->end->timestamp <= $todayEnds) {
			$include = true;
		}
		// starts before today and ends after today - a ongoing event
		if ($event->start->timestamp <= $todayStarts && $event->end->timestamp >= $todayEnds) {
			$include = true;
		}
		if ($include) {
			$dataToIncludeToday[] = $event;
		} else {
			// TOMORROW?
			$include = false;
			// starts tomorrow?
			if ($event->start->timestamp >= $tomorrowStarts && $event->start->timestamp <= $tomorrowEnds) {
				$include = true;
			}
			// ends tomorrow
			if ($event->end->timestamp >= $tomorrowStarts && $event->end->timestamp <= $tomorrowEnds) {
				$include = true;
			}
			// starts before tomorrow and ends after tomorrow - a ongoing event
			if ($event->start->timestamp <= $tomorrowStarts && $event->end->timestamp >= $tomorrowEnds) {
				$include = true;
			}
			if ($include) {
				$dataToIncludeTomorrow[] = $event;
			}
		}
	}

}


// Anything?
if (!$dataToIncludeToday && !$dataToIncludeTomorrow) {
	die("No Data In Include\n");
}

// Build message
$message = '';
if ($dataToIncludeToday) {
	$message = "On today:\n";
	foreach($dataToIncludeToday as $event) {
		$message .= "<".$event->siteurl."|".$event->summaryDisplay.">\n";
	}
}
if ($dataToIncludeTomorrow) {
	$message = "On tomorrow:\n";
	foreach($dataToIncludeTomorrow as $event) {
		$message .= "<".$event->siteurl."|".$event->summaryDisplay.">\n";
	}
}

// Post to Slack!
$post = array('text'=>$message);
if (isset($config['slack_channel']) && $config['slack_channel']) {
	$post['channel'] = $config['slack_channel'];
}
if (isset($config['slack_username']) && $config['slack_username']) {
	$post['username'] = $config['slack_username'];
}
if (isset($config['slack_icon_url']) && $config['slack_icon_url']) {
	$post['icon_url'] = $config['slack_icon_url'];
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config['slack_incoming_webhook_url']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'OpenACalendar Slack');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch,  CURLOPT_POSTFIELDS, array('payload' => json_encode($post)));
$pastResponse = curl_exec($ch);
$postInfo = curl_getinfo( $ch );
curl_close($ch);

var_dump($postResponse);
var_dump($postInfo);

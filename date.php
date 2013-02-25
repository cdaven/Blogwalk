<?

$sv_weekdays = array("söndag", "måndag", "tisdag", "onsdag", "torsdag", "fredag", "lördag");
$sv_months = array("januari", "februari", "mars", "april", "maj", "juni", "juli", "augusti", "september", "oktober", "november", "december");

function get_real_date($date)
{
	global $sv_weekdays, $sv_months;

	if(!ctype_digit($date))
		$date = strtotime($date);

	$then = getdate($date);

	$year = $then["year"];
	$month = $sv_months[$then["mon"] - 1];
	$day = $then["mday"];
	$wday = $sv_weekdays[$then["wday"]];
	$hour = $then["hours"];
	if($hour < 10)
		$hour = "0$hour";
	$minute = $then["minutes"];
	if($minute < 10)
		$minute = "0$minute";

	return "$wday $day $month $hour:$minute";
}

function get_short_date($date)
{
	global $sv_weekdays, $sv_months;

	if(!ctype_digit($date))
		$date = strtotime($date);

	$then = getdate($date);

	$month = $sv_months[$then["mon"] - 1];
	$day = $then["mday"];
	$wday = $sv_weekdays[$then["wday"]];
	$hour = $then["hours"];
	if($hour < 10)
		$hour = "0$hour";
	$minute = $then["minutes"];
	if($minute < 10)
		$minute = "0$minute";

	return "$wday $hour:$minute";
}

function get_nice_date($date)
{
	global $sv_weekdays, $sv_months;

	if(!ctype_digit($date))
		$date = strtotime($date);

	$then = getdate($date);
	$today = getdate();

	$year = $then["year"];
	$month = $sv_months[$then["mon"] - 1];
	$day = $then["mday"];
	$wday = $sv_weekdays[$then["wday"]];
	$hour = $then["hours"];
	if($hour < 10)
		$hour = "0$hour";
	$minute = $then["minutes"];
	if($minute < 10)
		$minute = "0$minute";

	$weekthen = strftime("%Y%V", $date);
	$weeknow = strftime("%Y%V");

	$timediff = (time() - $date) / 60.0;

	if($timediff < 1)
		$date = "för en minut sedan";
	elseif($timediff < 4)
		$date = "för några minuter sedan";
	elseif($timediff >= 4 and $timediff < 6)
		$date = "för fem minuter sedan";
	elseif($timediff >= 6 and $timediff < 12)
		$date = "för tio minuter sedan";
	elseif($timediff >= 12 and $timediff < 17)
		$date = "för en kvart sedan";
	elseif($timediff >= 17 and $timediff < 22)
		$date = "för 20 minuter sedan";
	elseif($timediff >= 22 and $timediff < 40)
		$date = "för en halvtimme sedan";
	elseif($timediff >= 40 and $timediff < 50)
		$date = "för knappt en timme sedan";
	elseif($timediff >= 50 and $timediff < 70)
		$date = "för en timme sedan";
	elseif($timediff < 60 * 48 and $then["wday"] == $today["wday"])
		$date = "idag $hour:$minute";
	elseif($timediff < 60 * 48 and ($then["wday"] + 1) % 7 == $today["wday"])
		$date = "igår $hour:$minute";
	elseif($timediff < 60 * 72 and ($then["wday"] + 2) % 7 == $today["wday"])
		$date = "i förrgår $hour:$minute";
	elseif($timediff < 60 * 24 * 7 and $then["wday"] != $today["wday"])
		$date = "i {$wday}s $hour:$minute";
	elseif($weekthen + 1 == $weeknow)
		$date = "i {$wday}s förra veckan";
	else
		$date = "$day $month $year";

	return $date;
}

function getFirstMondayBeforeDate($time)
{
	$date = getdate($time);
	$days = $date["wday"] - 1;
	if($days == -1) $days = 6;
	$time -= 3600 * 24 * $days;
	return $time;
}

function gotoWeek($year, $week)
{
	$date = getdate(time());
	$time = strtotime("$year-$date[mon]-$date[mday] 00:00");

	$date = getdate($time);
	$wk = strftime("%V", $time);

	$time += ($week - $wk) * 3600 * 24 * 7;
	return $time;
}

function getThisWeek()
{
	return strftime("%V", time());
}

function getWeek($time)
{
	return strftime("%V", $time);
}

function getYear($time)
{
	return strftime("%Y", $time);
}

function getWeekday($time)
{
	return strftime("%u", $time);
}

function getMonth($time)
{
	return strftime("%m", $time);
}

function getDateStr($time)
{
	return strftime("%Y%m%d", $time);
}

function getLastWeekPlusYear()
{
	$time = time() - 3600 * 24 * 7;
	$week = strftime("%V", $time);
	$year = strftime("%Y", $time);
	return array($week, $year);
}

function setTimeToMidnight($time)
{
	$date = getdate($time);
	return strtotime("$date[year]-$date[mon]-$date[mday] 00:00");
}

?>

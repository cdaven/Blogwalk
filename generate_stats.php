<?

require_once("utf8.php");
require_once("string.php");
require_once("db.php");
require_once("date.php");
require_once("counter.php");
require_once("sparkline/Sparkline_Line.php");
require_once("sparkline/Sparkline_Bar.php");

# KOMPILERAR LIVE-ZEITGEIST

$cs = new CounterStatistics(time() - 3600 * 24 * 7, time());
$searches = $cs->merge_list($cs->data["search_queries"]);
save_data(array_slice($searches, 0, 50), "/home/davense/aggregator/searches/live");

# KOMPILERAR STATISTIK

function generate_sparkline($data, $file)
{
	$sparkline = new Sparkline_Line();
	$sparkline->SetDebugLevel(DEBUG_NONE);

	$i = 0;
	$min = null;
	$max = null;

	foreach($data as $y)
	{
		$sparkline->SetData($i, $y);

		if($max == null or $y >= $max[1])
			$max = array($i, $y);
		if($min == null or $y <= $min[1])
			$min = array($i, $y);

		$i++;
	}

	$sparkline->SetYMin(0);
	$sparkline->SetPadding(1); // setpadding is additive
	$sparkline->SetPadding(imagefontheight(FONT_2), 0, 0, 0);

	$sparkline->SetFeaturePoint($min[0], $min[1], 'red',   5, $min[1], TEXT_TOP, FONT_2);
	$sparkline->SetFeaturePoint($max[0], $max[1], 'green', 5, $max[1], TEXT_TOP, FONT_2);

	$sparkline->Render(500, 60);
	$sparkline->OutputToFile($file);
}

function generate_sparkline_bar($data, $file, $weekdays = false)
{
	$sparkline = new Sparkline_Bar();
	$sparkline->SetDebugLevel(DEBUG_NONE);
	#$sparkline->SetDebugLevel(DEBUG_ERROR | DEBUG_WARNING | DEBUG_STATS | DEBUG_CALLS, 'log.txt');

	$sparkline->SetBarWidth(6);
	$sparkline->SetBarSpacing(3);

	foreach($data as $x => $y)
	{
		$color = "black";
		if($weekdays and ($x == 6 or $x == 7))
			$color = "red";

		$sparkline->SetData($x, $y, $color);
	}

	$sparkline->SetYMin(0);
	$sparkline->SetPadding(1); // setpadding is additive

	$sparkline->Render(40); // height only for Sparkline_Bar
	$sparkline->OutputToFile($file);
}

# RÄKNAR INLÄGG PER DAG (EXKL. IDAG)

$numposts = array();
$weekdays = array();
$weeks = array();
$holiday = array();
$normalday = array();
$minimum = array("count" => 1000);
$maximum = array("count" => 0);

$holidays = array("20050505", "20050506", "20050606", "20050624", "20051224", "20051225", "20051226", "20051231");

# börjar med den första hela dagen i databasen
$day = strtotime("2005-04-23 00:00:00");
$today = setTimeToMidnight(time());
do
{
	$nextday = $day + 3600 * 24;

	$mysql_day = strftime("%Y%m%d%H%M%S", $day);
	$mysql_nextday = strftime("%Y%m%d%H%M%S", $nextday);

	$row = mysql_fetch_row(query("SELECT COUNT(*) FROM post WHERE time >= $mysql_day AND time < $mysql_nextday"));
	$num = $row[0];
	array_push($numposts, $num);

	$weekdays[getWeekday($day)] += $num;
	$weeks[getWeek($day)] += $num;

	# sparar helgdagar separat
	$date = getDateStr($day);
	if(in_array($date, $holidays) or getWeekday($day) == 6 or getWeekday($day) == 7)
		array_push($holiday, $num);
	else
		array_push($normalday, $num);

	# kommer ihåg datum för maximum och minimum
	if($num >= $maximum["count"])
	{
		$maximum["count"] = $num;
		$maximum["date"] = $day;
	}
	if($num <= $minimum["count"])
	{
		$minimum["count"] = $num;
		$minimum["date"] = $day;
	}

	# går vidare till nästa dag
	$day = $nextday;
}
while($day < $today);

# GENERERAR PNG-GRAFER

generate_sparkline($numposts, "/home/davense/public_html/blogwalk/images/sparkline.png");

ksort($weekdays);
generate_sparkline_bar($weekdays, "/home/davense/public_html/blogwalk/images/weekdays.png", true);

ksort($weeks);
$weeks = array_slice($weeks, count($weeks) - 11, 10);
generate_sparkline_bar($weeks, "/home/davense/public_html/blogwalk/images/weeks.png");

$stats = array();

$stats["total"] = array("count" => array_sum($numposts), "days" => count($numposts));
$stats["60"] = array_sum(array_slice($numposts, $days - 60));
$stats["30"] = array_sum(array_slice($numposts, $days - 30));
$stats["7"] = array_sum(array_slice($numposts, $days - 7));

$stats["days"] = $weekdays;
$stats["weeks"] = $weeks;
$stats["holiday"] = $holiday;
$stats["normalday"] = $normalday;

$stats["minimum"] = $minimum;
$stats["maximum"] = $maximum;

$blogs = array();
$result = query("SELECT `index` FROM blog");
while($row = mysql_fetch_row($result))
{
	$blog = get_blog($row[0]);
	$post = get_first_post($row[0]);

	array_push($blogs, array("count" => $blog["postcount"], "first" => strtotime($post["time"])));
}

# RÄKNAR LITE MER ...

$ones = 0;
$ppd = array();
$ppd_table = array("0&ndash;2" => 0, "2&ndash;4" => 0, "4&ndash;6" => 0, "6&ndash;8" => 0, "8&ndash;10" => 0, "10&ndash;" => 0);
foreach($blogs as $x)
{
	if($x["count"] == 1 and $x["first"] < time() - 3600 * 24 * 30)
		$ones++;

	# ignorerar bloggar med bara ett inlägg
	if($x["count"] == 1)
		continue;

	$val = 3600 * 24 * 7 * $x["count"] / (time() - $x["first"]);

	if($val < 2)
		$ppd_table["0&ndash;2"]++;
	elseif($val < 4)
		$ppd_table["2&ndash;4"]++;
	elseif($val < 6)
		$ppd_table["4&ndash;6"]++;
	elseif($val < 8)
		$ppd_table["6&ndash;8"]++;
	elseif($val < 10)
		$ppd_table["8&ndash;10"]++;
	else
		$ppd_table["10&ndash;"]++;

	array_push($ppd, $val);
	$n++;
}

# väntevärde
$E = array_sum($ppd) / $n;
# varians
foreach($ppd as $x)
	$V += pow($x - $E, 2);
$V /= $n;

$min = $E - 2 * sqrt($V / $n);
$max = $E + 2 * sqrt($V / $n);

$stats["blogs"] = array("E" => $E, "V" => $V, "n" => $n, "min" => $min, "max" => $max, "ppd_table" => $ppd_table, "ones" => $ones);

$row = mysql_fetch_row(query("SELECT COUNT(*) FROM blog"));
$stats["blogcount"] = $row[0];

$row = mysql_fetch_row(query("SELECT COUNT(*) FROM post"));
$stats["postcount"] = $row[0];

save_data($stats, "cache/stats");

?>

<?

require_once("/home/davense/aggregator/db.php");
require_once("/home/davense/aggregator/keywords.php");
require_once("/home/davense/aggregator/utf8.php");
require_once("/home/davense/aggregator/string.php");
require_once("/home/davense/aggregator/date.php");
require_once("/home/davense/aggregator/html.php");
require_once("/home/davense/aggregator/counter.php");
require_once("/home/davense/aggregator/harvest_links.php");

function set_cluster_if_none($index, $score, $cluster)
{
	global $clusters;
	if($clusters[$index] == "" and $score > 12)
		$clusters[$index] = $cluster;
}

list($week, $year) = getLastWeekPlusYear();

if(isset($_GET["week"]))
	$week = $_GET["week"];

# tittar på ett visst tidsintervall
$time_begin = getFirstMondayBeforeDate(gotoWeek($year, $week));
$time_end = $time_begin + 3600 * 24 * 7;

# -------- genererar lista över veckans mest populära sökningar
$cs = new CounterStatistics($time_begin, $time_end);
$searches = $cs->merge_list($cs->data["search_queries"]);
save_data(array_slice($searches, 0, 50), "/home/davense/aggregator/searches/$year$week");
# --------

$time_begin = strftime("%Y%m%d%H%M%S", $time_begin);
$time_end = strftime("%Y%m%d%H%M%S", $time_end);

# -------- skördar länkar och sammanställer lista
harvest($time_begin, $time_end, "$year$week");
# --------

$result = query(
	"SELECT `index`,keywords,blog,title,summary
	FROM post
	WHERE
		time >= $time_begin AND time < $time_end
		AND keywords != 'X' AND keywords != ''");

$posts = array();
while($row = fetch_array($result))
	$posts[$row["index"]] = $row;

$clusters = array();
foreach($posts as $index => $post)
{
	# vissa inlägg sätts när de hittas av annat inlägg
	if($clusters[$index] != "") continue;

	$kw = $post["keywords"];
	prepare_string($kw);

	# använder mySQL:s fulltext-sökning för att hitta liknande inlägg
	# (inlägg från samma blogg räknas inte)
	$result = query(
		"SELECT `index`,MATCH (title,summary) AGAINST ('$kw') AS score,title,summary
		FROM post
		WHERE
			MATCH (title,summary) AGAINST ('$kw')
			AND time >= $time_begin AND time < $time_end
			AND blog != $post[blog]
			HAVING score > 9
			LIMIT 3");

	if(mysql_num_rows($result) > 0)
	{
		#echo "<h2>$post[title]</h2><p>$post[summary]</p>";
		$neighbours = array();
		$count = 0;
		while($row = fetch_array($result))
		{
			$neighbours[$count]["cluster"] = $clusters[$row["index"]];
			$neighbours[$count]["score"] = $row["score"];
			$neighbours[$count]["index"] = $row["index"];
			$count++;

			#echo "<p><strong>$row[title] ($row[score]):</strong> $row[summary]</p>";
		}

		# använder ett slags "k nearest neighbour"-algoritm för att para ihop inlägg i kluster

		# ingen tillhör något kluster ännu
		if($neighbours[0]["cluster"] == "" and $neighbours[1]["cluster"] == "" and $neighbours[2]["cluster"] == "")
		{
			$cid = md5($post["summary"]);
			$clusters[$index] = $cid;

			# vid höga poäng sätter vi också de närmaste inläggens klustertillhörighet
			set_cluster_if_none($neighbours[0]["index"], $neighbours[0]["score"], $cid);
			set_cluster_if_none($neighbours[1]["index"], $neighbours[1]["score"], $cid);
			set_cluster_if_none($neighbours[2]["index"], $neighbours[2]["score"], $cid);
		}
		# endast 0 tillhör ett kluster
		elseif($neighbours[0]["cluster"] != "" and $neighbours[1]["cluster"] == "" and $neighbours[2]["cluster"] == "")
		{
			$cid = $neighbours[0]["cluster"];
			$clusters[$index] = $cid;

			# vid höga poäng sätter vi också de närmaste inläggens klustertillhörighet
			set_cluster_if_none($neighbours[1]["index"], $neighbours[1]["score"], $cid);
			set_cluster_if_none($neighbours[2]["index"], $neighbours[2]["score"], $cid);
		}
		# endast 1 tillhör ett kluster
		elseif($neighbours[1]["cluster"] != "" and $neighbours[0]["cluster"] == "" and $neighbours[2]["cluster"] == "")
		{
			$cid = $neighbours[1]["cluster"];
			$clusters[$index] = $cid;

			# vid höga poäng sätter vi också de närmaste inläggens klustertillhörighet
			set_cluster_if_none($neighbours[0]["index"], $neighbours[0]["score"], $cid);
			set_cluster_if_none($neighbours[2]["index"], $neighbours[2]["score"], $cid);
		}
		# endast 2 tillhör ett kluster
		elseif($neighbours[2]["cluster"] != "" and $neighbours[0]["cluster"] == "" and $neighbours[1]["cluster"] == "")
		{
			$cid = $neighbours[2]["cluster"];
			$clusters[$index] = $cid;

			# vid höga poäng sätter vi också de närmaste inläggens klustertillhörighet
			set_cluster_if_none($neighbours[0]["index"], $neighbours[0]["score"], $cid);
			set_cluster_if_none($neighbours[1]["index"], $neighbours[1]["score"], $cid);
		}
		# majoritet 0+1
		elseif($neighbours[0]["cluster"] == $neighbours[1]["cluster"] or $neighbours[0]["cluster"] == $neighbours[2])
		{
			$cid = $neighbours[0]["cluster"];
			$clusters[$index] = $cid;

			# vid höga poäng sätter vi också de närmaste inläggens klustertillhörighet
			set_cluster_if_none($neighbours[2]["index"], $neighbours[2]["score"], $cid);
		}
		# majoritet 0+2
		elseif($neighbours[0]["cluster"] == $neighbours[1]["cluster"] or $neighbours[0]["cluster"] == $neighbours[2])
		{
			$cid = $neighbours[0]["cluster"];
			$clusters[$index] = $cid;

			# vid höga poäng sätter vi också de närmaste inläggens klustertillhörighet
			set_cluster_if_none($neighbours[1]["index"], $neighbours[1]["score"], $cid);
		}
		# majoritet 1+2
		elseif($neighbours[1]["cluster"] == $neighbours[2]["cluster"])
		{
			$cid = $neighbours[1]["cluster"];
			$clusters[$index] = $cid;

			# vid höga poäng sätter vi också de närmaste inläggens klustertillhörighet
			set_cluster_if_none($neighbours[0]["index"], $neighbours[0]["score"], $cid);
		}
		# alla tre olika, antar att det inte finns någon likhet
		else
		{
			$clusters[$index] = md5($post["summary"]);
		}
	}
}

# plockar ut alla kluster som består av fler än ett inlägg
$okclusters = array();
foreach(@array_count_values($clusters) as $id => $count)
{
	if($count == 1) continue;

	$currentcluster = array();
	foreach($clusters as $index => $hash)
		if($id == $hash)
			array_push($currentcluster, $index);

	array_push($okclusters, $currentcluster);
}

# skapar "läns-moln"
$l = new LocationHtml();
save_data($l->generate_cloud(), "cache/locationcloud");

# sparar kluster till fil för senare manuellt godkännande
save_data($okclusters, "/home/davense/aggregator/clusters/$year$week");

echo "kluster skapat. moderera och generera från
	http://www.daven.se/blogwalk/index.php?clusters&week=$year$week";

require_once("fetch_icons.php");
require_once("analyse_tools.php");
require_once("fetch_bookmarks.php");

?>

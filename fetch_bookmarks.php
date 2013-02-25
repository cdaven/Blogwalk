<?

require_once("db.php");
require_once("utf8.php");
require_once("string.php");
require_once("date.php");
require_once("xmlparse.php");

function _cmp($a, $b) 
{
    if ($a["date"] == $b["date"])
        return 0;

    return ($a["date"] > $b["date"]) ? -1 : 1;
}

function fetch($count)
{
	ini_set("user_agent", "BlogwalkBot/1.0 (+http://www.blogwalk.se/about/bot)");

	$tags = get_most_used_tags(100);
	$tagnames = array_keys($tags);

	foreach($tagnames as $tag)
	{
		$tag = urlencode($tag);

		echo "<p>fetching $tag</p>";

		# hämtar del.icio.us-länkar

		$xmldata = @file_get_contents("http://del.icio.us/rss/tag/$tag");
		if($xmldata === false)
		{
			echo "! inget svar från del.icio.us/rss/tag/$tag\n";
			continue;
		}
		$dlinks = parse_rdf($xmldata);

		# hämtar furl-länkar

		$xmldata = @file_get_contents("http://www.furl.net/members/rss.xml?topic=$tag");
		if($xmldata === false)
		{
			echo "! inget svar från furl.net/members/rss.xml?topic=$tag\n";
			continue;
		}
		$flinks = parse_rss($xmldata);

		# lägger ihop länkarna (utgår från att det finns fler på del.icio.us än på furl)

		$flinks = array_slice($flinks, 0, $count / 2);
		$links = array_merge(array_slice($dlinks, 0, $count - count($flinks)), $flinks);
		if(count($links) > 0)
		{
			usort($links, "_cmp");
			save_data($links, "cache/{$tag}_links");
		}
	}

	return $links;
}

$links = fetch(6);

?>

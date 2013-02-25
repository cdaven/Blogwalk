<?

require_once("db.php");
require_once("utf8.php");
require_once("string.php");
require_once("date.php");

$suffixes = array("/index.html", "/index.htm", "/index.shtml", "/index.shtm", "/index.php", "/index.php3", "/index.php4", "/index.py", "/index.asp", "/default.asp", "/main.aspx", "/index.jsp");

function normalize($url)
{
	global $suffixes;

	# tar bort "http://"
	$url = substr($url, 7);
	if(string_startswith($url, "www."))
		$url = substr($url, 4);

	foreach($suffixes as $suffix)
		if(string_endswith(strtolower($url), $suffix))
			return remove_suffix($url, $suffix);

	return $url;
}

function remove_suffix($word, $suffix)
{
	$end = strlen($word) - strlen($suffix);
	return substr($word, 0, $end);
}

function extract_unique_links($text)
{
	$matches = array();
	preg_match_all("/(<|&lt;)a[^>]+href=('http:\/\/([^>]+)'|\"http:\/\/([^>]+)\")/Usi", $text, &$matches);

	$urls = array();
	foreach($matches[2] as $url)
	{
		$url = trim(trim($url, "'\"/"));
		# Blogger har en egen länk i alla flöden
		if($url == "http://help.blogger.com/bin/answer.py?answer=697") continue;
		array_push($urls, normalize($url));
	}

	return array_unique($urls);
}

function extract_title($text)
{
	# tittar endast på strängen fram till första inlägget
	$pos = strpos($text, "<item");
	if($pos === false)
		$pos = strpos($text, "<entry");

	if($pos !== false)
		$text = substr($text, 0, $pos);

	$matches = array();
	if(preg_match("/<title( [^>]+)?>(<!\[CDATA\[)??(.*)(\]\]>)??<\/title>/Usi", $text, &$matches) == 1)
		return $matches[3];

	return "";
}

function harvest($time_begin, $time_end, $filename)
{
	ini_set("user_agent", "BlogwalkBot/1.0 (+http://www.blogwalk.se/about/bot)");

	# ta reda på vilka flöden som har uppdaterats den gångna veckan

	$result = query(
		"SELECT DISTINCT blog.`index`,name,feedurl
		FROM post,blog
		WHERE
			post.blog = blog.`index`
			AND time >= $time_begin AND time < $time_end");

	# hämta alla flöden och plocka ut unika länkar ur varje

	$links = array();
	while($row = fetch_array($result))
	{
		$xmldata = @file_get_contents($row["feedurl"]);
		if($xmldata === false)
		{
			echo "! inget svar från $row[name]\n";
			continue;
		}

		# när vi ändå läser flöden ... hämtar och lagrar bloggtiteln
		$title = extract_title($xmldata);
		if($title != "")
		{
			prepare_string($title);
			query("UPDATE blog SET feedtitle='$title' WHERE `index`=$row[index]");
		}

		foreach(extract_unique_links($xmldata) as $url)
			$links[$url] += 1;
	}

	# hämta titel på de mest populära sidorna

	arsort($links);

	$toplinks = array();
	foreach($links as $url => $hits)
	{
		if($hits <= 2) break;

		$title = $url;
		$htmldata = @file_get_contents("http://$url");
		if($htmldata !== false)
		{
			$matches = array();
			if(preg_match("/<title>(.*)<\/title>/Uis", $htmldata, &$matches) == 1)
				$title = trim($matches[1]);
		}

		array_push($toplinks, array("title" => $title, "url" => $url, "hits" => $hits));
	}

	save_data($toplinks, "/home/davense/aggregator/links/$filename");
}

?>

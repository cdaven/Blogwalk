<?

require_once("db.php");
require_once("utf8.php");
require_once("string.php");
require_once("date.php");

function _http_get_nontext($f, $file, $host)
{
	$firstline = true;
	$header = true;
	$redirect = false;

	fputs($f, "GET $file HTTP/1.0\r\nHost: $host\r\n\r\n");
	while(!feof($f))
	{
		$thisline = fgets($f, 65536);
		$data .= $thisline;

		if($firstline and preg_match("/HTTP\/1\.\d (4|5)\d\d .+\r\n/", $thisline) == 1)
			return false;
		elseif($firstline and preg_match("/HTTP\/1\.\d 3\d\d .+\r\n/", $thisline) == 1)
			$redirect = true;
		elseif($redirect and preg_match("/Location: (.+)\r\n/", $thisline, $matches) == 1)
		{
			if(string_startswith($matches[1], "http://"))
				return download_file($matches[1]);
			else
				return _http_get_nontext($f, $matches[1], $host);
		}
		elseif($header and string_startswith($thisline, "Content-Type: text/html"))
			return false;
		elseif($header and $thisline == "\r\n")
			$header = false;
		elseif(!$header)
			$content .= $thisline;

		$firstline = false;
	}

	return $content;
}

function download_file($url)
{
	$host = get_full_server_from_url($url);
	$file = get_relative_url($url);

	if($f = @fsockopen($host, 80))
	{
		$content = _http_get_nontext($f, $file, $host);
		fclose($f);
		return $content;
	}

	return false;
}

function get_icon($url, $blog)
{
	$icon = download_file("http://$url");
	if($icon !== false)
	{
		$fh = fopen("/home/davense/public_html/blogwalk/icons/$blog.ico", "w");
		if(!$fh) return false;
		fwrite($fh, $icon);
		fclose($fh);
		return true;
	}
	return false;
}

function find_blog_icon($url, $blog)
{
	$url = substr(rtrim(strip_filename_from_url($url), "/"), 7);
	if(get_icon("$url/favicon.ico", $blog))
		return;

	$pos = strrpos($url, "/");
	if($pos !== false)
	{
		$url = substr($url, 0, $pos);
		get_icon("$url/favicon.ico", $blog);
	}

	$pos = strrpos($url, "/");
	if($pos !== false)
	{
		$url = substr($url, 0, $pos);
		get_icon("$url/favicon.ico", $blog);
	}
}

ini_set("user_agent", "BlogwalkBot/1.0 (+http://www.blogwalk.se/about/bot)");

$result = query("SELECT `index`,url,name FROM blog");
while($row = mysql_fetch_array($result))
{
	$domain = get_server_from_url($row["url"]);
	foreach($hostings as $host)
		if(string_endswith($domain, $host))
		{
			$tool = $host;
			break;
		}

	if(isset($tool) and !is_readable("/home/davense/public_html/blogwalk/icons/$tool.ico"))
		find_blog_icon($row["url"], $tool);
	else
		find_blog_icon($row["url"], $row["index"]);
}

?>

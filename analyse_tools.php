<?

require_once("db.php");
require_once("utf8.php");
require_once("string.php");
require_once("date.php");

function extract_generator($html)
{
	# <meta name="generator" content="xxx"
	if(preg_match("/<meta\s+name=(['\"])generator\\1\s+content=\\1(.+)\\1/Ui", $html, $matches) == 1)
		return $matches[2];

	# <meta name="powered-by" content="xxx"
	if(preg_match("/<meta\s+name=(['\"])powered-by\\1\s+content=\\1(.+)\\1/Ui", $html, $matches) == 1)
		return $matches[2];

	# powered by xxx
	if(preg_match("/powered by\s+([^\]\)\"'<]+)/i", $html, $matches) == 1)
		return $matches[1];

	# powered by <a href="">xxx</a>
	if(preg_match("/powered by.+?<a [^>]+>\s*(.+?)\s*<\/a>/i", $html, $matches) == 1)
		return strip_tags($matches[1]);

	# "/mt.cgi"
	if(preg_match("/(['\"]).*\/mt(.+)?.cgi.*\\1/Ui", $html) == 1)
		return "Movable type";

	# "/htsrv/"
	if(preg_match("/(['\"]).*\/htsrv\/.*\\1/Ui", $html) == 1)
		return "b2evolution";

	# "/textpattern/"
	if(preg_match("/(['\"]).*\/textpattern\/.*\\1/Ui", $html) == 1)
		return "Textpattern";

	# "/pivot/"
	if(preg_match("/(['\"]).*\/pivot\/.*\\1/Ui", $html) == 1)
		return "Pivot";

	# "/wp-content/"
	if(preg_match("/(['\"]).*\/wp-content\/.*\\1/Ui", $html) == 1)
		return "Wordpress";

	return "";
}

function get_tool_index($tool)
{
	prepare_string($tool);
	$row = mysql_fetch_row(query("SELECT `index` FROM tool WHERE name='$tool'"));
	if(isset($row[0]))
		return $row[0];
	else
		return false;
}

function add_tool($tool)
{
	prepare_string($tool);
	query("INSERT INTO tool (name) VALUES ('$tool')", false);
}

function set_blog_tool($tool, $index)
{
	add_tool($tool);
	$tindex = get_tool_index($tool);
	query("UPDATE blog SET tool=$tindex WHERE `index`=$index");
}

$unknown_html = array();

function analyse_tools()
{
	global $unknown_html, $hostings;

	echo "analysing blog tools...\n";
	ini_set("user_agent", "BlogwalkBot/1.0 (+http://www.blogwalk.se/about/bot)");

	$result = query("SELECT `index`,url,name FROM blog");

	$generators = array();
	while($row = fetch_array($result))
	{
		$tool = "";
		$domain = get_server_from_url($row["url"]);
		foreach($hostings as $host)
			if(string_endswith($domain, $host))
			{
				$tool = $host;
				break;
			}


	# !!! om bloggens url inkluderar /wp/ eller /wordpress/ eller /mt/ ...


		# om bloggen inte har en känd URL, hämta html-data för analys
		if($tool == "")
		{
			$data = @file_get_contents($row["url"]);
			if($data === false)
			{
				echo "! inget svar från $row[name]\n";
				continue;
			}

			$tool = extract_generator($data);
			set_blog_tool($tool, $row["index"]);

			if($tool == "")
				array_push($unknown_html, $data);
		}
		# bloggen har en känd URL
		else
			set_blog_tool($tool, $row["index"]);
	}
}

analyse_tools();
save_data($unknown_html, "unknown_tools");

?>

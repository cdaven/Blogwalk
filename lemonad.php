<?

function fetch_lemonad_changes()
{
	# g�r en backup av f�rra XML-filen
	copy("lemonad.xml", "lemonad.bak");

	$previous = parse_lemonad_xml(file_get_contents("lemonad.xml"));
	$last_time = $previous[0]["time"];
	$lemonad_url = "http://svensk.lemonad.org/changes.php?since=$last_time";

	ini_set("user_agent", "BlogwalkBot/1.0 (+http://www.blogwalk.se/about/bot)");

	$calc = new SimilarityCalculator();

	$xmldata = file_get_contents($lemonad_url);
	if($xmldata === false)
		die("failure reading $lemonad_url \n");

	# sparar inl�st data f�r eventuell fels�kning
	$fh = fopen("lemonad.temp", "w");
	fwrite($fh, $xmldata);
	fclose($fh);

	$posts = parse_lemonad_xml($xmldata);
	foreach($posts as $post)
	{
		# ignorerar inl�gg som saknar sammandrag eller permal�nk
		if($post["summary"] == "" or $post["summary"] == "..." or $post["url"] == "")
			continue;

		# l�gger till bloggen och inl�gget. om add_x returnerar false har ingenting lagts till databasen
		if(add_blog($post["blog_name"], $post["blog_url"], $post["blog_desc"],
		$post["location"], $post["feed_url"]))
			if(add_post($post["blog_url"], $post["title"], $post["summary"],
			$post["time"], $post["url"]))
			{
				# lagrar eventuella taggar/etiketter
				if($post["tags"] != "")
					add_tags($post["url"], $post["tags"]);

				# lagrar podcast-url om s�dan angivits
				if($post["podcast"] != "")
					add_podcast($post["url"], $post["podcast"]);
			}
	}

	if(count($posts) > 0)
	{
		# skriver en ny XML-fil endast om antalet poster �r minst 1
		# annars skulle vi skriva en "tom" fil utan information om n�r
		# vi senast h�mtade posterna
		$fh = fopen("lemonad.xml", "w");
		fwrite($fh, $xmldata);
		fclose($fh);
	}
}

# parsar Lemonad-XML-formatet till en array
function parse_lemonad_xml($xmldata)
{
	$xmltoarray = new XMLToArray();
	$root_node = $xmltoarray->parse($xmldata);

	if($xmltoarray->errormsg != "")
	{
		$x = $xmltoarray->curpos["column"];
		$y = $xmltoarray->curpos["line"];
		die("parser error at line $y, column $x: ".$xmltoarray->errormsg);
	}

	validate_lemonad_version($root_node) or die("Lemonad levererade XML-data i ok�nt format.");

	$data = array();
	$posts = xml_get_all_elements($root_node, "entry");
	foreach($posts as $post)
	{
		$p = array();
		$p["time"] = xml_get_element_data($post, "pingtime");
		$p["blog_name"] = xml_get_element_data($post, "name");
		$p["blog_desc"] = xml_get_element_data($post, "description");
		$p["blog_url"] = xml_get_element_data($post, "url");
		$p["url"] = xml_get_element_data($post, "permalink");
		$p["title"] = xml_get_element_data($post, "title");
		$p["location"] = xml_get_element_data($post, "location");
		$p["summary"] = xml_get_element_data($post, "text");
		$p["feed_url"] = xml_get_element_data($post, "xmlurl");
		$p["podcast"] = xml_get_element_data($post, "podcast");
		$p["tags"] = xml_get_element_data($post, "tags");

		array_push($data, $p);
	}

	return $data;
}

# kontrollerar att formen p� XML-datat inte har f�r�ndrats
function validate_lemonad_version($root)
{
	$leaf = xml_get_element($root, "version");
	$major = xml_get_element_data($leaf, "major");
	$minor = xml_get_element_data($leaf, "minor");

	if($major != "1") return false;
	if($minor != "1") return false;

	return true;
}

# "�vers�tter" fr�n Lemonads tidsangivelse till mySQL-standard
function parse_timestamp($timestamp)
{
	$year = substr($timestamp, 0, 4);
	$month = substr($timestamp, 5, 2);
	$day = substr($timestamp, 8, 2);
	$hour = substr($timestamp, 11, 2);
	$minute = substr($timestamp, 14, 2);
	$second = substr($timestamp, 17, 2);

	return "$year-$month-$day $hour:$minute:$second";
}

?>

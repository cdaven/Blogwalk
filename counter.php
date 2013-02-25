<?php

class Counter
{
	function count($article, $tag = "")
	{
		global $editor;

		# räknar inte mig när jag är inloggad
		if($editor->IsLoggedIn())
			return;

		$time = time();
		$ip = $_SERVER["REMOTE_ADDR"];
		$agent = mysql_escape_string($_SERVER["HTTP_USER_AGENT"]);
		$referrer = mysql_escape_string($_SERVER["HTTP_REFERER"]);

		$article = mysql_escape_string($article);
		$tag = mysql_escape_string($tag);

		query("INSERT INTO log (ip,time,article,tag,referrer,agent) VALUES ('$ip',$time,'$article','$tag','$referrer','$agent')");
	}
}


function dumbfilter($a)
{
	return ($a != "1");
}


class CounterStatistics
{
	var $log;
	var $data;

	var $human_agents;
	var $robot_agents_with_mozilla;
	var $robot_agents;
	var $os;
	var $browser;

	function CounterStatistics($time_begin = 0, $time_end = null)
	{
		$this->log = array();

		if($time_end)
			$result = query("SELECT * FROM log WHERE time >= $time_begin AND time < $time_end ORDER BY time");
		else
			$result = query("SELECT * FROM log ORDER BY time");

		while($row = fetch_array($result))
			array_push($this->log, $row);

		$this->human_agents = array(
			"Mozilla", "MSIE", "Opera", "Netscape", "Links", "Dillo", "EPOC32", "UPG1", "curl",
			"amaya", "Mosaic", "IBrowse", "Lynx", "iCab", "DocZilla", "ELinks", "ICE Browser",
			"OmniWeb", "Bison", "W3CLineMode", "w3m", "Xiino", "BlackBerry", "Nokia", "AU-MIC",
			"SHARP", "SIE-", "LG-", "ReqwirelessWeb", "SonyEricsson", "Slimbrowser", "Enigma Browser",
			"Netsurf", "T-Online Browser", "LG", "SAGEM", "SHARP", "DoCoMo",
		);

		$this->robot_agents_with_mozilla = array(
			"Ask Jeeves", "FAST", "Firefly", "Googlebot", "grub-client", "Yahoo! Slurp", "MSIECrawler",
			"ZyBorg", "kulturarw3", "heritrix", "Zyte", "HTTrack", "SpeedySpider", "Cerberian Drtrs",
			"GPU p2p crawler", "HallBot", "Larbin",
		);

		$this->robot_agents = array_merge($this->robot_agents_with_mozilla,
			array("Bloglines", "FeedOnFeeds", "NetNewsWire", "PHP", "Java", "msnbot", "Filangy",
				"ccubee", "aipbot", "yacy", "OmniExplorer", "RssBandit", "MagpieRSS",
				"Liferea", "Wget", "updated.com", "LWP::Simple", "lmspider"));

		$this->os = array(
			"Windows", "Linux", "Macintosh", "FreeBSD", "OpenBSD", "AmigaOS", "SunOS", "OS/2", "BeOS",
			"Nokia", "SonyEricsson", "LG-", "SIE-",
		);

		# måste kontrollera i rätt ordning eftersom vissa webbläsare har flera id-strängar
		$this->browser = array("Opera", "MSIE 3", "MSIE 4", "MSIE 5", "MSIE 6", "MSIE 7", "Safari", "KHTML", "Firefox",
			"Thunderbird", "Netscape", "Gecko", "Lynx", "Links", "ELinks", "w3m", "W3CLineMode", "Google WAP Proxy",
			"Konqueror", "NAVIO", "AvantGo");

		$this->feedreader = array("Google Desktop", "Liferea", "Bloglines", "Sage", "Firefox");

		$this->data = array();

		$this->analyse();
	}

	function analyse()
	{
		$ip_humans = array();
		$ip_spiders = array();

		foreach($this->log as $entry)
		{
			if($this->is_probably_human($entry["agent"]))
			{
				$this->data["requests"][$entry["tag"]."/".$entry["article"]]++;

				$this->data["humans"]++;
				if($entry["tag"] == "post")
					$this->data["posts"][$entry["article"]]++;

				if($this->is_unique(&$ip_humans, $entry["ip"], $entry["time"]))
				{
					if(string_endswith($entry["tag"], "rss"))
					{
						# räknar alla åtkomster till flödena separat
						$this->data["unique_feedreaders"]++;
						$this->data["feedreaders"][$this->get_feedreader($entry["agent"])]++;
					}
					else
					{
						$this->data["unique_humans"]++;
						$this->data["oses"][$this->get_os($entry["agent"])]++;
						$this->data["browsers"][$this->get_browser($entry["agent"])]++;
						# $this->data["referrer_full"][$entry["referrer"]]++;
						$this->data["days"][mb_convert_encoding(strftime("%A %e %B", $entry["time"]), "UTF-8", "ISO-8859-1")]++;
					}

					$this->data["tags"][$entry["tag"]]++;

					$server = get_server_from_url($entry["referrer"]);
					# alla google-domäner klumpas ihop till en
					if(string_startswith($server, "google."))
						$server = "google.*";
					# alla statcounter-subdomäner klumpas ihop till en
					if(string_endswith($server, ".statcounter.com"))
						$server = "*.statcounter.com";

					$this->data["referrer_sites"][$server]++;

					$this->interpret_referrer($entry["referrer"]);
				}

				if($entry["tag"] == "search")
				{
					$query = mb_eregi_replace("[^a-zåäö0-9]", " ", mb_strtolower($entry["article"]));
					$query = preg_replace("/\s+/", " ", trim($query));

					$this->data["search_internal"][$query]++;
				}
			}
			else
			{
				$this->data["robots"]++;
				if($this->is_unique(&$ip_spiders, $entry["ip"], $entry["time"]))
				{
					if(string_endswith($entry["tag"], "rss"))
					{
						# räknar alla åtkomster till flödena separat
						$this->data["unique_feedreaders"]++;
						$this->data["feedreaders"][$this->get_feedreader($entry["agent"])]++;
					}
					else
					{
						$this->data["unique_robots"]++;
						$this->data["robot_agents"][$this->get_robot($entry["agent"])]++;
					}
				}
			}
		}
	}

	function interpret_referrer($referrer)
	{
		$refpatterns = array(
			'/.*google(\.[a-z]{2,3})+.*(\?|&)q=([^&]+).*/i'=> array('Google', 3),
			'/lycossvar\.spray\.se.*(\?|&)query=([^&]+).*/i'=> array('Spray', 2),
			'/(www\.)?eniro\.[se|no].*(\?|&)q=([^&]+).*/i'=> array('Eniro', 3),
			'/search\.msn(\.[a-z]{2,3})+.*(\?|&)q=([^&]+).*/i'=> array('MSN', 3),
			'/(se\.)?search\.yahoo\.com.*(\?|&)p=([^&]+).*/i'=>array('Yahoo', 3),
			'/([a-z]{2-3}\.)?altavista.com.*(\?|&)q=([^&]+).*/i' => array('AltaVista', 3),
			'/alltheweb\\.com.*(\?|&)q=([^&]+).*/i'=>array('AllTheWeb', 2),
			'/web\.ask\.com.*(\?|&)ask=([^&]+).*/i'=>array('Ask Jeeves', 2),
			'/aolsearch\.aol(\\.[a-z]{2,3})+.*(\?|&)query=([^&]+).*/i'=>array('AOL', 3),
			'/www\.dogpile\.com.*\/([^\/]+)/i'=>array('Dogpile', 1),
			'/www\\.mywebsearch\\.com.*(\\?|&)searchfor=(.+?)(&|$).*/i'=>array('MyWay', 2),
			'/search\\.wanadoo(\\.[a-z]{2,3})+.*(\\?|&)q=(.+?)(&|$).*/i'=>array('Wanadoo', 3),
			'/.*a9\.com\/([^&?]+).*/i'=>array('A9', 1),
		);

		if(!isset($this->data["searches"]))
			$this->data["searches"] = array();

		if(!isset($this->data["search_queries"]))
			$this->data["search_queries"] = array();

		global $stoplist;

		foreach($refpatterns as $keng => $eng)
			if(preg_match($keng, $referrer, $matches))
			{
				$query = urldecode($matches[$eng[1]]);
				$query = mb_strtolower(mb_convert_encoding($query, "UTF-8", mb_detect_encoding($query, "UTF-8, iso-8859-1")));
				$query = str_replace(array(" and ", " or "), " ", $query);

				array_push($this->data["searches"], "$eng[0]: $query");

				$query = mb_eregi_replace("[^a-zåäöìíïéèëñàáü0-9]", " ", $query);
				$query = preg_replace("/\s+/", " ", trim($query));

				$this->data["search_queries"][$query]++;

				break;
			}
	}
	
	function is_probably_human($agent)
	{
		foreach($this->human_agents as $human)
			if(strpos($agent, $human) !== false)
			{
				if($human == "Mozilla" && $this->is_robot_disguised_as_mozilla($agent))
					return false;
				else
					return true;
			}

		return false;
	}

	function is_robot_disguised_as_mozilla($agent)
	{
		foreach($this->robot_agents_with_mozilla as $robot)
			if(strpos($agent, $robot) !== false)
				return true;

		return false;
	}
	
	function get_os($agent)
	{
		foreach($this->os as $os)
			if(strpos($agent, $os) !== false)
				return $os;

		if(strpos($agent, "Win") !== false)
			return "Windows";
		elseif(strpos($agent, "Mac") !== false)
			return "Macintosh";

		return $agent;
	}

	function get_robot($agent)
	{
		foreach($this->robot_agents as $robot)
			if(strpos($agent, $robot) !== false)
				return $robot;

		return $agent;
	}

	function get_browser($agent)
	{
		foreach($this->browser as $browser)
			if(strpos($agent, $browser) !== false)
				return $browser;

		return $agent;
	}

	function get_feedreader($agent)
	{
		foreach($this->feedreader as $browser)
			if(strpos($agent, $browser) !== false)
				return $browser;

		return $agent;
	}

	function is_unique(&$table, $ip, $timestamp)
	{
		$unique = false;
		if(!isset($table[$ip]) or $table[$ip] + 1800 < $timestamp)
			$unique = true;

		$table[$ip] = $timestamp;
		return $unique;
	}

	function generate_list($list, $count, $urlprefix = "")
	{
		arsort($list);
		$i = 0;

		#$total = array_sum($list);

		$html = "<table>";
		foreach($list as $id => $hits)
		{
			if($id == "") $id = "-";

			$id = tidy_up_text($id);

			#$percent = round(100 * $hits / $total, 1);

			if($urlprefix !== "")
				$html .= "<tr><td>$hits</td><td><em><a href='$urlprefix$id'>$id</a></em></td></tr>";
			else
				$html .= "<tr><td>$hits</td><td><em>$id</em></td></tr>";

			$i++;
			if($i == $count) break;
		}

		$html .= "</table>";
		return $html;
	}

	function merge_list($list)
	{
		$list = array_filter($list, "dumbfilter");
		arsort($list);

		$already = array();
		$xlist = array();
		$queries = array_keys($list);
		for($i = 0; $i < count($queries); $i++)
		{
			$queryi = $queries[$i];
			$query = $queryi;
			$count = (int)$list[$query];
			if($already[$query])
				continue;

			for($j = $i + 1; $j < count($queries); $j++)
			{
				$queryj = $queries[$j];
				if(levenshtein($queryi, $queryj) <= 2)
				{
					$count += (int)$list[$queryj];
					$query .= ":$queryj";
					$already[$queryj] = true;
				}
			}

			$xlist[$query] = $count;
		}

		arsort($xlist);
		return $xlist;
	}

	function generate_xlist($list, $count)
	{
		$list = $this->merge_list($list);

		$i = 0;
		$html = "<table>";
		foreach($list as $id => $hits)
		{
			$id = tidy_up_text($id);
			$id = str_replace(":", ", ", $id);

			$html .= "<tr><td>$hits</td><td><em>$id</em></td></tr>";

			$i++;
			if($i == $count) break;
		}
		$html .= "</table>";
		return $html;
	}

	function generate_simple_list($list, $count)
	{
		$i = 0;

		$html = "<ol>";
		foreach($list as $id)
		{
			if($id == "") continue;

			$id = tidy_up_text($id);
			$html .= "<li>$id</li>";

			$i++;
			if($i == $count) break;
		}

		$html .= "</ol>";
		return $html;
	}

	function generate_post_list($list, $count)
	{
		arsort($list);
		$i = 0;

		$html = "<ol>";
		foreach($list as $id => $hits)
		{
			if($id == "") continue;
			$post = get_post($id);

			$title = tidy_up_text($post["title"]);

			$html .= "<li><a href='/blogwalk/post/$id'>$title</a></li>";

			$i++;
			if($i == $count) break;
		}

		$html .= "</ol>";
		return $html;
	}

	function generate_statistics()
	{
		global $self;

		$output = "<style type='text/css'>td,th { text-align: left; }</style>
			<h2>Räknarstatistik</h2>";

		$cs = new CounterStatistics(time() - 24 * 3600, time());
		$humanslast24h = number_to_string($cs->data["unique_humans"]);

		$humans = number_to_string($this->data["unique_humans"]);
		$spiders = number_to_string($this->data["unique_robots"]);
		$feedreaders = number_to_string($this->data["unique_feedreaders"]);

		$hits = number_to_string($this->data["humans"]);
		$spiderhits = number_to_string($this->data["robots"]);

		$uptime = (time() - $this->log[0]["time"]) / 3600 / 24;
		$humansperday = number_to_string($this->data["unique_humans"] / $uptime);
		$hitspervisit = number_to_string($this->data["humans"] / $this->data["unique_humans"], 1);

		$categories = array("main", "locations", "location", "blogurl", "blog", "about", "post", "search", "contact");

		$output .= "<p><strong>{$this->data[unique_humans]}</strong> besök (<strong>$humansperday</strong> per dygn, men <strong>$humanslast24h</strong> det senaste dygnet). "
			."Totalt $hits sidvisningar, eller $hitspervisit per besök.</p>"
			."<p><strong>{$this->data[unique_robots]}</strong> unika bots/spindlar. Totalt $spiderhits sidvisningar.</p>";

		$output .= "<h3>Dagar med flest unika besök</h3>"
			.$this->generate_list($this->data["days"], 10);

		$output .= "<h3>De vanligaste referenserna</h3>"
			.$this->generate_list($this->data["referrer_sites"], 40);

		$output .= "<h3>De mest besökta inläggen (ej unika träffar)</h3>"
			.$this->generate_post_list($this->data["posts"], 15);

		$output .= "<h3>De vanligaste sidorna (ej unika träffar)</h3>"
			.$this->generate_list($this->data["requests"], 20);

		$output .= "<h3>De vanligaste sökfraserna</h3>"
			.$this->generate_xlist($this->data["search_queries"], 15);

		$output .= "<h3>De vanligaste sidtyperna för 'ingång'</h3>"
			.$this->generate_list($this->data["tags"], 20);

		$output .= "<h3>De vanligaste robotarna</h3>"
			.$this->generate_list($this->data["robot_agents"], 10);

		$output .= "<h3>De vanligaste operativsystemen</h3>"
			.$this->generate_list($this->data["oses"], 20);

		$output .= "<h3>De vanligaste webbläsarna</h3>"
			.$this->generate_list($this->data["browsers"], 20);

		$output .= "<h3>De vanligaste flödesläsarna</h3>"
			.$this->generate_list($this->data["feedreaders"], 20);

		$output .= "<h3>De vanligaste interna sökfraserna</h3>"
			.$this->generate_list($this->data["search_internal"], 10);

		$output .= "<h3>De senaste sökningarna</h3>"
			.$this->generate_simple_list(array_reverse($this->data["searches"]), 60);

		return $output;
	}
}

?>

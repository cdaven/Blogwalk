<?

function doConditionalGet($timestamp)
{
	// A PHP implementation of conditional get, see 
	//   http://fishbowl.pastiche.org/archives/001132.html
	$last_modified = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
	$etag = '"'.md5($last_modified).'"';
	// Send the headers
	header("Last-Modified: $last_modified");
	header("ETag: $etag");
	// See if the client has provided the required headers
	$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
	    stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) :
	    false;
	$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
	    stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : 
	    false;
	if (!$if_modified_since && !$if_none_match) {
	    return;
	}
	// At least one of the headers is there - check them
	if ($if_none_match && $if_none_match != $etag) {
	    return; // etag is there but doesn't match
	}
	if ($if_modified_since && $if_modified_since != $last_modified) {
	    return; // if-modified-since is there but doesn't match
	}
	// Nothing has changed since their last request - serve a 304 and exit

	header('HTTP/1.0 304 Not Modified');
	exit;
}

# inkluderar filer på ett felsäkert sätt.
# när en fil saknas eller håller på att uppdateras visas ett meddelande om detta.
# annars visas kryptiskt fatal error av PHP.

$includes = array(
	"login.php",
	"keywords.php",
	"string.php",
	"db.php",
	"utf8.php",
	"html_entities.php",
	"date.php",
	"html_admin.php",
	"html.php",
	"counter.php",
	"syndication.php",
);

foreach($includes as $include)
{
	$include = "/home/davense/aggregator/$include";

	if(is_readable($include))
	{
		# måste ha {} omkring sig av någon anledning
		require_once($include);
	}
	else
	{
		die("För tillfället är Blogwalk inte tillgänglig. Uppgraderingsarbete pågår och bör vara avslutat inom ett par minuter.");
	}
}

# -------------------------------------------

function redirect($url = "/blogwalk/index.php", $code = 302)
{
	header("Location: http://www.daven.se$url", true, $code);
	exit;
}

function return_404($message)
{
	header("HTTP/1.0 404 Not Found");
	die("<h1>Sidan finns inte (felkod 404)</h1><p>$message</p>");
}

$editor = new EditorLogin();

$subtitle = "";
$showfooter = true;
$rawcode = false;
$robots = "index,follow";

$postsearch = false;
if(!isset($_GET["type"]) or $_GET["type"] == "post")
	$postsearch = true;

$robotvisit = false;
if(string_contains_any($_SERVER["HTTP_USER_AGENT"], array("Googlebot", "Yahoo! Slurp", "msnbot")))
	$robotvisit = true;

$counterpage = "";
$countertag = "";
$count = true; # räknar inte administrativa sidor etc.

$querycount = 0;

if(isset($_GET["post"]))
{
	$post = get_post((int)$_GET["post"]);
	$title = urlencode(fix_amps($post["title"]));
	$main = "<h2>Inga fler liknande inlägg</h2>
		<p>Denna funktionalitet på Blogwalk har upphört. Använd <a href='/blogwalk/?search=$title'>sökfunktionen</a> eller <a href='/blogwalk/'>bläddra manuellt</a> istället.</p>";
	$robots = "noindex,follow,nosnippet";

/*
	$_GET["post"] = (int)$_GET["post"];

	# normaliserar URL så att det endast finns en unik adress per inlägg
	if(preg_match("/blogwalk\/post\/\d+\/.+/", $_SERVER["REQUEST_URI"]) == 0)
	{
		$post = get_post($_GET["post"]);
		if($post === false)
			return_404("Inlägget du söker finns inte. Det är möjligt att det togs bort nyligen.");

		redirect("/blogwalk/post/$_GET[post]/".string_to_url($post["title"]), 301);
	}

	$p = new PostHtml();
	$main = $p->generate($_GET["post"]);
	if($main === false)
		return_404("Inlägget du söker finns inte. Det är möjligt att det togs bort nyligen.");

	$counterpage = $_GET["post"];
	$countertag = "post";
	$subtitle = $p->title;
	$robots = "noindex,follow,nosnippet";
*/
}
elseif(isset($_GET["search"]))
{
	$searchstring = stripslashes(fix_amps(urldecode($_GET["search"])));

	$counterpage = $searchstring;
	$countertag = "search";

	$subtitle = "Sökresultat";
	$robots = "noindex,follow";

	$s = new SearchEngineHtml();
	$main = "<h2>Sökresultat</h2>"
		.$s->generate($searchstring, 50, $postsearch);
}
elseif(isset($_GET["statistics"]))
{
	$countertag = "statistics";
	$subtitle = "Statistik";

	# skickar 304 Not Modified om sidan inte förändrats
	$stat = stat("/home/davense/aggregator/cache/stats");
	doConditionalGet($stat["mtime"]);

	$s = new StatsHtml();
	$main = "<h2>Statistik</h2>".$s->generate();
}
elseif(isset($_GET["about"]))
{
	require_once("/home/davense/aggregator/texts.php");

	if(isset($text[$_GET["about"]]))
	{
		$counterpage = $_GET["about"];
		$countertag = "about";

		$main = $text[$_GET["about"]];
		$subtitle = $title[$_GET["about"]];
	}
	else
		return_404("Den angivna texten finns inte.");
}
elseif(isset($_GET["blog"]))
{
	$countertag = "blog";
	$b = new BlogHtml();
	if($_GET["blog"] == "")
	{
		$main = $b->generate_newest_list(30);
		$subtitle = "Nya bloggar";
	}
	else
	{
		$main = $b->generate((int)$_GET["blog"]);
		if($main === false)
			return_404("Bloggen du söker finns inte. Det är möjligt att den togs bort nyligen.");
		else
		{
			$counterpage = (int)$_GET["blog"];
			$subtitle = "Inlägg från ".$b->name;
		}
	}
}
elseif(isset($_GET["blogurl"]))
{
	$url = mysql_escape_string(stripslashes(fix_amps(urldecode($_GET["blogurl"]))));
	$row = fetch_array(query("SELECT `index`,name FROM blog WHERE url LIKE '%$url%' LIMIT 1"));

	if($row === false)
		return_404("Bloggen du söker finns inte. Det är möjligt att den togs bort nyligen.");
	else
	{
		$counterpage = $row["name"];
		$countertag = "blogurl";
		redirect("/blogwalk/blog/$row[index]");
	}
}
elseif(isset($_GET["location"]))
{
	$location = stripslashes(fix_amps(fix_utf8(urldecode($_GET["location"]))));
	$l = new LocationHtml();

	if($location == "")
	{
		# skickar 304 Not Modified om sidan inte förändrats
		$stat = stat("/home/davense/aggregator/cache/locationcloud");
		doConditionalGet($stat["mtime"]);

		$main = load_data("/home/davense/aggregator/cache/locationcloud");
		#$main = $l->generate_cloud();

		$subtitle = "Länsvis";
		$countertag = "locations";
	}
	else
	{
		$subtitle = "Bloggar i $location";
		$main = $l->generate(fix_utf8($_GET["location"]), 30);
		if($main === false)
			return_404("Det angivna länet finns inte.");

		$counterpage = $location;
		$countertag = "location";
	}
}
elseif(isset($_GET["tag"]))
{
	$tag = stripslashes(fix_amps(fix_utf8(urldecode_tag($_GET["tag"]))));
	$t = new TagHtml();

	if($tag == "")
	{
		$subtitle = "Etiketter";

		# skickar 304 Not Modified om sidan inte förändrats
		$stat = stat("/home/davense/aggregator/cache/tagcloudpage");
		doConditionalGet($stat["mtime"]);

		$main = load_data("/home/davense/aggregator/cache/tagcloudpage");
		#$main = $t->generate_cloud(100);
		$countertag = "tagcloud";
	}
	else
	{
		$counterpage = $tag;
		if(isset($_GET["tagfeed"]))
		{
			$main = generate_tag_rss($tag, 15);
			if($main === false)
				return_404("Den angivna etiketten finns inte.");

			$countertag = "tag_rss";
			$rawcode = true;
		}
		else
		{
			$subtitle = "Etikett: $tag";
			$main = $t->generate_tag($tag, 20);
			if($main === false)
				return_404("Den angivna etiketten finns inte.");

			$countertag = "tag";
		}
	}
}
elseif(isset($_GET["supertag"]))
{
	$tag = stripslashes(fix_amps(fix_utf8(urldecode_tag($_GET["supertag"]))));
	$t = new SuperTagHtml();

	if($tag == "")
	{
		$subtitle = "Super-etiketter";

		# skickar 304 Not Modified om sidan inte förändrats
		$stat = stat("/home/davense/aggregator/cache/supertags");
		doConditionalGet($stat["mtime"]);

		$main = load_data("/home/davense/aggregator/cache/supertags");
		#$main = $t->generate_overview();
		$countertag = "supertags";
	}
	else
	{
		$counterpage = $tag;
		if(isset($_GET["tagfeed"]))
		{
			$main = generate_supertag_rss($tag, 15);
			if($main === false)
				return_404("Den angivna super-etiketten finns inte.");

			$countertag = "supertag_rss";
			$rawcode = true;
		}
		else
		{
			$subtitle = "Superetikett: $tag";
			$main = $t->generate_tag($tag, 20);
			if($main === false)
				return_404("Den angivna super-etiketten finns inte.");

			$countertag = "supertag";
		}
	}
}
elseif(isset($_GET["zeitgeist"]))
{
	$z = new ZeitgeistHtml();

	$countertag = "zeitgeist";
	$subtitle = "Trendspaning";

	if(isset($_GET["week"]))
	{
		$main = $z->generate_week($_GET["week"], 5);
		if($main === false)
			return_404("Den angivna tidpunkten finns inte.");

		$counterpage = (int)$_GET["week"];
	}
	else
		$main = $z->generate_main(7);
}
elseif(isset($_GET["contact"]))
{
	$c = new ContactHtml();
	$robots = "noindex,follow";

	if(isset($_POST["email"]) and isset($_POST["subject"]))
	{
		$email = fix_amps(urldecode($_POST["email"]));
		$subject = stripslashes(fix_amps(urldecode($_POST["subject"])));
		$main = $c->send_message($email, $subject);
		$count = false;
	}
	else
	{
		$main = "<h2>Kontaktformulär</h2><p>Ange din e-postadress och ditt meddelande nedan, så återkommer jag. Alternativt kan du <a href='mailto:%63&#104;&#x72;%69&#115;&#x74;%69&#97;&#x6e;%40&#100;&#x61;%76&#101;&#x6e;%2e&#115;&#x65;' title='Skicka e-post till skribenten'>skicka e-post till mig</a>.</p>"
			.$c->generate_form();

		$countertag = "contact";
	}

	$subtitle = "Kontaktformulär";
}
elseif(isset($_GET["login"]))
{
	if($editor->Login($_POST["username"], $_POST["password"]))
		redirect("/blogwalk/index.php");

	$robots = "noindex,follow";
	$main = generate_login_form();
	$count = false;
}
elseif(isset($_GET["editpost"]))
{
	if(!$editor->IsLoggedIn())
		redirect("/blogwalk/index.php?login");

	if(isset($_POST["index"]))
	{
		$title = stripslashes(preg_replace("/&amp;(#\d+|[\da-zA-Z]+);/", "&\\1;", $_POST["title"]));
		$summary = stripslashes(preg_replace("/&amp;(#\d+|[\da-zA-Z]+);/", "&\\1;", $_POST["summary"]));

		update_post($_POST["index"], $title, $summary);
		redirect("/blogwalk/post/$_POST[index]");
	}
	else
		$main = generate_post_form($_GET["editpost"]);

	$count = false;
}
elseif(isset($_GET["removepost"]))
{
	if(!$editor->IsLoggedIn())
		redirect("/blogwalk/index.php?login");

	remove_post($_GET["removepost"]);
	redirect();
}
elseif(isset($_GET["removeblog"]))
{
	if(!$editor->IsLoggedIn())
		redirect("/blogwalk/index.php?login");

	remove_blog($_GET["removeblog"]);
	redirect();
}
elseif(isset($_GET["editblog"]))
{
	if(!$editor->IsLoggedIn())
		redirect("/blogwalk/index.php?login");

	if(isset($_POST["index"]))
	{
		if($_POST["move"] == "1")
		{
			merge_blogs($_POST["index"], $_POST["destination"]);
		}
		else
		{
			$name = preg_replace("/&amp;(#\d+|[\da-zA-Z]+);/", "&\\1;", $_POST["name"]);
			$description = preg_replace("/&amp;(#\d+|[\da-zA-Z]+);/", "&\\1;", $_POST["description"]);

			update_blog($_POST["index"], $name, $description);
		}

		redirect("/blogwalk/blog/$_POST[index]");
	}
	else
		$main = generate_blog_form($_GET["editblog"]);

	$count = false;
}
elseif(isset($_GET["edittag"]))
{
	if(!$editor->IsLoggedIn())
		redirect("/blogwalk/index.php?login");

	if(isset($_GET["friend"]) and isset($_POST["tag1"]) and isset($_POST["tag2"]))
	{
		$tag1 = get_tag_index(fix_amps(urldecode_tag($_POST["tag1"])));
		$tag2 = get_tag_index(fix_amps(urldecode_tag($_POST["tag2"])));

		if($tag1 !== false and $tag2 !== false)
			add_tag_friend($tag1, $tag2);

		redirect("/blogwalk/tag/".urlencode_tag($_POST["tag1"]));
	}
	elseif(isset($_GET["super"]) and isset($_POST["tag1"]) and isset($_POST["tag2"]))
	{
		$tag1 = urldecode_tag($_POST["tag1"]);
		$tag2 = urldecode_tag($_POST["tag2"]);

		if($tag1 !== false and $tag2 !== false)
			add_subtag($tag2, $tag1);

		redirect("/blogwalk/tag/".urlencode_tag($_POST["tag1"]));
	}
	else
		$main = generate_tag_form(fix_amps(urldecode_tag($_GET["edittag"])));

	$count = false;
}
elseif(isset($_GET["showtags"]))
{
	if(!$editor->IsLoggedIn())
		redirect("/blogwalk/index.php?login");

	$t = new TagHtml();
	$main = $t->generate_cloud(750);
	$count = false;
}
elseif(isset($_GET["showinactiveblogs"]))
{
	if(!$editor->IsLoggedIn())
		redirect("/blogwalk/index.php?login");

	$main = generate_inactive_blogs();
	$count = false;
}
elseif(isset($_GET["clusters"]))
{
	if(!$editor->IsLoggedIn())
		redirect("/blogwalk/index.php?login");

	$c = new ClusterHtml();

	if(isset($_POST["week"]))
	{
		$main = $c->compile_week($_POST["week"], $_POST["cluster"]);
		redirect();
	}
	elseif(isset($_GET["week"]))
		$main = $c->generate_admin_week($_GET["week"]);
	else
		$main = $c->generate_admin_overview();

	$count = false;
}
elseif(isset($_GET["links"]))
{
	if(!$editor->IsLoggedIn())
		redirect("/blogwalk/index.php?login");

	$l = new LinksHtml();

	if(isset($_POST["week"]))
	{
		$main = $l->compile($_POST["week"], $_POST["links"]);
		redirect();
	}
	elseif(isset($_GET["week"]))
		$main = $l->generate($_GET["week"]);
	else
		$main = $l->generate_overview();

	$count = false;
}
elseif(isset($_GET["stats"]))
{
	if(!$editor->IsLoggedIn())
		redirect("/blogwalk/index.php?login");

	$stats = new CounterStatistics("/home/davense/aggregator/count");
	$main = $stats->generate_statistics();
	$count = false;
}
elseif(isset($_GET["feed"]))
{
	$main = generate_newest_rss(15);
	$countertag = "rss";
	$rawcode = true;
}
elseif(isset($_GET["offset"]))
{
	$stat = stat("/home/davense/aggregator/html.php");
	$timestamp = max($stat["mtime"], (int)$_GET["offset"]);
	# skickar 304 Not Modified om antingen html.php inte förändrats eller själva tidsstämpeln
	doConditionalGet($timestamp);

	$fp = new FrontPageHtml();
	$main = $fp->generate(20, (int)$_GET["offset"]);
	$robots = "noindex,follow";
	$countertag = "offset";
}
else
{
	# skickar 304 Not Modified om sidan inte förändrats
	$stat = stat("/home/davense/aggregator/cache/frontpage");
	doConditionalGet($stat["mtime"]);

	# hämtar färdigkompilerad data (från update_db.php)
	$main = load_data("/home/davense/aggregator/cache/frontpage");
	$countertag = "main";

	# ************************* DEBUG
	#$fp = new FrontPageHtml();
	#$main = $fp->generate(20);
	# *************************
}

if($rawcode)
{
	# räknar inte flöde om rätt autentiseringskod ges (så att jag själv inte räknas)
	if(isset($_GET["auth"]) && $_GET["auth"] == "PortoBetalt")
		$count = false;

	echo $main;
}
else
{
	$footer = generate_footer($showfooter);
	$header = generate_header($subtitle, $robots);
	$menu = generate_menu();

	echo
		"$header
		<div id='main'>$main</div>
		$menu
		$footer";
}

if($count)
{
	$counter = new Counter("/home/davense/aggregator/count");
	$counter->count($counterpage, $countertag);
}

?>

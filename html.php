<?

$self = "/blogwalk/index.php";

setlocale(LC_TIME, "sv_SE");

$stats = load_data("/home/davense/aggregator/cache/stats");
$devblog = load_data("/home/davense/aggregator/cache/devblog");

function generate_contenttype()
{
	@header("Content-Type: text/html;charset=utf-8");

	return array(
		"doctype" => "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01//EN' 'http://www.w3.org/TR/html4/strict.dtd'>
			<html lang='sv'>",
		"meta" => "<meta http-equiv='content-type' content='text/html; charset=utf-8'>"
	);
}

function generate_header($subtitle = "", $robots = "index,follow")
{
	global $self;

	$title = "$subtitle &ndash; Blogwalk";
	if($subtitle == "")
		$title = "Blogwalk &ndash; din bloggportal &ndash; samlade inlägg från svenska bloggar";

	$ct = generate_contenttype();

	return

"$ct[doctype]
<head><title>$title</title>
$ct[meta]
<meta name='robots' content='$robots'>
<meta name='author' content='Christian Davén'>
<meta name='description' content='Bloggportal som samlar inlägg från svenska bloggar. Du kan se de se de senaste inläggen eller söka bland alla inlägg.'>

<link rel='alternate' type='application/rss+xml' title='Blogwalk-flöde' href='/blogwalk/feed/'>
<link rel='start' href='$self'>
<link rel='author' href='/blogwalk/contact'>
<link rel='help' href='/blogwalk/about/whatisthis'>
<link rel='shortcut icon' href='http://www.daven.se/blogwalk/favicon.ico'>

<link rel='stylesheet' type='text/css' href='/blogwalk/style.css'>
<link rel='alternate stylesheet' type='text/css' href='/blogwalk/style_small.css' title='Liten'>
<link rel='alternate stylesheet' type='text/css' href='/blogwalk/style_medium.css' title='Standard'>
<link rel='alternate stylesheet' type='text/css' href='/blogwalk/style_large.css' title='Stor'>
<script type='text/javascript' src='/blogwalk/functions.js'></script>

</head><body><div id='container'>";

}

function generate_footer($showfooter = true)
{
	global $self;

	if($showfooter)
		$html = 

	"<div id='footer' class='clear'>
	<hr />
	<p>Skapat av <a href='/blogwalk/contact' accesskey='9'>Christian Davén</a> | All bloggdata kommer från <a href='http://svensk.lemonad.org/'>Var är du?</a> | <a href='http://creativecommons.org/licenses/by-nc/2.0/' title='Creative Commons' rel='license'>Läs licensavtalet</a></p>
	</div>";

	$html .= "</div></body></html>";

	return $html;
}

function generate_post_box($contents)
{
	return "<div class='boxwrap'><div class='topborder600'></div><div class='box600 postbox'>$contents</div><div class='bottomborder600'></div></div>";
}

function generate_large_box($contents)
{
	return "<div class='boxwrap'><div class='topborder600'></div><div class='box600'>$contents</div><div class='bottomborder600'></div></div>";
}

function generate_small_box($contents)
{
	return "<div class='boxwrap'><div class='topborder250'></div><div class='box250'>$contents</div><div class='bottomborder250'></div></div>";
}

function generate_latestblogs_list($count)
{
	$html = "<ul>";
	$result = query("SELECT * FROM blog ORDER BY `index` DESC LIMIT $count");
	while($blog = fetch_array($result))
	{
		$name = tidy_up_text($blog["name"]);
		if($blog["feedtitle"] != "")
				$name = tidy_up_text($blog["feedtitle"]);

		$index = $blog["index"];
		$blogurl = "/blogwalk/blog/$index";
		
		$html .= "<li><a href='$blogurl' title='Se inlägg från denna blogg'>$name</a></li>";
	}
	$html .= "</ul>";

	return $html;
}

function generate_list_ads()
{
	return

'<div class="adlinks">
<script type="text/javascript"><!--
google_ad_client = "pub-3336537055851942";
google_ad_width = 468;
google_ad_height = 60;
google_ad_format = "468x60_as";
google_ad_type = "text";
google_ad_channel ="2459231277";
google_color_border = "FFFFFF";
google_color_bg = "EEEEEE";
google_color_link = "333366";
google_color_url = "999999";
google_color_text = "000000";
//--></script>
<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
</div>';
}

function generate_menu_ads()
{
	return 

'<div class="ads">
<script type="text/javascript"><!--
google_ad_client = "pub-3336537055851942";
google_ad_width = 120;
google_ad_height = 240;
google_ad_format = "120x240_as_rimg";
google_cpa_choice = "CAAQm7n8zwEaCPDMcAKRk7b8KIfjl3Q";
//--></script>
<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
</div>

<div class="ads">
<script type="text/javascript"><!--
google_ad_client = "pub-3336537055851942";
google_ad_width = 120;
google_ad_height = 240;
google_ad_format = "120x240_as";
google_ad_type = "text";
google_ad_channel ="2459231277";
google_color_border = "FFFFFF";
google_color_bg = "EEEEEE";
google_color_link = "333366";
google_color_url = "999999";
google_color_text = "000000";
//--></script><script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
</div>';
}

function generate_menu()
{
	function generate_wordburst_list($count)
	{
		$bursts = load_data("/home/davense/aggregator/bursts/live");
		foreach(array_slice($bursts, 0, $count) as $burst)
		{
			$word = tidy_up_text($burst["word"]);
			$uword = urlencode($word);

			$html .= "<li><a href='/blogwalk/?search=$word'>$word</a></li>";
		}
		return $html;
	}

	global $self, $devblog, $editor, $querycount, $robotvisit, $editor, $stats, $searchstring;

	$number_posts = str_replace(",", "&nbsp;", number_format($stats["postcount"]));
	$number_blogs = str_replace(",", "&nbsp;", number_format($stats["blogcount"]));
	$searchstring = htmlspecialchars(str_replace("&amp;", "&", $searchstring), ENT_QUOTES);

	$posthtml = "checked='checked'";
	$bloghtml = "";
	if(!$postsearch)
	{
		$posthtml = "";
		$bloghtml = "checked='checked'";
	}

	$html = "";

	$bwdev = "";
	for($i = 0; $i < 4; $i++)
	{
		$url = $devblog[$i]["url"];
		$title = $devblog[$i]["title"];

		$bwdev .= "<li><a href='$url'>$title</a></li>";
	}

	$html .= 

	"<div id='sidebar'>
	<h1><a href='/blogwalk/' accesskey='1'><strong>Blog</strong>walk</a></h1>
	<h2 id='tagline'>Samlade inlägg<br />från svenska bloggar</h2>
	<form action='/blogwalk/' method='GET'>
		<p><label for='search' accesskey='4'>Sök igenom $number_posts blogginlägg:</label> <input id='search' name='search' type='text' value='$searchstring'>
			<label for='searchbutton' accesskey='S'></label> <button id='searchbutton' type='submit'>sök</button>
			<br />($number_blogs bloggar)</p>
	</form>";

	if($editor->IsLoggedIn())
	{
		$boxhtml = "<h3>Administration</h3><p>";

		if(isset($_GET["post"]))
			$boxhtml .= "<a href='$self?editpost=$_GET[post]'>Redigera inlägg</a><br />";
		elseif(isset($_GET["blog"]))
			$boxhtml .= "<a href='$self?editblog=$_GET[blog]'>Redigera blogg</a><br />";
		elseif(isset($_GET["tag"]))
			$boxhtml .= "<a href='$self?edittag=$_GET[tag]'>Redigera etikett</a><br />";

		$boxhtml .= 
"<a href='$self?showtags'>Visa etiketter</a><br />
<a href='$self?showinactiveblogs'>Visa inaktiva bloggar</a><br />
<a href='$self?clusters'>Hantera inläggskluster</a><br />
<a href='$self?links'>Hantera länklistor</a><br />
<a href='$self?stats'>Visa statistik</a></p>

		<p>$querycount SQL-anrop</p>";

		$html .= generate_small_box($boxhtml);
	}

	$html .=

	generate_small_box("<h3>Registrera din blogg</h3><p>Vill du också vara med här på Blogwalk? Se till att pinga <a href='http://svensk.lemonad.org/new.php'><em>Var är du?</em></a> när du skriver nya inlägg, så dyker din blogg automatiskt upp här också.</p><p class='more'><a href='/blogwalk/about/whatisthis#opt-in'>läs mer »</a></p>")."
	".generate_small_box("<h3>Trendspaning</h3><p>De trendigaste orden just nu:</p><ul>".generate_wordburst_list(7)."</ul><p class='more'><a href='/blogwalk/zeitgeist/'>mer trendspaning »</a></p>")."
	".generate_small_box("<h3>Nya bloggar</h3><p>De senast registrerade bloggarna:</p>".load_data("/home/davense/aggregator/cache/newestblogs")."<p class='more'><a href='/blogwalk/blog/'>fler nya bloggar »</a></p>")."
	".generate_small_box("<h3>Annat</h3><ul><li><a href='/blogwalk/tag/'>Etiketter »</a></li><li><a href='/blogwalk/supertag/'>Super-etiketter »</a></li><li><a href='/blogwalk/location/'>Länsvis indelning »</a></li><li><a href='/blogwalk/statistics/'>Bloggar-statistik »</a></li><li><a href='/blogwalk/feed/'>RSS-prenumeration »</a></li></ul>")."
	".generate_small_box("<h3>Utvecklingsblogg</h3><p>De senaste inläggen från Blogwalks utvecklingsblogg:</p><ul>$bwdev</ul><p class='more'><a href='http://blogwalk-dev.blogspot.com/'>utvecklingsbloggen »</a></p>")."
	".generate_small_box("<h3>Om Blogwalk</h3><ul><li><a href='/blogwalk/about/whatisthis' >Vad är Blogwalk?</a></li><li><a href='/blogwalk/about/whatisablog'>Vad är en blogg?</a></li><li><a href='/blogwalk/contact/'>Kontakta ansvarig</a></li></ul>")."
	".generate_small_box("<h3>Teckenstorlek</h3><p>Välj den teckenstorlek som passar dig bäst. Ditt val sparas i en cookie och koms ihåg till nästa gång.</p><ul><li><a href='#' onclick='setActiveStyleSheet(\"Liten\"); return false;'>Liten</a></li><li><a href='#' onclick='setActiveStyleSheet(\"Standard\"); return false;'>Standard</a></li><li><a href='#' onclick='setActiveStyleSheet(\"Stor\"); return false;'>Stor</a></li></ul>")

	.generate_menu_ads()

	."</div>";

	return $html;
}

function generate_icon($blogindex, $url, $link, $alt = '')
{
	global $hostings;

	$domain = get_server_from_url($url);
	foreach($hostings as $host)
		if(string_endswith($domain, $host))
		{
			$tool = $host;
			break;
		}

	$icon = "$blogindex.ico";
	if(isset($tool))
		$icon = "$tool.ico";

	if(is_readable("/home/davense/public_html/blogwalk/icons/$icon") or is_readable("/home/christian/web/www/blogwalk/icons/$icon"))
	{
		$alt = tidy_up_text($alt);
		$html = "<img class='icon' src='/blogwalk/icons/$icon' width='16' height='16' alt='$alt'>";
		if($link != "")
			$html = "<a href='$link'>$html</a>";
		return "$html ";
	}
	else
		return "";
}

function generate_spot($post, $realtime = false)
{
	global $self;

	$title = tidy_up_text($post["title"]);
	$url = "/blogwalk/post/$post[index]/".string_to_url($post["title"]);
	$directurl = fix_amps($post["url"]);
	$summary = get_first_paragraphs(tidy_up_text($post["summary"]), 200, 300, "<a href='$directurl'>", "</a>");

	$blogurl = "/blogwalk/blog/$post[blog]";
	$blogname = tidy_up_text($post["name"]);
	if($post["feedtitle"] != "")
			$blogname = tidy_up_text($post["feedtitle"]);

	if(isset($post["tags"]))
	{
		$tags = " Etiketterat som ";
		foreach($post["tags"] as $tag)
		{
			$tagurl = urlencode_tag($tag);
			$tag = fix_amps($tag);
			$tags .= "<a href='/blogwalk/tag/$tagurl' title='Se fler inlägg med denna etikett'>$tag</a>, ";
		}
		$tags = mb_substr($tags, 0, mb_strlen($tags) - 2) . ".";
	}

	$date = get_real_date($post["time"]);
	if($realtime)
		$date = get_nice_date($post["time"]);

	$meta = "Från <a href='$blogurl' title='Se fler inlägg från bloggen'>$blogname</a>; skrivet $date.$tags";

	global $editor, $self;
	if($editor and $editor->IsLoggedIn())
	{
		$meta .= " <a href='$self?editpost=$post[index]'>Redigera inlägg.</a>";
	}

	$html = generate_post_box("<h3>".generate_icon($post["blog"], $post["blogurl"], $blogurl, $blogname)."<a href='$directurl'>$title</a></h3>$summary<hr /><p class='meta'>$meta</p>");

	return $html;
}

function generate_postlist($posts, $realtime = false)
{
	global $robotvisit;

	$html = "<!-- google_ad_section_start -->";
	$topmost = true;
	foreach(array_values($posts) as $post)
	{
		$html .= generate_spot($post, $realtime);
		if($topmost and !$robotvisit)
		{
			$html .= generate_list_ads();
			$topmost = false;
		}
	}
	$html .= "<!-- google_ad_section_end -->";

	return $html;
}

function generate_bloglist($blogs)
{
	global $robotvisit;

	$html = "<!-- google_ad_section_start -->";
	$topmost = true;
	foreach($blogs as $blog)
	{
		$html .= generate_blogspot($blog);
		if($topmost and !$robotvisit)
		{
			$html .= generate_list_ads();
			$topmost = false;
		}
	}
	$html .= "<!-- google_ad_section_end -->";

	return $html;
}

function get_location($blog)
{
	$location = $blog["location"];
	$matches = array();
	preg_match("/(.*), (.* Län)(: (.*?))??/U", $location, &$matches);
	$city = $matches[1];
	$lan = str_replace("Län", "län", $matches[2]);

	$location = "";
	if($matches[4] != "")
	{
		$detail = $matches[4];
		$location = "$detail, ";
	}
	$location .= "$city (<a href='/blogwalk/location/".urlencode($lan)."' title='Se fler bloggar från detta län'>$lan</a>)";

	return fix_amps($location);
}

function generate_blogspot($blog)
{
	global $self;

	$name = tidy_up_text($blog["name"]);
	if($blog["feedtitle"] != "")
			$name = tidy_up_text($blog["feedtitle"]);

	$description = tidy_up_text($blog["description"]);
	$index = $blog["index"];
	$directurl = fix_amps($blog["url"]);
	$blogurl = "/blogwalk/blog/$index";

	$html = "<h3>".generate_icon($blog["index"], $blog["url"], $blogurl, $name)."<a href='$directurl'>$name</a></h3><p><a href='$directurl' class='neutral'>$description</a></p><p class='meta'>";

	$location = get_location($blog);
	$html .= "$location; <a href='$blogurl' title='Se bloggens senaste inlägg'>läs bloggens inlägg</a></p>";

	return generate_large_box($html);
}

# -------------------------------------------

class BlogHtml
{
	var $name;

	function generate($index)
	{
		global $self;

		$blog = get_blog($index);
		if($blog === false) return false;

		$blogurl = fix_amps($blog["url"]);
		$name = tidy_up_text($blog["name"]);
		if($blog["feedtitle"] != "")
			$name = tidy_up_text($blog["feedtitle"]);

		$this->name = $name;
		$description = tidy_up_text($blog["description"]);
		$lanurl = urlencode($lan);
		$location = get_location($blog);

		$html = "<h2 id='blog'><a href='$blogurl'>$name</a></h2><p id='description'>$location: <em>$description</em></p>";

		$posts = get_latest_posts_from_blog(20, $index);
		add_tags_to_posts(&$posts);

		$lastpost = $posts[0];
		$updated = get_nice_date($lastpost["time"]);

		$html .= "<p>Bloggen <strong>uppdaterades senast $updated</strong>.";
		if($blog["postcount"] > 5)
			$html .= " Det finns $blog[postcount] inlägg från denna blogg på Blogwalk.";
		$html .= "</p>";

		if(count($posts) > 0)
		{
			$html .= "<h2>De senaste inläggen från bloggen:</h2>".generate_postlist($posts);
		}

		return $html;
	}

	function generate_newest_list($count)
	{
		$result = query("SELECT * FROM blog ORDER BY `index` DESC LIMIT $count");

		$html = "<h2>De nyaste bloggarna</h2>
		<p>Dessa är de nyaste bloggarna som pingat Var är du? och sedan plockats upp av Blogwalk. Ibland kan det av tekniska skäl dröja innan din blogg visas här.</p>";

		$posts = array();
		while($row = fetch_array($result))
			array_push($posts, $row);

		$html .= generate_bloglist($posts);

		return $html;
	}
}

# -------------------------------------------

# alla län med befolkning från SCB
$sveriges_lan = array("Stockholms län" => 1880296, "Uppsala län" => 302848, "Södermanlands län" => 261506, "Östergötlands län" => 415713, "Jönköpings län" => 329861, "Kronobergs län" => 178134, "Kalmar län" => 233924, "Gotlands län" => 57639, "Blekinge län" => 150348, "Skåne län" => 1164203, "Hallands län" => 284939, "Västra Götalands län" => 1524203, "Värmlands län" => 273377, "Örebro län" => 273671, "Västmanlands län" => 261142, "Dalarnas län" => 275899, "Gävleborgs län" => 276436, "Västernorrlands län" => 244082, "Jämtlands län" => 127170, "Västerbottens län" => 256734, "Norrbottens län" => 252061);

class LocationHtml
{
	function generate_cloud()
	{
		global $sveriges_lan;

		$html = "<h2>Bloggar ordnade efter län</h2><p>Välj ett län för att se bloggar som hör hemma där. Ju större textstorlek, desto fler bloggar i det länet.</p>";

		$boxhtml = "<ul id='cosmos'>";

		$counts = array();
		foreach($sveriges_lan as $l => $_)
		{
			list($_, $num) = $this->get_blogs_in_area($l, 0);
			$count[$l] = log($num);
		}

		$min = min($count);
		$max = max($count);
		$scale = 17 / ($max - $min);

		$locations = array_keys($sveriges_lan);
		sort($locations);

		foreach($locations as $lan)
		{
			$score = (int) (($count[$lan] - $min) * $scale + 1);
			$url = urlencode($lan);
			$location = mb_substr($lan, 0, mb_strlen($lan) - 4);
			if(string_endswith($location, "s"))
				$location = mb_substr($location, 0, mb_strlen($location) - 1);
			$boxhtml .= "<li class='keyword$score'><a href='/blogwalk/location/$url'>$location</a></li> ";
		}

		$boxhtml .= "</ul>";

		$html .= generate_large_box($boxhtml);

		return $html;
	}

	function generate($location, $count)
	{
		global $sveriges_lan, $stats;

		list($blogs, $num) = $this->get_blogs_in_area($location, $count);
		if(count($blogs) == 0) return false;

		$html = "<h2>Ett urval av bloggar i $location:</h2>
			<p>Detta urval är slumpmässigt och görs om varannan dag.</p>";

		$html .= generate_bloglist($blogs);

		$population = str_replace(",", "&nbsp;", number_format($sveriges_lan[$location]));
		$density = str_replace(",", "&nbsp;", number_format($sveriges_lan[$location] / $num, 0));

		$total_pop = 9014921;
		$total_density = str_replace(",", "&nbsp;", number_format($total_pop / $stats["blogcount"], 0));

		$html .= "<p>Antal bloggar i länet: <strong>$num</strong>. Befolkning: <strong>$population</strong>. Det går $density personer per blogg i detta län, jämfört med $total_density för hela riket.</p>";

		return $html;
	}

	function get_blogs_in_area($location, $count)
	{
		list($blogs, $num) = $this->get_from_cache($location, $count);
		if(count($blogs) > 0) return array($blogs, $num);

		$sqllocation = $location;
		prepare_string($sqllocation);

		$result = query(
			"SELECT `index`,name,description,location,url
			FROM blog
			WHERE
				location LIKE '%$sqllocation%'
				ORDER BY rand()");

		$blogs = array();
		$num = 0;
		while($row = fetch_array($result))
		{
			array_push($blogs, $row);

			$num++;
			if($num == $count) break;
		}

		$this->cache($location, $blogs, mysql_num_rows($result));
		$num = $blogs[0]["num_blogs"];

		return array($blogs, mysql_num_rows($result));
	}

	function get_from_cache($lan, $count)
	{
		$lan = get_lan_index($lan);

		$result = query(
			"SELECT blog.`index`,blog.name,location,description,num_blogs,blog.url
			FROM cache_locationblogs,blog,lan
			WHERE
				cache_locationblogs.lan=$lan AND lan.`index`=$lan AND
				blog.`index`=cache_locationblogs.`index`
				ORDER BY rand()
				LIMIT $count");

		$blogs = array();
		while($row = fetch_array($result))
			array_push($blogs, $row);

		$num = $blogs[0]["num_blogs"];

		return array($blogs, $num);
	}

	function cache($lan, $blogs, $num)
	{
		$lan = get_lan_index($lan);

		$time = time();
		foreach($blogs as $blog)
		{
			$index = $blog["index"];
			query("INSERT INTO cache_locationblogs (lan,`index`,time)
					VALUES ('$lan',$index,$time)", false);
		}

		query("UPDATE lan SET num_blogs='$num' WHERE `index`='$lan'");
	}
}

# -------------------------------------------

class SearchEngineHtml
{
	function generate($query, $count, $postsearch)
	{
		if($postsearch)
			$html = $this->generate_posts($query, $count);
		else
			$html = $this->generate_blogs($query, $count);

		$query = tidy_up_text($query);

		$html .= "<!-- SiteSearch Google -->
			<p>Sök med Google:</p>
			<div class='table'><form method='get' action='http://www.google.se/custom'><table class='center'>
			<tr><td nowrap='nowrap' valign='top' align='left' height='32'>
			<a href='http://www.google.com/'><img src='http://www.google.com/logos/Logo_25wht.gif' alt='Google' /></a>
			<input type='text' name='q' size='35' maxlength='255' value='$query site:daven.se inurl:blogwalk' />
			<button type='submit' name='sa' value='Sök'>Sök</button>
			<input type='hidden' name='client' value='pub-3336537055851942' />
			<input type='hidden' name='forid' value='1' />
			<input type='hidden' name='ie' value='UTF-8' />
			<input type='hidden' name='oe' value='UTF-8' />
			<input type='hidden' name='cof' value='GALT:#999999;GL:1;DIV:#FFFFFF;VLC:999999;AH:center;BGC:FFFFFF;LBGC:FFFFFF;ALC:000080;LC:000080;T:000000;GFNT:999999;GIMP:999999;LH:100;LW:100;L:http://www.daven.se/blogwalk/images/logo.jpg;S:http://www.daven.se/blogwalk;LP:1;FORID:1;' />
			<input type='hidden' name='hl' value='sv'></input>
			</td></tr></table></form></div><!-- SiteSearch Google -->
			<p>De senaste inläggen finns ännu inte med i Googles databas, och endast sammanfattningarna av inläggen söks igenom. <em>inurl</em> gör att sökningen endast omfattar Blogwalk. Ta bort det för att söka igenom hela webben.</p>";

		return $html;
	}

	function generate_blogs($query, $count)
	{
		global $self;

		list($num, $blogs) = $this->search_blogs($query, $count);

		$html = "<p>";

		if(count($blogs) == 0)
			$html .= "Inga bloggar som matchar söksträngen kunde hittas.";
		else
		{
			$html .= "$num";
			if($num == 1)
				$html .= " blogg ";
			else
				$html .= " bloggar ";
			$html .= "matchar söksträngen.";
			if($num > $count)
				$html .= " Visar de $count senaste bloggarna.";
		}

		$query = tidy_up_text($query);
		$html .= " <a href='/blogwalk/?search=$query&amp;type=post'>Leta efter matchande <em>inlägg</em> istället.</a></p>"
			.generate_bloglist($blogs);

		return $html;
	}

	function generate_posts($query, $count)
	{
		global $self;

		list($num, $posts) = $this->search_posts($query, $count);

		$html = "<p>";

		if(count($posts) == 0)
			$html .= "Inga inlägg som matchar söksträngen kunde hittas.";
		else
		{
			$html .= "$num inlägg matchar söksträngen.";
			if($num > $count)
				$html .= " Visar de $count senaste inläggen.";
		}

		$query = tidy_up_text($query);
		$html .= " <a href='/blogwalk/?search=$query&amp;type=blog'>Leta efter matchande <em>bloggar</em> istället.</a></p>"
			.generate_postlist($posts);

		return $html;
	}

	function generate_query($string)
	{
		global $stoplist;

		$in_word = false;
		$in_phrase = false;
		$modified = false;
		$query = "";
		$term = "";
		foreach(mb_string_to_array($string." ") as $char)
		{
			if($in_word)
			{
				if($char == " ")
				{
					$in_word = false;
					$xterm = str_replace(array("*", "+", "-"), "", $term);
					if(!in_array($xterm, $stoplist) and mb_strlen($xterm) > 2)
						$query .= "$term$char";
				}
				else
					$term .= $char;
			}
			elseif($in_phrase)
			{
				if($char == "\"")
				{
					$in_phrase = false;
					$xterm = str_replace(array("\"", "*", "+", "-"), "", $term);
					if(!in_array(mb_substr($term, 2), $stoplist) and mb_strlen($xterm) > 2)
						$query .= "$term$char";
				}
				else
					$term .= $char;
			}
			else
			{
				if($char == "\"")
				{
					$term = "+$char";
					if($modified)
						$term = $char;

					$in_phrase = true;
					$modified = false;
				}
				elseif($char == "-")
				{
					$modified = true;
					$query .= $char;
				}
				elseif($char != " ")
				{
					$term = "+$char";
					if($modified)
						$term = $char;

					$in_word = true;
					$modified = false;
				}
				else
					$query .= $char;
			}
		}

		return $query;
	}

	function search_posts($string, $count)
	{
		$string = $this->generate_query($string);
		return search_posts($string, $count);
	}

	function search_blogs($string, $count)
	{
		$string = $this->generate_query($string);
		return search_blogs($string, $count);
	}
}

# -------------------------------------------
class PostHtml
{
	var $title;

	function detect_misspellings_in_search()
	{
		$refpatterns = array(
			'/(www\.)?google(\.[a-z]{2,3})+.*(\?|&)q=([^&]+).*/i'=> array('Google', 4),
			'/(www\.)?eniro\.se.*(\?|&)q=([^&]+).*/i'=> array('Eniro', 3),
			'/search\.msn(\.[a-z]{2,3})+.*(\?|&)q=([^&]+).*/i'=> array('MSN', 3),
		);

		$html = "";

		$misspellings = array("sudoko" => "sudoku", "soduko" => "sudoku", "satelit" => "satellit", "sattelit" => "satellit", "beatiful" => "beautiful");

		foreach($refpatterns as $keng => $eng)
			if(preg_match($keng, $_SERVER["HTTP_REFERER"], &$matches))
			{
				$query = urldecode($matches[$eng[1]]);
				$query = mb_strtolower(mb_convert_encoding($query, "UTF-8", mb_detect_encoding($query, "UTF-8, iso-8859-1")));

				foreach($misspellings as $wrong => $right)
					if(mb_strpos($query, $wrong) !== false)
						$html = "<div id='search_notice'><strong>Tips:</strong> Ordet du letar efter, <em>$wrong</em>, stavas vanligen <strong><em>$right</em></strong>. <a href='/blogwalk/?search=$right*'>Se fler inlägg på Blogwalk som handlar om <em>$right</em>.</a></div>";

				break;
			}

		return $html;
	}

	function generate($index)
	{
		$calculator = new SimilarityCalculator();

		$post = get_post($index);
		if($post === false) return false;

		$html = $this->detect_misspellings_in_search()
			."<!-- google_ad_section_start -->"
			.$this->generate_post($post);

		$similar_html = "";
		foreach($calculator->get_similar_posts($index, explode(" ", $post["keywords"]), 10) as $post)
			$similar_html .= generate_spot($post);

		$html .= "<hr /><div id='similar_posts'><h2 id='apropos'>Apropå det &hellip;</h2>";
		if($similar_html != "")
			$html .= "<p>Ett urval av inlägg som kanske har med detta att göra.</p><ul>$similar_html</ul>";
		else
			$html .= "<p>Kunde inte hitta några liknande inlägg.</p>";

		return $html."</div><!-- google_ad_section_end -->";
	}

	function generate_post($post)
	{
		global $self, $robotvisit;

		$title = tidy_up_text($post["title"]);
		$blogname = tidy_up_text($post["name"]);
		if($post["feedtitle"] != "")
			$blogname = tidy_up_text($post["feedtitle"]);

		$this->title = "$title (utdrag från $blogname)";

		$posturl = fix_amps($post["url"]);
		$blogdirecturl = fix_amps($post["blogurl"]);
		$podcast = fix_amps($post["podcast"]);
		$blogurl = "/blogwalk/blog/$post[blog]";

		$summary = get_first_paragraphs(tidy_up_text($post["summary"]), 250, 350, "<a href='$posturl' class='neutral'>", "</a>");

		$time = strtotime($post["time"]);
		$week = getWeek($time);
		$year = getYear($time);
		$time = get_nice_date($post["time"]);

		foreach(get_tags_for_post($post["index"]) as $tag)
		{
			$tagurl = urlencode_tag($tag);
			$tag = fix_amps($tag);
			$tags .= "<a href='/blogwalk/tag/$tagurl' title='Se fler inlägg med denna etikett'>$tag</a>, ";
		}
		if(isset($tags))
			$tags = mb_substr($tags, 0, mb_strlen($tags) - 2);

		$html = "<h2 class='post'>".generate_icon($post["blog"], $post["url"], $blogurl, $blogname)."<a href='$posturl'>$title</a></h2><p class='date'>Skrivet $time";

		#if(file_exists("/home/davense/aggregator/clusters/$year{$week}_html"))
		#	$html .= " &ndash; <a href='/blogwalk/zeitgeist/$year$week'>se andra händelser och trender från denna vecka</a>";

		$html .= "</p><blockquote>$summary</blockquote>";

		if($podcast != "")
			$html .= "<p><a href='$podcast' title='Ladda hem ljudfil'><img src='/blogwalk/images/podcast.gif' width='70' height='22' alt='Podcast' /></a></p>";

		$html .= "<p><a href='$blogurl'>Se fler inlägg från bloggen <em>$blogname</em></a> eller <a href='$blogdirecturl'>gå direkt till bloggen</a></p>";

		if(isset($tags))
			$html .= "<p>Etiketter för detta inlägg: <em>$tags</em></p>";

		$html .= generate_ad(mb_strtolower("$title $summary"));

		return $html;
	}
}

# -------------------------------------------

class ContactHtml
{
	function generate_form($email = "", $subject = "")
	{
		global $self;

		return

		"<form action='/blogwalk/contact' method='post'>
			<p><label>Din e-postadress:</label><br /><input type='text' size='50' name='email' value='$email' /></p>
			<p><label for='textbox'>Ditt meddelande:</label><br /><textarea id='textbox' cols='50' rows='20' name='subject'>$subject</textarea></p>
			<p><label for='sendbutton' accesskey='S'></label><input id='sendbutton' class='button' type='submit' value='Skicka' /></p>		
		</form>";
	}

	function send_message($email, $subject)
	{
		$html = "";

		if(!$this->validate_email($email))
			$html = "<h2>Fel</h2><p>Kunde inte verifiera e-postadressen. Kontrollera att du har angivit en giltig adress.</p>".$this->generate_form($email, $subject);
		elseif($subject == "")
			$html = "<h2>Fel</h2><p>Du måste ange ett meddelande.</p>".$this->generate_form($email, $subject);
		else
		{
			$iso_email = utf8_decode($email);
			$iso_subject = str_replace("&amp;", "&", utf8_decode($subject));

			if(@mail("christian@daven.se", "Blogwalk -- kontaktmeddelande", $iso_subject,
			"From: $iso_email\r\n"."X-Mailer: PHP/".phpversion()))
				$html = "<h2>Meddelande skickat</h2><p>Ditt meddelande har nu skickats till mig. Tack!</p>";
			else
				$html = "<h2>Fel</h2><p>Kunde inte skicka meddelande.</p>".$this->generate_form($email, $subject);
		}

		return $html;
	}

	function validate_email($email)
	{
		if(eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email))
		{
			# kollar så att domännamnet som angetts finns i DNS-servrarna
			list($_, $domain) = split("@", $email);
			return getmxrr($domain, $mxhost);
		}
		else
			return false;
	}
}

# -------------------------------------------

class FrontPageHtml
{
	var $posts;
	var $dynamic;

	function generate($count, $offset = -1)
	{
		$this->dynamic = true;
		if($offset == -1 or $offset == "")
		{
			$offset = time();
			$this->dynamic = false;
			$html = "<h2>De senaste inläggen:</h2>";
		}
		else
			$html = "<h2>Inlägg skrivna före ".get_real_date($offset).":</h2>";

		$posts = get_latest_posts($count + 1, $offset);
		add_tags_to_posts(&$posts);

		$firstpost_nextpage = array_pop($posts);
		$this->posts = $posts;

		$html .= generate_postlist($posts, $this->dynamic);

		$lastpost = array_pop($posts);
		$lasttime = strtotime($lastpost["time"]);
		$nexttitle = string_to_url(fix_amps($firstpost_nextpage["title"]));
		$html .= "<!-- google_ad_section_end -->"
			."<p id='nextpage'><a href='/blogwalk/offset/$lasttime/$nexttitle' title='Se äldre inlägg' accesskey='N'>fler inlägg »</a></p>";

		return $html;
	}
}

# -------------------------------------------

class ClusterHtml
{
	function generate_week_overview()
	{
		global $self;
		list($week, $year) = getLastWeekPlusYear();

		for($i = $week; $i > 0; $i--)
			if(file_exists("/home/davense/aggregator/clusters/$year{$i}_html"))
			{
				$date = $this->generate_week_date_span($year, $i);
				$url = "/blogwalk/zeitgeist/$year$i";
				$html .= "<li><a href='$url'>Vecka $i</a>: <cite><a href='$url'>$date</a></cite></li>";
			}

		$html .= "</ul>";
		return $html;
	}

	function generate_admin_overview()
	{
		global $self;
		list($week, $year) = getLastWeekPlusYear();

		$html = "<h2>Veckor med inläggskluster</h2><ul>";

		for($i = $week; $i > 0; $i--)
			if(file_exists("/home/davense/aggregator/clusters/$year$i"))
			{
				$html .= "<li><a href='$self?clusters&amp;week=$year$i'>Vecka $i</a>";
				if(!file_exists("/home/davense/aggregator/clusters/$year{$i}_html"))
					$html .= " (ej bearbetad)";
				$html .= "</li>";
			}

		$html .= "</ul>";
		return $html;
	}

	function generate_admin_week($week)
	{
		global $self;
		$clusters = load_data("/home/davense/aggregator/clusters/$week");

		$html = "<h2>Inläggskluster $week</h2><p>Markera de inlägg som ska ingå i respektive kluster.</p><form action='$self?clusters' method='post'><p><input type='hidden' name='week' value='$week' /></p>";

		$count = 0;
		foreach($clusters as $cluster)
		{
			$html .= "<fieldset>";
			foreach($cluster as $index)
			{
				$post = get_post($index);
				$title = tidy_up_text($post["title"]);
				$summary = tidy_up_text(get_first_words($post["summary"], 500));
				$html .= "<p><input type='checkbox' name='cluster[$count][]' value='$index' /><a href='/blogwalk/post/$index'><strong>$title:</strong></a> <em>$summary</em></p>";
			}

			$html .= "</fieldset>";
			$count++;
		}

		$html .= "<p><button type='submit'>Generera</button></p></form>";

		return $html;
	}

	function generate_week_date_span($year, $week)
	{
		$monday1 = getFirstMondayBeforeDate(gotoWeek($year, $week));
		$monday2 = getFirstMondayBeforeDate(gotoWeek($year, $week + 1)) - 3600 * 24;

		$date2 = strftime("%e %B", $monday2);
		if(getMonth($monday1) == getMonth($monday2))
			$date1 = strftime("%e", $monday1);
		else
			$date1 = strftime("%e %B", $monday1);

		return "$date1 &ndash; $date2";
	}

	function generate_week($week)
	{
		$y = substr($week, 0, 4);
		$w = substr($week, 4);

		if(!is_readable("/home/davense/aggregator/clusters/{$week}_html"))
			return false;

		$date = $this->generate_week_date_span($y, $w);

		return load_data("/home/davense/aggregator/clusters/{$week}_html");
	}

	function compile_week($week, $clusters)
	{
		$html = "";

		foreach($clusters as $cluster)
		{
			$html .= "<div class='cluster'><ul>";

			foreach($cluster as $index)
				$html .= generate_spot(get_post($index));

			$html .= "</ul></div>";
		}

		save_data($html, "/home/davense/aggregator/clusters/{$week}_html");
		return $html;
	}
}

# -------------------------------------------

class StatsHtml
{
	function generate()
	{
		global $stats;
		$sums = $stats;

		$totalaverage = $sums["total"]["count"] / $sums["total"]["days"];

		$sum7 = number_to_string($sums["7"]);
		$average7 = number_to_string($sums["7"] / 7);

		if($average7 < $totalaverage)
		{
			$change = number_to_string(100 * ($totalaverage - $average7) / $totalaverage);
			$changetxt = "$change% färre";
		}
		else
		{
			$change = number_to_string(100 * ($average7 - $totalaverage) / $totalaverage);
			$changetxt = "$change% fler";
		}

		$maxday = strftime("%e %B", $sums["maximum"]["date"]);
		$minday = strftime("%e %B", $sums["minimum"]["date"]);

		$totalaverage = number_to_string($totalaverage);

		$html = "<div id='stats'>
		<p style='margin-top: 0'>De inpingade inläggen fördelas per dag enligt följande diagram (grön prick markerar maximum, röd minimum):</p>

		<p style='text-align: center'><img src='/blogwalk/images/sparkline.png' width='500' height='60' /></p>

		<div class='table'><table class='center'>
			<tr>
				<th>Maximum</th>
				<td>$maxday</td>
				<td>{$sums[maximum][count]} inlägg</td>
			</tr>
			<tr>
				<th>Minimum</th>
				<td>$minday</td>
				<td>{$sums[minimum][count]} inlägg</td>
			</tr>
		</table></div>

		<p>I genomsnitt pingas det in <strong>$totalaverage nya inlägg varje dag</strong>. Den senaste veckan har det pingats in $sum7 nya inlägg ($average7 per dag). Det pingas alltså in <strong>$changetxt inlägg denna vecka</strong> än totalt sett.</p>

		<p>Om man istället delar upp inläggen per vecka ser de 10 senaste veckorna ut så här:</p>

		<p style='text-align: center'><img src='/blogwalk/images/weeks.png' width='87' height='40' /></p>

		<div class='table'><table class='center'>
			<thead>
				<th>Vecka</th>
				<th>Antal inlägg</th>
			</thead>";

		$week0 = getThisWeek() - 10;
		for($i = 0; $i < 10; $i++)
			$html .= "<tr><td>".($week0 + $i)."</td><td>".number_to_string($sums["weeks"][$i])."</td></tr>";

		$html .= "</table></div>";

		$html .= $this->generate_weekdays($sums["days"]);
		$html .= $this->generate_holidays($sums["holiday"], $sums["normalday"]);
		$html .= $this->generate_postsperblog($sums["blogs"]);
		$html .= $this->generate_blogtools();

		$html .= "</div>";

		return generate_list_ads().generate_large_box($html);
	}

	function generate_blogtools()
	{
		$result = query("SELECT blog.url AS url,tool.name AS tool,COUNT(*) AS count FROM blog,tool WHERE blog.tool=tool.`index` GROUP BY tool.`index`");

		$tools = array();
		$translations = array(
			"blogspot.com" => "blogger",
			"typepad.com" => "typepad",
			"blogs.com" => "typepad",
			"http://www.typepad.com/" => "typepad",
			"http://www.movabletype.org/" => "movable type",
			"blogg.se" => "blogsoft",
			"webblogg.se" => "blogsoft",
			"bloggi.se" => "bloggi (plog?)",
			"blogsome.com" => "wordpress",
			"bloggning.se" => "plog",
			"blogspirit.com" => "blogspirit",
			"blogg.passagen.se" => "passagen blogg",
			"blogdrive.com" => "blog drive",
			"s-info.se" => "s-info",
			"blog-city.com" => "blog-city",
		);

		while($row = fetch_array($result))
		{
			$tool = mb_strtolower($row["tool"]);

			if(preg_match("/(.+)([\s-]v?\.?\d|,)/U", $tool, $matches))
				$tool = $matches[1];

			foreach($translations as $log => $nicefmt)
				if($tool == $log)
				{
					$tool = $nicefmt;
					break;
				}

			$tools[trim($tool)] += $row["count"];
		}

		arsort($tools);

		$html = "<h3>Spridning av bloggverktyg</h3>

			<p>De flesta bloggare använder någon form verktyg för att skriva sina inlägg etc. De bloggar som är registrerade på Blogwalk använder följande verktyg:</p>

			<div class='table'><table class='center'>
			<thead>
				<th>Verktyg</th>
				<th>Antal bloggar</th>
			</thead>";

		foreach($tools as $tool => $no)
		{
			if($no == 2) break;

			$tool = tidy_up_text(mb_convert_case($tool, MB_CASE_TITLE));
			if($tool == "") $tool = "okänt";
			$html .= "<tr><td>$tool</td><td>$no</td></tr>";
		}

		$html .= "</table></div>

		<p>Bloggarna har analyserats automatiskt, och förutom att många anger verktyget i en meta-tagg eller med frasen \"powered by\" letas det också efter standard-sökvägar eller cgi-skript för de olika verktygen. Kända blogg-värdar, som blogspot.com används också som identifierare. De värdar som använder ett känt verktyg slås ihop med respektive verktyg i statistiken.</p>";

		return $html;
	}

	function generate_weekdays($weekdays)
	{
		$satsun = $weekdays[6] + $weekdays[7];
		$total = array_sum($weekdays);
		$monfri = $total - $satsun;

		$ratio = number_to_string(100 * ($satsun / 2) / ($monfri / 5));

		$html = "<h3>Veckoslutseffekten</h3>

		<p>Det första diagrammet ovan visar ett tydligt mönster med regelbundna toppar och dalar. Räknar man antal inlägg per veckodag ser man att det på lördagar och söndagar bara skrivs $ratio% så många inlägg som under måndag till fredag. Dessutom minskar antalet inlägg stadigt när veckoslutet närmar sig:</p>

		<p style='text-align: center'><img src='/blogwalk/images/weekdays.png' width='60' height='40' /></p>";

		$mon = $weekdays[1];
		$tue = $weekdays[2];
		$wed = $weekdays[3];
		$thu = $weekdays[4];
		$fri = $weekdays[5];
		$sat = $weekdays[6];
		$sun = $weekdays[7];

		$html .= "<div class='table'><table class='center'>
			<thead>
				<th>Veckodag</th>
				<th>Andel inlägg</th>
			</thead>
			<tr>
				<td>Måndag</td>
				<td>".number_to_string(100 * $mon / $total, 1)." %</td>
			</tr>
			<tr>
				<td>Tisdag</td>
				<td>".number_to_string(100 * $tue / $total, 1)." %</td>
			</tr>
			<tr>
				<td>Onsdag</td>
				<td>".number_to_string(100 * $wed / $total, 1)." %</td>
			</tr>
			<tr>
				<td>Torsdag</td>
				<td>".number_to_string(100 * $thu / $total, 1)." %</td>
			</tr>
			<tr>
				<td>Fredag</td>
				<td>".number_to_string(100 * $fri / $total, 1)." %</td>
			</tr>
			<tr>
				<td>Lördag</td>
				<td>".number_to_string(100 * $sat / $total, 1)." %</td>
			</tr>
			<tr>
				<td>Söndag</td>
				<td>".number_to_string(100 * $sun / $total, 1)." %</td>
			</tr>
			</table></div>";

		return $html;
	}

	function generate_holidays($holidays, $normaldays)
	{
		$meanholiday = number_to_string(array_sum($holidays) / count($holidays));
		$meanother = number_to_string(array_sum($normaldays) / count($normaldays));

		$html .= "<h4>Lediga dagar</h4>

		<p>Även helgdagar som infaller under arbetsveckan påverkar antalet inlägg. I genomsnitt skrivs det $meanholiday inlägg på lediga dagar (helgdagar, klämdagar, lördagar och midsommarafton) jämfört med $meanother inlägg på arbetsdagar.</p>";

		return $html;
	}

	function generate_postsperblog($blogs)
	{
		$min = number_to_string($blogs["min"], 1);
		$max = number_to_string($blogs["max"], 1);

		$E = number_to_string($blogs["E"], 1);

		$html = "<h3>Inlägg per blogg</h3>

		<p>De $blogs[n] bloggar som har pingat in fler än ett inlägg pingar i genomsnitt in <strong>$min&ndash;$max inlägg per vecka</strong> (två standardavvikelser från medelvärdet $E).</p>

		<div class='table'><table class='center'>
			<thead>
				<tr>
					<th>Inlägg per vecka</th>
					<th>Andel bloggar</th>
				</tr>
			</thead>";

		foreach($blogs["ppd_table"] as $txt => $num)
			$html .= "<tr><td>$txt</td><td>".number_to_string(100 * $num / $blogs["n"], 1)." %</td></tr>";

		$html .= "</table></div>

		<p>Totalt $blogs[ones] bloggar har pingat in ett enda inlägg och sedan ingenting på över en månad.</p>";

		return $html;
	}
}

# -------------------------------------------

class RandomHtml
{
	function generate()
	{
		# visar slumpmässiga inlägg från den senaste tiden
		$posts = get_random_posts(20, time() - 3600 * 24 * 60, time() - 3600 * 36, 50);
		# visar en slumpmässig blogg
		$blog = get_random_blog();

		$html = "<h2>Slumpmässigt utvalda inlägg</h2>";

		foreach($posts as $post)
			$html .= $this->generate_post($post);

		return $html;
	}

	function generate_post($post)
	{
		$title = tidy_up_text($post["title"]);
		$text = tidy_up_text(get_first_words($post["summary"], 150)."&nbsp;&hellip;");
		$url = "/blogwalk/post/".$post["index"];

		$time = get_nice_date($post["time"]);

		return "<h3 class='post'><a href='$url'>$title</a></h3><p class='date'>Skrivet $time</p>
			<p><a href='$url' class='neutral'>$text</a></p>";
	}
}

# -------------------------------------------

class TagHtml
{
	function generate_tag($tag, $count)
	{
		$posts = get_posts_with_tag($tag, $count);
		if($posts === false)
			return false;
		add_tags_to_posts(&$posts);

		$tagurl = urlencode_tag(str_replace("&amp;", "&", $tag));

		$html = "<h2>Inlägg med <a href='/blogwalk/tag/'>etiketten</a> <em>$tag</em>:</h2><p class='meta'>"
			.$this->generate_supertag_links($posts[0]["tag"])
			."Du kan också <a href='/blogwalk/tag/$tagurl/feed/' title='Prenumerera via RSS 2.0'>prenumerera på denna etikett</a>.</p>";

		$html .= generate_postlist($posts);

		$html .= $this->generate_external_tag_links($tag);

		return $html;
	}

	function generate_external_tag_links($tag)
	{
		$tag = urlencode($tag);

		$links = load_data("/home/davense/aggregator/cache/{$tag}_links");
		if($links !== false)
		{
			$html = "<h2>Externa länkar med denna etikett</h2><p>Dessa länkar är hämtade från <a href='http://del.icio.us/'>del.icio.us</a> och <a href='http://www.furl.net/'>Furl</a>.</p><ul>";
			foreach($links as $link)
			{
				$url = fix_amps($link["link"]);
				$title = tidy_up_text($link["title"]);
				$html .= "<li><a href='$url'>$title</a></li>";
			}
			$html .= "</ul>";
		}

		$html .= "<h2>Denna etikett på andra webbplatser</h2><ul>"
			."<li><a href='http://www.technorati.com/tag/$tag'>Technorati</a> (bloggar över hela världen)</li>"
			."<li><a href='http://del.icio.us/tag/$tag'>del.icio.us</a> (bokmärken och länktips)</li>"
			."<li><a href='http://www.flickr.com/photos/tags/$tag'>Flickr</a> (bilder och foton)</li>"
			."<li><a href='http://www.intressant.se/tema/$tag/'>Intressant.se</a> (svenska bloggar)</li></ul>";

		return $html;
	}

	function generate_supertag_links($tagindex)
	{
		$result = query("SELECT supertag FROM supertags WHERE tag=$tagindex");
		$html = "";
		while($row = mysql_fetch_array($result))
		{
			$tagurl = urlencode_tag($row["supertag"]);
			$tag = fix_amps($row["supertag"]);
			$html .= "<em><a href='/blogwalk/supertag/$tagurl'>$tag</a></em>, ";
		}
		$html = mb_substr($html, 0, max(0, mb_strlen($html) - 2));

		if($html != "")
		{
			if(mysql_num_rows($result) == 1)
				$html = "Ingår i super-etiketten $html. ";
			else
				$html = "Ingår i super-etiketterna $html. ";
		}

		return $html;
	}

	function generate_cloud($count)
	{
		$tags = get_most_used_tags($count);
		$names = array_keys($tags);
		asort($names);

		# viktar etiketterna (antal bloggar per etikett är mkt viktigare än antal inlägg)
		foreach($names as $tag)
			$tags[$tag] = count_blogs_with_tag($tag) + log($tags[$tag]);

		$min = min($tags);
		$max = max($tags);
		$scale = 17 / ($max - $min);

		$html =
		"<h2>De mest använda etiketterna</h2>
		<p>En etikett (eng. <em>tag</em>) är ungefär som en kategori. Varje inlägg kan dock ha flera etiketter.
		Välj en etikett nedan för att se inlägg som har den etiketten.</p>
		<p>Det finns också ett antal <a href='/blogwalk/supertag/'>super-etiketter på Blogwalk</a>. Dessa omfattar flera liknande etiketter.</p>";

		$boxhtml = "<ul id='cosmos'>";
		foreach($names as $tagname)
		{
			$score = (int) (($tags[$tagname] - $min) * $scale + 1);

			$tag = fix_amps($tagname);
			$tagurl = urlencode_tag($tagname);
			$tag = str_replace(" ", "&nbsp;", $tag);

			$boxhtml .= "<li class='keyword$score'><a href='/blogwalk/tag/$tagurl'>$tag</a></li> ";
		}
		$boxhtml .= "</ul>";

		$html .= generate_large_box($boxhtml);

		$html .= "<p>Etiketterna hämtas direkt från bloggarnas flöden. Om du vill att dina inlägg ska etiketteras på Blogwalk,
		se till att de kategorier, taggar eller etiketter du väljer på din blogg också visas i ditt flöde.</p>";

		return $html;
	}
}

# -------------------------------------------

class ZeitgeistHtml
{
	function generate_main($count)
	{
		list($week,$year) = getLastWeekPlusYear();

		$boxhtml =

		$this->generate_burst_list("live", 10, "<h3>Vanliga ord i inläggen</h3><p>Ord som förekommer väldigt ofta i blogginlägg just nu, tillsammans med ett exempel på användning av ordet. Klicka på ordet för att göra en sökning på Blogwalk, eller på exemplet för att gå till det inlägget.</p>")

/*		.$this->generate_search_list("live", $count, "<h3 class='padtop'>Populära sökfraser</h3>
			<p>Dessa är de vanligaste sökfraserna som de senaste sju dygnen lett besökarna till Blogwalk från olika sökmotorer.
			Välj en fras för att göra en sökning i Blogwalks databas.</p>")
*/

		.$this->generate_link_list("$year$week", "<h3 class='padtop'>Populära länkar</h3>
			<p>Här ser du de vanligast förekommande länkarna i blogginlägg från förra veckan.</p>");

		$html = "<h2>Trendspaning</h2><p>Här ser du trender bland inläggen och besökarna på Blogwalk. Du kan också gå tillbaka i tiden och minnas gamla händelser och trender.</p>"
			.generate_list_ads()
			.generate_large_box($boxhtml);

		return $html;
	}

	function generate_overview()
	{
		$html = "<h3>Historik</h3>
		<p>Välj en vecka för att spana in trenderna under den veckan.</p><ul>";

		$ch = new ClusterHtml();
		$html .= $ch->generate_week_overview();

		return $html;
	}

	function generate_burst_list($file, $count, $header)
	{
		if(!is_readable("/home/davense/aggregator/bursts/$file"))
			return "";

		$html = "$header<ul>";

		$bursts = load_data("/home/davense/aggregator/bursts/$file");
		foreach(array_slice($bursts, 0, $count) as $burst)
		{
			$word = tidy_up_text($burst["word"]);
			$uword = urlencode($word);

			$html .= "<li><a href='/blogwalk/?search=$word'>$word</a>";
/*
			$extext = tidy_up_text($burst["eg"]["phrase"]);
			$exlink = "/blogwalk/post/".$burst["eg"]["index"];

			if($extext !== "")
				$html .= " (<em><a href='$exlink' class='neutral'>&rdquo;$extext&rdquo;</a></em>)";
*/
			$html .= "</li>";
		}

		$html .= "</ul>";
		return $html;
	}

	function generate_search_list($week, $count, $heading = "")
	{
		if(!is_readable("/home/davense/aggregator/searches/$week"))
			return "";

		$html = $heading;
		if($heading == "")
			$html = "<h3 class='padtop'>Populära sökfraser</h3>
			<p>Dessa är de vanligaste sökfraserna som under veckan lett besökarna till Blogwalk från olika sökmotorer.
			Välj en fras för att göra en sökning i Blogwalks databas.</p>";

		$html .= "<ul>";
		foreach(array_slice(load_data("/home/davense/aggregator/searches/$week"), 0, $count) as $search => $array)
		{
			$search = tidy_up_text($search);

			$variants = explode(":", $search);
			$html .= "<li>";
			foreach($variants as $variant)
			{
				$uword = urlencode($variant);
				$html .= "<a href='/blogwalk/?search=$uword'>$variant</a>, ";
			}

			$html = mb_substr($html, 0, mb_strlen($html) - 2);

			$html .= "</li>";
		}
		$html .= "</ul>";
		return $html;
	}

	function generate_week_date_span($year, $week)
	{
		$monday1 = getFirstMondayBeforeDate(gotoWeek($year, $week));
		$monday2 = getFirstMondayBeforeDate(gotoWeek($year, $week + 1)) - 3600 * 24;

		$date2 = strftime("%e %B %Y", $monday2);
		if(getMonth($monday1) == getMonth($monday2))
			$date1 = strftime("%e", $monday1);
		else
			$date1 = strftime("%e %B", $monday1);

		return "$date1 &ndash; $date2";
	}

	function generate_link_list($week, $heading = "")
	{
		if(!is_readable("/home/davense/aggregator/links/{$week}_done"))
			return "";

		$html = $heading;
		if($heading == "")
			$html = "<h3 class='padtop'>Populäraste länkarna</h3>
			<p>Här ser du de vanligast förekommande länkarna i blogginlägg under denna vecka.</p>";

		$html .= "<ul>";
		foreach(array_slice(load_data("/home/davense/aggregator/links/{$week}_done"), 0, 20) as $link)
		{
			$title = fix_amps($link["title"]);
			$url = fix_amps($link["url"]);
			$html .= "<li><a href='$url'>$title</a></li>";
		}
		$html .= "</ul>";
		return $html;
	}

	function generate_week($week, $count)
	{
		$y = substr($week, 0, 4);
		$w = substr($week, 4);

		$date = $this->generate_week_date_span($y, $w);

		$html =

		"<h2>Trendspaning vecka $w</h2>
		<p class='date'>$date</p>"

		.$this->generate_burst_list($week, 10, "<h3>Vanliga ord i inläggen</h3><p>Ord som förekom väldigt ofta i blogginlägg denna vecka. Klicka på ordet för att göra en sökning på Blogwalk, eller på exemplet för att gå till det inlägget.</p>")

		.$this->generate_search_list($week, $count)
		.$this->generate_link_list($week);

#		."<h3>Omskrivet i bloggarna</h3>
#		<p>Dessa inlägg berör händelser som har nämnts i fler än en blogg. Urvalet av inlägg är automatgenererat (och sedan manuellt modererat).
#		Viktiga händelser kan ha utelämnats på grund av brister i algoritmen.</p>";

#		$ch = new ClusterHtml();
#		$chtml = $ch->generate_week($week);
#		if($chtml === false)
#			return false;

		return $html.$chtml;
	}
}

# -------------------------------------------

class LinksHtml
{
	function generate($week)
	{
		global $self;

		$html = "<h2>Länklista vecka $week</h2>
		<p>Kontrollera URL och fyll eventuellt i saknade titlar. Töm URL:en för en rad för att ta bort den ur listan.</p>

		<form action='$self?links' method='post'>
		<p><input type='hidden' name='week' value='$week' /></p>";

		foreach(load_data("/home/davense/aggregator/links/$week") as $link)
		{
			$title = tidy_up_text(mb_convert_encoding($link["title"], "UTF-8", mb_detect_encoding($link["title"], "UTF-8, iso-8859-1")));
			$url = tidy_up_text($link["url"]);
			$html .= "<p><input type='text' name='links[url][]' size='30' value='http://$url' />
			<input type='text' name='links[title][]' size='30' value='$title' /></p>";
		}

		$html .= "<p><input type='submit' value='Spara' /></p></form>";
		return $html;
	}

	function compile($week, $links)
	{
		$list = array();
		foreach($links["url"] as $index => $url)
		{
			if($url == "") continue;

			array_push($list, array("url" => $url, "title" => stripslashes($links["title"][$index])));
		}
		save_data($list, "/home/davense/aggregator/links/{$week}_done");
	}

	function generate_overview()
	{
		global $self;
		list($week, $year) = getLastWeekPlusYear();

		$html = "<h2>Veckor med länklistor</h2><ul>";

		for($i = $week; $i > 0; $i--)
			if(file_exists("/home/davense/aggregator/links/$year$i"))
			{
				$html .= "<li><a href='$self?links&amp;week=$year$i'>Vecka $i</a>";
				if(!file_exists("/home/davense/aggregator/links/$year{$i}_done"))
					$html .= " (ej bearbetad)";
				$html .= "</li>";
			}

		$html .= "</ul>";
		return $html;
	}
}

# -------------------------------------------

class SuperTagHtml
{
	function generate_overview()
	{
		$html = "<h2>Super-etiketter</h2>
			<p>En super-etikett inkluderar alla inlägg som är märkta med någon av dess sub-etiketter. Du kan
			antingen läsa inläggen här, eller prenumerera på etiketterna via RSS-flöden.</p>
			<p>Om du saknar en super- eller sub-etikett som skulle passa in här, kontakta mig.</p>";

		foreach(get_supertags() as $supertag)
		{
			$tagurl = urlencode_tag($supertag);
			$tag = fix_amps($supertag);
			$boxhtml = "<h3><a href='/blogwalk/supertag/$tagurl'>$tag</a></h3><p>Inkluderar etiketterna ";
			foreach(get_subtags($supertag) as $subtag)
			{
				$tagurl = urlencode_tag($subtag["name"]);
				$tag = fix_amps($subtag["name"]);
				$boxhtml .= "<em><a href='/blogwalk/tag/$tagurl'>$tag</a></em>, ";
			}
			$boxhtml = mb_substr($boxhtml, 0, mb_strlen($boxhtml) - 2);
			$boxhtml .= "</p>";

			$html .= generate_large_box($boxhtml);
		}

		return $html;
	}

	function generate_tag($supertag, $count)
	{
		$posts = get_posts_with_supertag($supertag, $count);
		if($posts === false)
			return false;
		add_tags_to_posts(&$posts);

		$tagurl = urlencode_tag(str_replace("&amp;", "&", $supertag));
		$html = "<h2>Inlägg med <a href='/blogwalk/supertag/'>super-etiketten</a> <em>$supertag</em>:</h2>"
			."<p class='meta'>Du kan också <a href='/blogwalk/supertag/$tagurl/feed/' title='Prenumerera via RSS 2.0'>prenumerera på denna super-etikett</a>.<br />"
			."Denna super-etikett inkluderar etiketterna ";
		foreach(get_subtags($supertag) as $subtag)
		{
			$tagurl = urlencode_tag($subtag["name"]);
			$tag = tidy_up_text($subtag["name"]);
			$html .= "<em><a href='/blogwalk/tag/$tagurl'>$tag</a></em>, ";
		}
		$html = mb_substr($html, 0, mb_strlen($html) - 2).".</p>";

		$html .= generate_postlist($posts);

		return $html;
	}
}

?>

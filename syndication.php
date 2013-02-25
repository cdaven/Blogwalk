<?

function get_last_modified($posts)
{
	$timestamp = 0;
	foreach($posts as $post)
	{
		$time = strtotime($post["time"]);
		$timestamp = max($time, $timestamp);
	}
	return $timestamp;
}

function generate_supertag_rss($tag, $count)
{
	$posts = get_posts_with_supertag($tag, $count);
	if($posts === false)
		return false;

	$file = urlencode($tag);
	return _generate_rss_through_cache($posts, "tag_{$file}_rss", "Blogwalk ($tag)", "De senaste inläggen från svenska bloggar (med super-etiketten $tag)", false);
}

function generate_tag_rss($tag, $count)
{
	$posts = get_posts_with_tag($tag, $count);
	if($posts === false)
		return false;

	$file = urlencode($tag);
	return _generate_rss_through_cache($posts, "tag_{$file}_rss", "Blogwalk ($tag)", "De senaste inläggen från svenska bloggar (med etiketten $tag)", false);
}

function _generate_rss_through_cache($posts, $cachefile, $title = "Blogwalk", $description = "De senaste inläggen från svenska bloggar", $similar = true)
{
	$timestamp = get_last_modified($posts);
	doConditionalGet($timestamp);

	header('Content-Type: application/rss+xml;charset=UTF-8');

	$cache = load_data("/home/davense/aggregator/cache/$cachefile");
	if($cache === false or $cache["timestamp"] < $timestamp)
	{
		$rss = generate_rss($posts, $title, $description, $similar);
		$cache["feed"] = $rss;
		$cache["timestamp"] = $timestamp;
		save_data($cache, "/home/davense/aggregator/cache/$cachefile");
	}
	else
		$rss = $cache["feed"];

	return $rss;
}

function generate_newest_rss($count)
{
	$posts = get_latest_posts($count, time());
	return _generate_rss_through_cache($posts, "newest_rss");
}

function _generate_spot($post)
{
	$title = tidy_up_text($post["title"]);
	$summary = tidy_up_text(get_first_words($post["summary"], 200)."&nbsp;&hellip;");
	$url = tidy_up_text($post["url"]);

	$blogname = tidy_up_text($post["name"]);
	$blogurl = "http://www.blogwalk.se/blog/$post[blog]";

	$html = "<h4><a href='$url'>$title</a></h4><p><em>$summary</em> (Från <a href='$blogurl'>$blogname</a>.)</p>";

	return $html;
}

$calculator = new SimilarityCalculator();
function generate_similar_posts($post)
{
	global $calculator;

	$similar = $calculator->get_similar_posts($post["index"], explode(" ", $post["keywords"]), 3, false);
	if(count($similar) > 0)
	{
		$html = "<hr /><h3><b>Apropå det ...</b></h3>"
			."<p>De här inläggen från Blogwalk kanske också är intressanta:</p>";
		foreach($similar as $spost)
		{
			$html .= _generate_spot($spost);
		}
		$url = "http://www.blogwalk.se/post/$post[index]/".string_to_url($post["title"]);
		$html .= "<p><a href='$url'><b>Se ännu fler liknande inlägg på Blogwalk.</b></a></p>";
	}
	return $html;
}

# RSS 2.0
# http://blogs.law.harvard.edu/tech/rss

function generate_rss($posts, $title = "Blogwalk", $description = "De senaste inläggen från svenska bloggar", $similar = true)
{
	$url = "http://www.blogwalk.se/";
	$timestamp = date("r", get_last_modified($posts));

	$xml = "<?xml version='1.0' encoding='utf-8'?>\n<rss version='2.0' xmlns:content='http://purl.org/rss/1.0/modules/content/'><channel><title>$title</title><link>$url</link><description>$description</description><pubDate>$timestamp</pubDate><copyright>http://creativecommons.org/licenses/by-nc/2.0/</copyright>\n";

	foreach($posts as $post)
	{
		$title = stripslashes(tidy_up_text($post["title"]));

		$htmlsummary = stripslashes(get_first_paragraphs(tidy_up_text($post["summary"]), 300, 400));
		$plainsummary = clean_up_xml_text(str_replace("\n", " ", get_first_words($post["summary"], 400))." ...");

		$posturl = tidy_up_text($post["url"]);
#		$bwposturl = "{$url}post/$post[index]/".string_to_url($post["title"]);
		$bwblogurl = "{$url}blog/$post[blog]";
		$guid = "Blogwalk Post #".$post["index"];
		$time = date("r", strtotime($post["time"]));
		$blog = tidy_up_text($post["name"]);

		$metadata = "Från <a href='$bwblogurl' title='Se fler inlägg från denna blogg'>$blog</a>.";

		$tags = "";
		foreach(get_tags_for_post($post["index"]) as $tag)
		{
			$tagurl = "{$url}tag/".urlencode_tag($tag);
			$tag = tidy_up_text($tag);
			$tags .= "<a href='$tagurl' title='Se fler inlägg med denna etikett'>$tag</a>, ";
		}
		if($tags != "")
		{
			$tags = mb_substr($tags, 0, mb_strlen($tags) - 2);
			$metadata .= " Etiketter för inlägget: $tags.";
		}

/*		if($similar)
			$similar = generate_similar_posts($post);
		else
			$metadata .= " <a href='$bwposturl'>Se liknande inlägg på Blogwalk</a>.";
*/

		$xml .= "<item><title>$title</title><link>$posturl</link><guid isPermaLink='false'>$guid</guid><pubDate>$time</pubDate>"
			."<content:encoded><![CDATA[$htmlsummary"
			."<p><em>$metadata</em></p>]]></content:encoded>"
			."<description>$plainsummary</description>"
			."</item>\n";
	}

	$xml .= "</channel></rss>";

	return $xml;
}

function clean_up_xml_text($text)
{
	$text = strip_tags($text);
	$text = decode_html_entities($text);
	$text = tidy_up_text($text);
	$text = stripslashes($text);
	return $text;
}

?>

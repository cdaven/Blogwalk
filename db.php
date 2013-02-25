<?

mysql_connect("localhost", "databasnamn", "databaslösenord") or
	die("Kan inte koppla upp mot databasen; ".mysql_error());
mysql_select_db("databasnamn") or
	die("Kan inte välja databasen; ".mysql_error());

# -------------------------------------------

function query($query, $die = true)
{
	global $querycount;
	$querycount++;

	$result = mysql_query($query);
	if($die and $result === false)
		die("Kan inte köra frågan '$query' på databasen; ".mysql_error());

	return $result;
}

# förbereder sträng för att sättas in i databasen
function prepare_string(&$string)
{
	$string = utf8_to_code(fix_utf8($string));
	if(strpos($string, "\'") == false)
		# escape:ar strängen bara om den inte redan är det
		$string = mysql_escape_string($string);
	return $string;
}

# omvandlar specialkod till UTF-8
function fetch_array($result)
{
	$row = mysql_fetch_assoc($result);
	if($row === false) return false;

	$array = array();
	foreach($row as $key => $val)
		$array[$key] = code_to_utf8($val);

	return $array;
}

function is_blog_already_in_table($url)
{
	if(get_blog_index($url) === false)
		return false;
	else
		return true;
}

function get_blog_index($url)
{
	$array = mysql_fetch_row(query("SELECT `index` FROM blog WHERE url='$url' LIMIT 1"));
	if(isset($array[0]))
		return $array[0];
	else
		return false;
}

# blockerade bloggar, identifierade med URL
$blocked = array(

);

function add_blog($name, $url, $description, $location, $feed)
{
	global $blocked;

	# lagrar inte bloggar utan namn eller URL
	if($name == "" or $name == " " or $url == "") return false;

	if(in_array($url, $blocked))
	{
		echo "blocked $url\n";
		return false;
	}

	prepare_string($name);
	prepare_string($description);
	prepare_string($url);
	prepare_string($location);
	prepare_string($feed);

	$query = "INSERT INTO blog (name,description,location,url,feedurl) VALUES ('$name','$description','$location','$url','$feed')";
	if(is_blog_already_in_table($url))
		# uppdaterar information för bloggen som finns på adressen $url
		$query = "UPDATE blog SET name='$name',description='$description',location='$location',feedurl='$feed' WHERE url='$url'";

	query($query);
	return true;
}

function update_blog($index, $name, $description)
{
	prepare_string($name);
	prepare_string($description);

	query("UPDATE blog SET name='$name',description='$description' WHERE `index`=$index");
}

function count_posts_from_blog($index)
{
	$row = mysql_fetch_row(query(
		"SELECT COUNT(*) AS count
		FROM blog,post
		WHERE
			blog.`index`=$index AND
			blog.`index`=post.blog
			GROUP BY blog.`index`"));

	return $row[0];
}

function get_blog($index)
{
	$row = fetch_array(query(
		"SELECT name,description,location,blog.url,feedtitle,COUNT(*) AS count
		FROM blog,post
		WHERE
			blog.`index`=$index AND
			blog.`index`=post.blog
			GROUP BY blog.`index`"));

	if($row === false) return false;

	return array(
		"name" => $row["name"],
		"description" => $row["description"],
		"location" => $row["location"],
		"url" => $row["url"],
		"postcount" => $row["count"],
		"feedtitle" => $row["feedtitle"]
	);
}

# hämtar en slumpvis utvald blogg med fler än ett inlägg
function get_random_blog()
{
	$row = fetch_array(query(
		"SELECT name,description,location,blog.url,COUNT(*) AS count
		FROM blog,post
		WHERE
			blog.`index`=post.blog
			GROUP BY blog.`index`
			HAVING count > 1
			ORDER BY rand() LIMIT 1"));

	if($row === false) return false;

	return array(
		"name" => $row["name"],
		"description" => $row["description"],
		"location" => $row["location"],
		"url" => $row["url"],
		"postcount" => $row["count"],
	);
}

function get_last_post($blogindex)
{
	return fetch_array(query(
		"SELECT title,time,url
		FROM post
		WHERE
			blog=$blogindex
			ORDER BY time DESC
			LIMIT 1"));
}

function get_post_index($url)
{
	$array = mysql_fetch_row(query("SELECT `index` FROM post WHERE url='$url' LIMIT 1"));
	if(isset($array[0]))
		return $array[0];
	else
		return false;
}

function get_first_post($blogindex)
{
	return fetch_array(query(
		"SELECT title,time,url
		FROM post
		WHERE
			blog=$blogindex
			ORDER BY time ASC
			LIMIT 1"));
}

function merge_blogs($fromblog, $toblog)
{
	$result = query("SELECT `index` FROM post WHERE blog=$fromblog");
	while($row = mysql_fetch_row($result))
		query("UPDATE post SET blog=$toblog WHERE `index`=$row[0]");

	query("DELETE FROM blog WHERE `index`=$fromblog");
}

# kollar om det finns annat inlägg från samma blogg
# med identisk rubrik eller sammanfattning inom det gångna dygnet.
# returvärden:
#  * index	om inlägget är en "ny" dublett, dvs. en uppdatering
#  * -1		om inlägget är en "gammal" dublett
#  * 0		om inlägget inte har någon dublett
function is_duplicate($blog, $title, $summary, $time)
{
	$time_min = strftime("%Y%m%d%H%M%S", time() - 3600 * 24);
	$query =
		"SELECT `index`,time FROM post
		WHERE
			time > $time_min
			AND post.blog = $blog
			AND (title = '$title' OR summary = '$summary')";

	$row = fetch_array(query($query));
	if(isset($row["index"]))
	{
		if($time > $row["time"])
			return $row["index"];
		else
			return -1;
	}

	return 0;
}

function tidy_up_summary($summary)
{
	# kortar ned extremt långa inläggstexter av flera skäl:
	#  - de tar längre tid att söka igenom
	#  - de tar mer utrymme på disk
	#  - de har större sannolikhet att dyka upp i en godtycklig sökning
	$summary = mb_substr($summary, 0, 2000);

	# tar bort Flickr-koder från inlägg som visar Flickr-bilder
	$summary = preg_replace("/\.flickr-\w+ \{.*(\}|$)/U", "", $summary);

	# tar bort vanliga men felaktiga "&#160"-koder från ex. Blogspot
	$summary = preg_replace("/&(amp;)?#160(?!;)/", " ", $summary);

	return $summary;
}

function add_post($blog_url, $title, $summary, $time, $url)
{
	# plockar ut Technorati-taggar ur flödestexten
	$tags = array();
	if(preg_match("/.*?Technorati tags: (([^ \n\r]+ *-? *)+)$/", $summary, $matches) == 1)
	{
		$tags = preg_replace("/\s+-?(\s+)?/", "|", mb_strtolower($matches[1]));
		$tags = explode("|", str_replace("+", " ", $tags));
	}

	$summary = tidy_up_summary($summary);

	# ignorerar inlägg utan titel, URL eller sammanfattning
	if($title == "" or $url == "" or $summary == "" or $summary == "...") return false;
	# ignorerar inlägg med extremt korta texter
	if(mb_strlen($title) + mb_strlen($summary) < 15) return false;
	# ignorerar inlägg som är Flickr-test
	if(string_startswith($summary, "This is a test post from")) return false;
	# ignorerar hemliga inlägg som kräver lösenord
	if(string_startswith($summary, "Hemligt inlägg som du behöver lösenord för att kunna läsa")) return false;
	if(string_startswith($summary, "This post is password protected")) return false;
	if(string_startswith($summary, "Detta inlägg är lösenordsskyddat")) return false;
	if(string_startswith($summary, "Detta inl&auml;gg &auml;r l&ouml;senordsskyddat")) return false;

	prepare_string($title);
	prepare_string($summary);
	prepare_string($url);
	prepare_string($blog_url);

	$time = parse_timestamp($time);

	$blog = get_blog_index($blog_url);
	if($blog === false) die("Den angivna bloggen för detta inlägg existerar inte i databasen!");

	# är inlägget en dublett?
	$dupe = is_duplicate($blog, $title, $summary, $time);

	if($dupe > 0)
		query("UPDATE post SET time='$time',url='$url' WHERE `index`=$dupe");
	elseif($dupe == 0)
		query("INSERT INTO post (title,summary,time,url,blog)
			VALUES ('$title','$summary','$time','$url','$blog')", false);
	else
		return false; # en "gammal" dublett

	if(count($tags) > 0)
		foreach($tags as $tag)
			add_tag_to_post($url, $tag);

	return true;
}

function remove_post($index)
{
	query("DELETE FROM post WHERE `index`=$index");
}

function remove_blog($index)
{
	query("DELETE FROM post WHERE blog=$index");
	query("DELETE FROM blog WHERE `index`=$index");
}

function update_post($index, $title, $summary)
{
	prepare_string($title);
	prepare_string($summary);

	query("UPDATE post SET title='$title',summary='$summary' WHERE `index`=$index");
}

function get_post($index)
{
	$result = query(
		"SELECT post.`index`,title,summary,time,blog,name,location,post.url,
			blog.url AS blogurl,podcast.url AS podcast,keywords,feedtitle
		FROM post,blog
		LEFT JOIN podcast
			ON post.`index`=podcast.post
		WHERE
			post.`index`=$index AND
			post.blog=blog.`index`");

	$row = fetch_array($result);
	if($row === false) return false;

	return $row;
}

function add_lan($lan)
{
	prepare_string($lan);
	query("INSERT INTO lan (name) VALUES ('$lan')", false);
}

function get_lan_index($lan)
{
	prepare_string($lan);
	$row = mysql_fetch_row(query("SELECT `index` FROM lan WHERE name='$lan'"));
	return $row[0];
}

function get_blogposts_from_cache($count, $blog)
{
	$result = query(
		"SELECT `index`,title,summary,url,post.time,blog
		FROM cache_blogposts,post
		WHERE
			cache_blogposts.blog=$blog AND
			post.`index`=cache_blogposts.post
			ORDER BY post.time DESC
			LIMIT $count");

	$posts = array();
	while($row = fetch_array($result))
		array_push($posts, $row);

	return $posts;
}

function get_latest_posts($count, $offset)
{
	$offset = strftime("%Y%m%d%H%M%S", $offset);

	$result = query(
		"SELECT post.`index`,title,summary,post.url,time,blog,name,blog.url AS blogurl,keywords,feedtitle
		FROM post,blog
		WHERE
			post.blog=blog.`index`
			AND post.time < $offset
		ORDER BY time DESC
		LIMIT $count");

	$posts = array();
	while($row = fetch_array($result))
		array_push($posts, $row);

	return $posts;
}

# hämtar etiketter för inläggen (inte exakt de valda inläggen ... [bugg?])
function add_tags_to_posts(&$posts)
{
	$result = query(
		"SELECT post.`index`,tag.name AS tag
		FROM tag,
			post JOIN post_tags ON post.`index` = post_tags.post
		WHERE
			tag.`index` = post_tags.tag
		ORDER BY time DESC
		LIMIT " . count($posts) * 3);

	while($row = fetch_array($result))
	{
		for($i = 0; $i < count($posts); $i++)
			if($posts[$i]["index"] == $row["index"])
			{
				if(isset($posts[$i]["tags"]))
					array_push($posts[$i]["tags"], $row["tag"]);
				else
					$posts[$i]["tags"] = array($row["tag"]);

				break;
			}
	}
}

function get_latest_posts_from_blog($count, $blog)
{
	$result = query(
		"SELECT post.`index`,title,summary,post.url,time,post.blog,blog.name,blog.url AS blogurl,feedtitle
		FROM post,blog
		WHERE
			post.blog=blog.`index` AND blog=$blog
		ORDER BY time DESC
		LIMIT $count");

	$posts = array();
	while($row = fetch_array($result))
		array_push($posts, $row);

	return $posts;
}

function get_random_posts_from_blog($count, $blog)
{
	# försöker hämta från cache
	$cache = get_blogposts_from_cache($count, $blog);
	if(count($cache) > 0) return $cache;

	$result = query(
		"SELECT `index`,title,summary,time,url,blog
		FROM post
		WHERE
			blog=$blog
		ORDER BY rand()
		LIMIT $count");

	$time = time();
	$posts = array();
	while($row = fetch_array($result))
	{
		array_push($posts, $row);

		# cachar inläggen
		query("INSERT INTO cache_blogposts (blog,post,time)
				VALUES ($blog,$row[index],$time)");
	}

	return $posts;
}

# hämtar $count slumpmässiga inlägg från specificerat tidsintervall
function get_random_posts($count, $time_low, $time_high = 0, $min_length = 0)
{
	$time_min = strftime("%Y%m%d%H%M%S", $time_low);

	$query =
		"SELECT post.`index`,title,summary,time,post.url,blog,name
		FROM post,blog
		WHERE
			post.blog = blog.`index`
			AND CHAR_LENGTH(summary) >= $min_length
			AND time >= $time_min ";

	if($time_high > 0)
	{
		$time_max = strftime("%Y%m%d%H%M%S", $time_high);
		$query .= "AND time <= $time_max ";
	}

	# rand() bör INTE användas på stora datamängder då den är långsam
	$query .= " ORDER BY rand() LIMIT $count";

	$posts = array();
	$result = query($query);
	while($row = fetch_array($result))
		array_push($posts, $row);

	return $posts;
}

# söker bland alla inläggen
function search_posts($qstring, $count, $exclude_post_id = -1)
{
	prepare_string($qstring);

	$query = 
		"SELECT post.`index`,title,summary,time,post.url,blog,blog.name,blog.url AS blogurl,feedtitle,
			MATCH (title,summary) AGAINST ('$qstring' IN BOOLEAN MODE) AS score
		FROM post,blog
		WHERE
			post.blog=blog.`index`
			AND MATCH (title,summary) AGAINST ('$qstring' IN BOOLEAN MODE) ";

	if($exclude_post_id > 0)
		$query .= "AND post.`index` != $exclude_post_id ";

	$query .= "ORDER BY score DESC, time DESC";

	$num = 0;
	$posts = array();
	$result = query($query);
	while($row = fetch_array($result))
	{
		array_push($posts, $row);

		$num++;
		if($num == $count) break;
	}

	return array(mysql_num_rows($result), $posts);
}

# söker bland alla bloggarna
function search_blogs($qstring, $count)
{
	prepare_string($qstring);

	$query = 
		"SELECT `index`,name,description,url,feedtitle,location,
			MATCH (name,description) AGAINST ('$qstring' IN BOOLEAN MODE) AS score
		FROM blog
		WHERE
			MATCH (name,description) AGAINST ('$qstring' IN BOOLEAN MODE)
			ORDER BY score DESC";

	$num = 0;
	$blogs = array();
	$result = query($query);
	while($row = fetch_array($result))
	{
		array_push($blogs, $row);

		$num++;
		if($num == $count) break;
	}

	return array(mysql_num_rows($result), $blogs);
}

function add_podcast($posturl, $podcasturl)
{
	$index = get_post_index($posturl);
	query("INSERT INTO podcast (post,url) VALUES ($index,'$podcasturl')", false);
}

function get_most_used_tags($count)
{
	$result = query(
		"SELECT name,COUNT(*) AS count
		FROM tag,post,post_tags
		WHERE
			post_tags.post = post.`index`
			AND post_tags.tag = tag.`index`
		GROUP BY name
		ORDER BY count DESC
		LIMIT $count");

	$tags = array();
	while($row = fetch_array($result))
		$tags[$row["name"]] = $row["count"];

	return $tags;
}

function count_blogs_with_tag($tag)
{
	$tag = str_replace("&amp;", "&", mysql_escape_string($tag));
	$result = query(
		"SELECT *
		FROM post,post_tags,blog,tag
		WHERE
			tag.name = '$tag'
			AND tag.`index` = post_tags.tag
			AND post.`index` = post_tags.post
			AND post.blog = blog.`index`
		GROUP BY blog");

	return mysql_num_rows($result);
}

function get_all_tags()
{
	$result = query("SELECT name FROM tag");

	$tags = array();
	while($row = fetch_array($result))
		array_push($tags, $row["name"]);

	return $tags;
}

function get_tag_index($tag)
{
	$tag = str_replace("&amp;", "&", mysql_escape_string($tag));
	$row = mysql_fetch_row(query("SELECT `index` FROM tag WHERE name='$tag'"));
	if(isset($row[0]))
		return $row[0];
	else
		return false;
}

function count_posts_with_tag($tag)
{
	# används ej, osäker på om koden stämmer

	$tag = str_replace("&amp;", "&", mysql_escape_string($tag));
	$row = mysql_fetch_row(query(
		"SELECT COUNT(*)
		FROM post_tags,tag
		WHERE
			tag.name != '$tag'
			AND tag.`index` = post_tags.tag"));

	return $row[0];
}

function get_posts_with_tag($tag, $count = 10)
{
	$tag = str_replace("&amp;", "&", mysql_escape_string($tag));
	$result = query(
		"SELECT post.`index`,title,summary,time,post.url,blog,blog.name,keywords,post_tags.tag,feedtitle
		FROM post,post_tags,blog,tag
		WHERE
			post.`index` = post_tags.post
			AND tag.name = '$tag'
			AND tag.`index` = post_tags.tag
			AND post.blog = blog.`index`
			ORDER BY time DESC
			LIMIT $count");

	$posts = array();
	while($row = fetch_array($result))
		array_push($posts, $row);

	if(count($posts) == 0)
		return false;
	else
		return $posts;
}

function get_posts_with_supertag($supertag, $count = 10)
{
	return get_posts_with_tags(get_subtags($supertag), $count);
}

function add_subtag($supertag, $subtag)
{
	$index = get_tag_index($subtag);
	$supertag = mysql_escape_string($supertag);
	query("INSERT INTO supertags (supertag,tag) VALUES ('$supertag','$index')");
}

function get_supertags()
{
	$tags = array();
	$result = query("SELECT DISTINCT supertag FROM supertags");
	while($row = mysql_fetch_array($result))
		array_push($tags, $row["supertag"]);
	natsort($tags);
	return $tags;
}

function get_subtags($supertag)
{
	$tag = str_replace("&amp;", "&", mysql_escape_string($supertag));
	$result = query("SELECT tag.name,tag.`index` FROM supertags,tag WHERE supertag='$tag' AND supertags.tag=tag.`index`");
	if(mysql_num_rows($result) == 0)
		return false;

	$subtags = array();
	while($row = mysql_fetch_array($result))
		array_push($subtags, $row);
	natsort($subtags);
	return $subtags;
}

function get_posts_with_tags($tags, $count = 10)
{
	$indices = "";
	foreach($tags as $tag)
		$indices .= "post_tags.tag = $tag[index] OR ";

	$indices = substr($indices, 0, strlen($indices) - 4);

	$result = query(
		"SELECT DISTINCT post.`index`,title,summary,time,post.url,blog,blog.name,keywords,feedtitle
		FROM post,post_tags,blog
		WHERE
			post.blog = blog.`index`
			AND post_tags.post = post.`index`
			AND ($indices)
			ORDER BY time DESC
			LIMIT $count");

	$posts = array();
	while($row = fetch_array($result))
		array_push($posts, $row);

	return $posts;
}

function get_tags_for_post($index)
{
	$result = query(
		"SELECT tag.name
		FROM tag,post_tags
		WHERE
			post_tags.post = $index
			AND post_tags.tag = tag.`index`");

	$tags = array();
	while($row = fetch_array($result))
		array_push($tags, $row["name"]);

	return $tags;
}

function add_tag_to_post($posturl, $tag)
{
	$index = get_post_index($posturl);

	$tag = mysql_escape_string($tag);
	query("INSERT INTO tag (name) VALUES ('$tag')", false);
	$tag = get_tag_index($tag);
	if(!query("INSERT INTO post_tags (post,tag) VALUES ($index,$tag)", false))
		echo "<p>-$tag-</p>";
}

function add_tags($posturl, $tags)
{
	$index = get_post_index($posturl);

	# bryter upp strängen till en array
	$tags = str_replace(
		array(
			"}{", "{", "}",
			"+", " and ", " och "),
		array(
			"|", "", "",
			" & ", " & ", " & "),
		$tags);

	$tags = explode("|",  $tags);
	$tagset = "";
	foreach($tags as $tag)
	{
		$tag = mysql_escape_string($tag);
		query("INSERT INTO tag (name) VALUES ('$tag')", false);
		$tag = get_tag_index($tag);
		$tagset .= "($index,$tag),";
	}
	$tagset = substr($tagset, 0, strlen($tagset) - 1);
	query("INSERT INTO post_tags (post,tag) VALUES $tagset", false);
}

function get_tag_friends($tag)
{
	$index = get_tag_index($tag);
	$tags = array();

	$result = query(
		"SELECT tag.name
		FROM tag,tag_friends
		WHERE
			tag1 = $index AND tag2 = tag.`index`");

	while($row = fetch_array($result))
		array_push($tags, $row["name"]);

	$result = query(
		"SELECT tag.name
		FROM tag,tag_friends
		WHERE
			tag2 = $index AND tag1 = tag.`index`");

	while($row = fetch_array($result))
		array_push($tags, $row["name"]);

	return $tags;
}

function add_tag_friend($index1, $index2)
{
	query("INSERT INTO tag_friends (tag1,tag2) VALUES ($index1,$index2)", false);
}

# -------------------------------------------

function load_data($file)
{
	if(!@is_readable($file)) return false;
	return @unserialize(@file_get_contents($file));
}

function save_data($data, $file)
{
	$fh = @fopen($file, "w");
	if(!$fh) return false;
	@fwrite($fh, @serialize($data));
	@fclose($fh);
	return true;
}

?>

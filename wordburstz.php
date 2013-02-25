<?

require_once("/home/davense/aggregator/db.php");
require_once("/home/davense/aggregator/utf8.php");
require_once("/home/davense/aggregator/string.php");
require_once("/home/davense/aggregator/date.php");

$stoplist = array(
"null", "här", "while", "och", "our", "det", "mot", "ours", "att", "alla", "ourselves", "någon", "you", "for", "eller", "your", "with", "jag", "allt", "yours", "about", "hon", "mycket", "yourself", "against", "som", "sedan", "yourselves", "between", "han", "into", "denna", "him", "through", "den", "själv", "his", "during", "med", "detta", "himself", "before", "var", "she", "after", "sig", "utan", "her", "above", "för", "varit", "hers", "below", "hur", "herself", "till", "ingen", "from", "mitt", "its", "men", "itself",
 "down", "ett", "bli", "they", "blev", "them", "out", "hade", "oss", "their", "din", "theirs", "off", "dessa", "themselves", "over", "icke", "några", "what", "under", "mig", "deras", "which", "again", "blir", "who", "further", "henne", "mina", "whom", "then", "samma", "this", "once", "sin", "vilken", "that", "here", "these", "there", "har", "sådan", "those", "when", "inte", "vår", "where", "hans", "blivit", "why", "honom", "dess", "are", "how", "skulle", "inom", "was", "all", "hennes", "mellan", "were", "any", "där",
 "sådant", "both", "min", "varför", "been", "each", "man", "varje", "being", "few", "vilka", "have", "more", "vid", "ditt", "has", "most", "kunde", "vem", "had", "other", "något", "vilket", "having", "some", "från", "sitta", "such", "sådana", "does", "när", "vart", "did", "nor", "efter", "dina", "doing", "not", "upp", "vars", "only", "vårt", "own", "dem", "våra", "the", "same", "vara", "ert", "and", "vad", "era", "but", "than", "över", "vilkas", "too", "very", "dig", "because", "kan", "sina", "myself", "until",
#"vi", "an", "so", "if", "än", "or", "me", "my", "as", "we", "ha", "of",
#"at", "by", "en", "ju", "he", "på", "åt", "så", "to", "it", "är", "up", "ni", "in", "om", "on", "de", "av",
#"du", "då", "nu", "er", "am", "is", "be", "ej", "do", "ut", "no",
"januari", "februari", "mars", "april", "maj", "juni", "juli", "augusti", "september", "oktober", "november", "december",
"000",
);

function bcfact($n)
{
	# faktoriserar n
	$r = $n--;
	while($n>1) $r=bcmul($r,$n--);
	return $r;
}

function bcbin($n, $r)
{
	# tar fram binomialgrejs för n över r
	$fin = $n - $r + 1;
	$t = 1;
	while($n >= $fin)
		$t = bcmul($t, $n--);
	$n = bcfact($r);
	return bcdiv($t, $n);
}

function _count_words($wz, &$words)
{
	global $stoplist;
	$already = array();

	foreach($wz as $w)
	{
		if(mb_strlen($w) > 3 and !is_numeric($w)
		and !in_array($w, $already)
		and !in_array($w, $stoplist))
		{
			$words[$w]++;
			array_push($already, $w);
		}
	}
}

function _count_bigrams($wz, &$bigrams)
{
	global $stoplist;
	$already = array();

	for($i = 0; $i < count($wz) - 1; $i++)
	{
		# båda orden måste vara längre än X tecken
		if(mb_strlen($wz[$i]) > 2 and mb_strlen($wz[$i+1]) > 2
		# inget av dem får finnas med i stopplistan
		and !in_array($wz[$i], $stoplist) and !in_array($wz[$i+1], $stoplist)
		# båda får inte vara numeriska (men ett av dem)
		and !(is_numeric($wz[$i]) and is_numeric($wz[$i+1])))
		{
			$bigram = $wz[$i]." ".$wz[$i+1];
			if(!in_array($bigram, $already))
			{
				$bigrams[$bigram]++;
				array_push($already, $bigram);
			}
		}
	}
}

function _get_words_n_bigrams($result, &$words, &$bigrams)
{
	while($row = fetch_array($result))
	{
		@set_time_limit(30);

		$text = mb_strtolower("$row[title]\n$row[summary]");
		$phrases = preg_split("/([.,:\r\n\t?!\")(\]\[]|“|”|«|»| (-+|–|—) )+/", $text);
		foreach($phrases as $phrase)
		{
			$phrase = mb_ereg_replace("[^a-zåäöæœøéèáàëüñß0-9]+", " ", $phrase);
			$wz = preg_split("/ +/", $phrase, -1, PREG_SPLIT_NO_EMPTY);

			_count_words($wz, &$words);
			_count_bigrams($wz, &$bigrams);
		}
	}
}

function get_word_bursts($timebegin, $timeend)
{
	global $stoplist;

	# räknar endast de senaste X dagarnas inlägg (eftersom antalet ökar hela tiden)
	$lowtime = strftime("%Y%m%d%H%M%S", strtotime($timeend) - 3600 * 24 * 60);

	# räknar totalt antal inlägg fram till slutdatum
	$row = mysql_fetch_row(query("SELECT COUNT(*) FROM post WHERE time >= $lowtime AND time <= $timeend"));
	$posts_total = $row[0];

	# räknar antal inlägg för tidsintervallet
	$row = mysql_fetch_row(query("SELECT COUNT(*) FROM post WHERE time >= $timebegin AND time <= $timeend"));
	$posts = $row[0];

	# plockar ut alla unika ord och bigram
	$words = array();
	$bigrams = array();

	$start = getmicrotime();

	$result = query("SELECT title,summary FROM post WHERE time >= $timebegin AND time <= $timeend");
	_get_words_n_bigrams($result, &$words, &$bigrams);

	$end = getmicrotime(); echo ($end - $start)." s";

	# använder Zipf-fördelningen för att filtrera bort ointressanta ord
	arsort($words);
	$upper = (int)(sqrt(2 * array_sum($words)));
	$words = array_slice($words, $upper);

	$words = array_merge($words, $bigrams);
	arsort($words);

	$bursts = array();
	foreach(array_keys($words) as $word)
	{
		@set_time_limit(30);

		$hits = $words[$word];
		# ord som bara förekommer ett fåtal gånger är ointressanta eller statistiskt otillförlitliga
		if($hits < 3)
			break;

		$wordx = $word;
		prepare_string($wordx);
		$result = query(
			"SELECT COUNT(*)
			FROM post
			WHERE
				time >= $lowtime
				AND time <= $timeend
				AND MATCH (title,summary) AGAINST ('\"$wordx\"' IN BOOLEAN MODE)
			GROUP BY blog");

		$blogs_total = mysql_num_rows($result);
		# ord som förekommer i X eller färre bloggar ignoreras
		if($blogs_total <= 2)
			continue;

		$hits_total = 0;
		while($row = mysql_fetch_row($result))
			$hits_total += $row[0];

		$result = query(
			"SELECT COUNT(*)
			FROM post
			WHERE
				time >= $timebegin
				AND time <= $timeend
				AND MATCH (title,summary) AGAINST ('\"$wordx\"' IN BOOLEAN MODE)
			GROUP BY blog");

		$blogs = mysql_num_rows($result);
		# ord som förekommer i X eller färre bloggar ignoreras
		if($blogs <= 2)
			continue;

		# sannolikheten att ett inlägg innehåller detta ord
		$p = $hits_total / $posts_total;

		# räknar ut "kostnaden" för ordet, enligt word bursts-algoritmen
		$c0 = -log(bcbin($posts, $hits) * pow($p, $hits) * pow(1 - $p, $posts - $hits));
		$c1 = -log(bcbin($posts, $hits) * pow($p * 4, $hits) * pow(1 - $p * 4, $posts - $hits));

		if($c1 < $c0)
		{
			#echo "<p><em>$word</em> $c0 $c1 ($hits / $posts : $hits_total / $posts_total : $blogs / $blogs_total)</p>";
			$bursts[$word] = $c0;
		}
	}

	return $bursts;
}

function get_most_bursty_words($timebegin, $timeend, $count)
{
	$bursts = get_word_bursts($timebegin, $timeend);
	arsort($bursts);
	$bursts = array_slice($bursts, 0, $count * 2);

	# tar bort ord som ingår i andra ord
	foreach(array_keys($bursts) as $wx)
	{
		foreach(array_keys($bursts) as $wy)
		{
			if($wx == $wy)
				continue;

			if(mb_strpos($wy, $wx) !== false)
			{
				#$bursts[$wy] += log($bursts[$wx]);
				unset($bursts[$wx]);
				break;
			}
		}
	}

	#arsort($bursts);
	return array_keys(array_slice($bursts, 0, $count));
}

function get_example_phrase($word, $timebegin, $timeend)
{
	$wordx = $word;
	prepare_string($wordx);

	$result = query(
		"SELECT `index`,title,summary
		FROM post
		WHERE
			MATCH (title,summary) AGAINST ('\"$wordx\"' IN BOOLEAN MODE)
			AND time >= $timebegin
			AND time <= $timeend
		ORDER BY rand()");

	while($row = fetch_array($result))
	{
		$phrases = preg_split("/([.,:\r\n\t?!\")(]|“|”|«|»| (-+|–|—) )+/", "$row[title]\n$row[summary]");
		foreach($phrases as $phrase)
			if(mb_eregi(".*([ .,:\r\n\t?!\")(]|^|“|”|«|»)$word([ .,:\r\n\t?!\")(]|$|“|”|«|»).+", $phrase)
			or mb_eregi(".+([ .,:\r\n\t?!\")(]|^|“|”|«|»)$word([ .,:\r\n\t?!\")(]|$|“|”|«|»).*", $phrase))
				return array("index" => $row["index"], "phrase" => trim($phrase));
	}

	return array("index" => -1, "phrase" => "");
}

function getmicrotime()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

# -------------------------------------------------------------------------------

$timebegin = strftime("%Y%m%d%H%M%S", time() - 3600 * 24 * 3);
$timeend = strftime("%Y%m%d%H%M%S", time());
$file = "live";

if(isset($_GET["timeend"]) && isset($_GET["timebegin"]))
{
	$timebegin = $_GET["timebegin"]."000000";
	$timeend = $_GET["timeend"]."235959";

	$date = strtotime($_GET["timeend"]);
	$file = getYear($date).getWeek($date);
}

$start = getmicrotime();
$bursts = get_most_bursty_words($timebegin, $timeend, 10);
$end = getmicrotime();

echo ($end - $start)." s";

$data = array();
foreach($bursts as $word)
	array_push($data, array("word" => $word, "eg" => get_example_phrase($word, $timebegin, $timeend)));

save_data($data, "/home/davense/aggregator/bursts/$file");

?>

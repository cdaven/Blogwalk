<?

mb_internal_encoding("UTF-8");

$hostings = array("blogdrive.com", "blogspot.com", "blogg.se", "bloggi.se", "bloggning.se", "webblogg.se",
	"typepad.com", "blogs.com", "blogspirit.com", "blogsome.com", "blogg.passagen.se", "s-info.se",
	"blog-city.com");

function string_contains_any($string, $array)
{
	foreach($array as $needle)
		if(mb_strpos($string, $needle) !== false)
			return true;

	return false;
}

# gör om alla tabbar och radbrytningar till blanksteg
function strip_whitespace($string)
{
	return str_replace(array("\n", "\r", "\t"), " ", $string);
}

function fix_amps($string)
{
	return preg_replace("/&(?![^ ;&]{2,7};)/", "&amp;", $string);
}

# "snyggar till" en sträng så att den kan visas i ett html/xml-dokument
function tidy_up_text($string)
{
	$string = preg_replace("/&(?![^ ;&]{2,7};)/", "&amp;", $string);
	$string = str_replace(array("<", ">", "'"), array("&lt;", "&gt;", "&#39;"), $string);
	return $string;
}

# skapar en url-vänlig sträng från en "vanlig" sträng
# "Skotten i Ådalen" blir "skotten_i_adalen"
function string_to_url($string)
{
	$repl_table = array(
		" " => "_",
		"--" => "-",
		"å" => "a",
		"ä" => "a",
		"à" => "a",
		"á" => "a",
		"æ" => "ae",
		"ö" => "o",
		"ø" => "o",
		"ô" => "o",
		"ó" => "o",
		"ò" => "o",
		"œ" => "oe",
		"é" => "e",
		"è" => "e",
		"ë" => "e",
		"ê" => "e",
		"ç" => "c",
		"ü" => "u",
		"ï" => "i",
		"î" => "i",
		"í" => "i",
		"ì" => "i",
		"ñ" => "n",
		"ß" => "ss",
		"'" => "",
		":" => "",
		"&amp;" => "_",
	);

	$string = mb_strtolower($string);
	$string = str_replace(array_keys($repl_table), array_values($repl_table), $string);
	$string = preg_replace("/[^_\-a-z0-9]/", "_", $string);
	return trim(preg_replace("/_+/", "_", $string), "_-");
}

function number_to_string($number, $decimals = 0)
{
	$string = number_format($number, $decimals);
	$string = str_replace(",", "&nbsp;", $string);
	$string = str_replace(".", ",", $string);
	return $string;
}

# konverterar en potentiell UTF-8-sträng till en ASCII-sträng
# med specialkod som kan omvandlas tillbaka till UTF-8,
# och som är sökbar i mySQL 4.0 med fulltext-sökning
function utf8_to_code(&$string)
{
	$code = "";
	foreach(utf8ToUnicode($string) as $char)
	{
		if($char < 128)
			$code .= chr($char);
		else
		{
			# drar först bort 127 från talet (eftersom det lägsta
			# värdet är 128) och konverterar till basen 36
			# för att spara maximalt utrymme
			$char = base_convert($char - 127, 10, 36);
			$code .= "w'$char'q";
		}
	}

	$string = $code;
	return $string;
}

function code_to_utf8(&$string)
{
	# !!! gör en str_replace med de 10 vanligaste tecknen först
	# det borde spara tid jämfört med preg_match_all

	if(strpos($string, "w'") !== false)
	{
		$matches = array();
		preg_match_all("/w'([a-z0-9]{1,7})'q/", $string, &$matches);

		$replacements = array();
		for($i = 0; $i < count($matches[0]); $i++)
		{
			$code = base_convert($matches[1][$i], 36, 10) + 127;
			$replacements[$matches[0][$i]] = dcr2utf8($code);
		}

		$string = str_replace(array_keys($replacements), array_values($replacements), $string);
	}

	return $string;
}

# konverterar en Unicode-sträng till en array
function mb_string_to_array($string)
{
	$array = array();
	foreach(range(0,mb_strlen($string) - 1) as $pos)
		array_push($array, mb_substr($string, $pos, 1));
	return $array;
}

# omvandlar felaktig UTF-8
function fix_utf8($string)
{
	if(mb_strpos($string, "Ã") !== false)
		$string = utf8_decode($string);

	return $string;
}

function string_startswith($string, $prefix)
{
	return mb_substr($string, 0, mb_strlen($prefix)) == $prefix;
}

function string_endswith($string, $suffix)
{
	$begin = mb_strlen($string) - mb_strlen($suffix);
	return mb_substr($string, $begin) == $suffix;
}

function remove_ending_stops($string)
{
	if(string_endswith($string, "..."))
		$string = mb_substr($string, 0, mb_strlen($string) - 3);
	if(string_endswith($string, "[...]"))
		$string = mb_substr($string, 0, mb_strlen($string) - 5);

	return $string;
}

function get_server_from_url($url)
{
	$matches = array();
	if(preg_match("/http:\/\/(www.)?([^:\/]+)(:\d+)?(\/.*|$)/", $url, &$matches) == 1)
		return $matches[2];
	else
		return $url;
}

function get_full_server_from_url($url)
{
	$matches = array();
	if(preg_match("/http:\/\/((www.)?[^:\/]+)(:\d+)?(\/.*|$)/", $url, &$matches) == 1)
		return $matches[1];
	else
		return $url;
}

function get_relative_url($url)
{
	$matches = array();
	if(preg_match("/http:\/\/(www.)?([^:\/]+)(:\d+)?(\/.*|$)/", $url, &$matches) == 1)
		return $matches[4];
	else
		return false;
}

function get_filename_from_url($url)
{
	$matches = array();
	if(preg_match("/http:\/\/(www.)?([^:\/]+)(:\d+)?(\/.+)*\/(.+\..+)$/", $url, &$matches) == 1)
		return $matches[5];
	else
		return false;
}

function strip_filename_from_url($url)
{
	$filename = get_filename_from_url($url);
	return substr($url, 0, strlen($url) - strlen($filename));
}

# eftersom taggar används i URL:er ersätts & med * ...
function urlencode_tag($tag)
{
	return urlencode(str_replace("&", "*", $tag));
}

# ... och vice versa
function urldecode_tag($tag)
{
	return str_replace("*", "&", urldecode($tag));
}

function get_first_words($text, $count)
{
	if(mb_strlen($text) <= $count)
		return remove_ending_stops($text);

	$short = mb_substr($text, 0, $count);
	$last_space = mb_strrpos($short, " ");
	if($last_space !== false)
		$short = mb_substr($short, 0, $last_space);

	return remove_ending_stops($short);
}

function get_first_paragraphs($text, $minchars, $maxchars, $prefix = "", $postfix = "")
{
	$text = remove_ending_stops($text);
	$text = preg_replace("/^(.*)((\r\n|\n){2}|\z)/Ums", "<p>\\1</p>", $text);

	if(mb_strlen($text) <= $chars)
		return $text;

	$short = mb_substr($text, 0, $maxchars);
	$last_endtag = mb_strrpos($short, "</p>");

	if($last_endtag === false or $last_endtag < $minchars)
	{
		$last_space = mb_strrpos($short, " ");
		if($last_space == false)
			$short = "$short&nbsp;&hellip;</p>";
		else
			$short = mb_substr($text, 0, $last_space)."&nbsp;&hellip;</p>";
	}
	else
		$short = mb_substr($text, 0, $last_endtag)."</p>";

	$short = str_replace("<p>", "<p>$prefix", $short);
	$short = str_replace("</p>", "$postfix</p>", $short);
	$short = preg_replace("/\r\n|\n/", "<br />", $short);

	return $short;
}

$ok_chars = "abcdefghijklmnopqrstuvwxyzåäöæøéèáàóòíìëïüêîôñß1234567890";

# tar bort alla icke-ord-tecken och delar upp texten i sina ord
function get_words($text)
{
	global $ok_chars;

	$newtext = "";
	foreach(mb_string_to_array($text) as $char)
	{
		if($char == "") continue;

		if(@mb_strpos($ok_chars, $char) !== false)
			$newtext .= $char;
		else
			$newtext .= " ";
	}

	return explode(" ", preg_replace("/ +/", " ", $newtext));
}

# stoppordslista svenska+engelska (ord > 2 tecken (plus ord m. åäö, som med specialkod blir längre))
$stoplist = array("null", "här", "while", "och", "our", "det", "mot", "ours", "att", "alla", "ourselves", "någon", "you", "for", "eller", "your", "with", "jag", "allt", "yours", "about", "hon", "mycket", "yourself", "against", "som", "sedan", "yourselves", "between", "han", "into", "på", "denna", "him", "through", "den", "själv", "his", "during",
"med", "detta", "himself", "before", "var", "åt", "she", "after", "sig", "utan", "her", "above", "för", "varit", "hers", "below", "så", "hur", "herself", "till", "ingen", "from", "är", "mitt", "its", "men", "itself", "down", "ett", "bli", "they", "blev", "them", "out", "hade", "oss", "their", "din", "theirs", "off", "dessa", "themselves", "over", "icke", "några", "what", "under", "mig", "deras", "which", "again", "blir", "who", "further", "henne", "mina", "whom", "then",
"då", "samma", "this", "once", "sin", "vilken", "that", "here", "these", "there", "har", "sådan", "those", "when", "inte", "vår", "where", "hans", "blivit", "why", "honom", "dess", "are", "how", "skulle", "inom", "was", "all", "hennes", "mellan", "were", "any", "där", "sådant", "both", "min", "varför", "been", "each", "man", "varje", "being",
"few", "vilka", "have", "more", "vid", "ditt", "has", "most", "kunde", "vem", "had", "other", "något", "vilket", "having", "some", "från", "sitta", "such", "sådana", "does", "när", "vart", "did", "nor", "efter", "dina", "doing", "not", "upp", "vars", "only", "vårt", "own", "dem", "våra", "the", "same", "vara", "ert", "and", "vad", "era", "but", "than", "över", "vilkas", "too", "än", "very", "dig", "because", "kan", "sina", "myself", "until", "idag", "två", "tre", "fyra", "fem", "sex", "sju", "åtta", "nio", "tio", "elva", "tolv",
"bara", "även", "kanske", "hel", "helt", "vanlig", "lite", "mer", "dagens",
"hos", "sitt", "dit", "ens", "iaf", "iofs", "etc", "osv", "mfl", "pga", "http", "www");

?>

<?

require_once("keywords.php");
require_once("utf8.php");
require_once("string.php");
require_once("db.php");
require_once("xmlparse.php");
require_once("html_entities.php");
require_once("lemonad.php");
require_once("html.php");
require_once("date.php");

fetch_lemonad_changes();

# KOMPILERAR FÖRSTASIDAN

$fp = new FrontPageHtml();
$html = $fp->generate(20);

save_data($html, "cache/frontpage");

# HÄMTNING AV FLÖDE FRÅN DEV-BLOGG

$entries = parse_atom(file_get_contents("http://blogwalk-dev.blogspot.com/atom.xml"));
if($entries !== false)
	save_data($entries, "cache/devblog");
else
	echo "Error fetching blogwalk-dev entries\n";

# SPARAR DE NYASTE BLOGGARNA

save_data(generate_latestblogs_list(7), "cache/newestblogs");

# GENERERAR STATISTIK

require_once("generate_stats.php");

# CACHE-TÖMNING

# time är i dessa tabeller ett heltal, inte datetime
$time = time() - 3600 * 24 * 7;
query("DELETE FROM cache_similarposts WHERE time < $time");
query("OPTIMIZE TABLE cache_similarposts");

$time = time() - 3600 * 24 * 2;
query("DELETE FROM cache_locationblogs WHERE time < $time");
query("OPTIMIZE TABLE cache_locationblogs");

# LOG-TÖMNING

$time = time() - 3600 * 24 * 28;
query("DELETE FROM log WHERE time < $time");
query("OPTIMIZE TABLE log");

?>

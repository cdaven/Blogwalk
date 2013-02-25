<?

require_once("keywords.php");
require_once("utf8.php");
require_once("string.php");
require_once("db.php");
require_once("html_entities.php");
require_once("lemonad.php");
require_once("html.php");
require_once("date.php");

# KOMPILERAR TAGGMOLNET

$t = new TagHtml();
save_data($t->generate_cloud(100), "cache/tagcloudpage");

# KOMPILERAR SUPER-TAGGARNA

$s = new SupertagHtml();
$html = $s->generate_overview();
save_data($html, "cache/supertags");

?>

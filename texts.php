<?

# ------------------------------------------

$title["whatisablog"] = "Vad är en blogg?";
$text["whatisablog"] = <<<EOF
<h2>$title[whatisablog]</h2>

<p>Blogg, webblogg eller blog, av engelskans <em>blog</em> eller <em>weblog</em>. Generellt sett är en blogg ett <strong>enkelt sätt att publicera artiklar på webben</strong>. Vanligtvis är det tidpunkten för publicering av inlägg som är den förvalda sorteringsordningen. (Jämför med en traditionell hemsida som nästan uteslutande använder kategorier baserade på dokumentens innehåll.) Många bloggar har dessutom möjligheten att sortera inlägg efter olika slags kategorier.</p>

<p>På de flesta bloggar är det endast en person som skriver inlägg, men det finns också exempel på samarbeten. Vissa företag driver också officiella bloggar. Gemensamt är att bloggen brukar vara medvetet <strong>personlig och subjektiv</strong> i motsats till traditionella nyhetsmedier.</p>

<p><strong>Ofta kan besökare lämna kommentarer</strong> till inläggen, men endast skribenten själv kan ändra på inlägget. Ibland är inlägg och kommentarer lösenordsskyddade eller kräver medlemsskap i bloggen eller annan organisation.</p>

<h3 class='padtop'>Andra tankar</h3>

<ul>
	<li><a href="http://westerstrand.blogspot.com/2005/05/vad-r-en-blogg.html">Infidel: Vad är en blogg?</a></li>
	<li><a href="http://somnlosanatter.se/index.php?id=203">sømnløsa nætter: Vad är en riktig blogg? Vem är bloggare?</a></li>
	<li><a href="http://blogs.law.harvard.edu/whatMakesAWeblogAWeblog">Harvard Weblogs: What Makes a Weblog a Weblog?</a></li>
	<li><a href="http://en.wikipedia.org/wiki/Weblog">Wikipedia (engelska): Weblog</a></li>
</ul>

EOF;

# ------------------------------------------
$number_blogs = number_to_string($stats["blogcount"]);
$number_posts = number_to_string($stats["postcount"]);

$title["whatisthis"] = "Vad är Blogwalk?";
$text["whatisthis"] = <<<EOF
<h2>$title[whatisthis]</h2>

<p>Blogwalk är i grunden en katalog eller <strong>ett arkiv med blogginlägg</strong> och information kring dem. Det finns många sådana, som ofta kallas bloggportaler, pingcentraler, aggregatorer eller liknande. Tanken med Blogwalk är att du enkelt ska kunna hitta inlägg och bloggar som passar dig, men som du inte visste fanns.</p>

<p>Det skrivs så mycket varje dag att det är omöjligt att läsa allt. För att undvika informationsstress bör man heller inte försöka det, utan bara bläddra lite förstrött bland inläggen och kanske följa sina favoritbloggar. På Blogwalk är det enkelt att vandra runt bland inläggen.</p>

<p>Läs gärna <a href='/blogwalk/about/howdoesthiswork'>den mer tekniska beskrivningen</a>.</p>

<h2>Vanliga frågor</h2>

<ul>
	<li><a href="#mostpopular">Var hittar jag de mest populära inläggen?</a></li>
	<li><a href="#opt-in">Hur gör jag för att komma med?</a></li>
	<li><a href="#opt-out">Hur gör jag för att inte komma med?</a></li>
	<li><a href="#linking">Får jag länka till Blogwalk från min blogg/hemsida?</a></li>
	<li><a href="#owner">Vem ligger bakom tjänsten?</a></li>
	<li><a href="#dbsize">Hur många inlägg finns i arkivet?</a></li>
	<li><a href="#cookies">Använder Blogwalk sig av cookies?</a></li>
</ul>

<h3 class='padtop'><a id="mostpopular">Var hittar jag de mest populära inläggen?</a></h3>

<p>På Blogwalk är alla inlägg jämlika. <strong>Du kan inte se vilka som är mer populära än andra.</strong> Topplistor gör ofta att övriga inlägg hamnar i skymundan, och en extremt liten mängd står i fokus. Populära bloggar fortsätter att vara populära &ndash; resten ser man inte. Blogwalk motverkar sådant genom att helt enkelt inte visa populariteten hos inläggen och bloggarna.</p>

<p><strong>Personliga skildringar är ofta långlivade.</strong> Upplägget på Blogwalk bygger på att de flesta bloggar inte är formella nyhetsmedium. De är istället skildringar av personliga upplevelser och tankar &ndash; som självklart påverkas av vad som rör sig i media. (Jämför gärna med <a href="http://www.bokus.com/cgi-bin/book_search.cgi?SERIES=STRINDBERGSS%C4LLSKAPETS%20SKRIFTER">den enorma samling av Strindbergs personliga korrespondens</a> som fortfarande fascinerar.)</p>

<h3 class='padtop'><a id="opt-in">Hur gör jag för att komma med?</a></h3>

<p><strong>All data för denna tjänst hämtas automatiskt från <a href="http://svensk.lemonad.org/">tjänsten Var är du?</a>.</strong> För att komma med i Blogwalk måste du alltså se till att <a href="http://svensk.lemonad.org/new.php">komma med i Var är du?</a>.</p>

<p>Vissa inlägg som finns i Var är du? finns inte med här. Endast de inlägg som inkluderar rubrik, sammanfattning och en permanent länk i sitt flöde tas med i Blogwalks databas.</p>

<p>Om du vill att dina inlägg ska vara etiketterade (eller kategorierade) på Blogwalk &ndash; se till att de kategorier du väljer på din blogg också visas i ditt flöde. Då hämtas de automatiskt tillsammans med inlägget.</p>

<h3 class='padtop'><a id="opt-out">Hur gör jag för att <em>inte</em> komma med?</a></h3>

<p>Om du vill att din blogg ska fortsätta finnas i Var är du?, men inte här, kan du kontakta mig (min adress finns längst ned på denna sida). Vill du inte längre finnas med i någon av tjänsterna måste du kontakta både mig och <a href="http://public.2idi.com/=lemonad">Jonas, som driver Var är du?</a>.</p>

<h3 class='padtop'><a id="linking">Får jag länka till Blogwalk från min blogg/hemsida?</a></h3>

<p>Naturligtvis! Använd gärna den lilla &rdquo;knappen&rdquo; som <a href="http://www.strangnet.se/blog/">Patrick</a> har gjort. <img src="/blogwalk/images/blogwalk.png" width="80" height="15" alt="Blogwalk" /> Spara den till din hårddisk och ladda upp till din blogg/hemsida.</p>

<h3 class='padtop'><a id="owner">Vem ligger bakom tjänsten?</a></h3>

<p>Det gör <a href="http://www.daven.se/christian/">jag, Christian Davén</a>. Än så länge är det ett enmansprojekt, med hjälp och feedback från många bloggare.</p>

<h3 class='padtop'><a id="dbsize">Hur många inlägg finns i arkivet?</a></h3>

<p>Arkivet sträcker sig från 22 april 2005 och framåt. Det finns just nu $number_blogs bloggar och $number_posts inlägg i databasen.</p>

<h3 class='padtop'><a id="cookies">Använder Blogwalk sig av cookies?</a></h3>

<p>Blogwalk lagrar en cookie (en textfil) på din dator för att komma ihåg vilken storlek du vill ha på texten. Om du inte vill att denna cookie ska lagras på din dator kan du stänga av den funktionaliteten i din webbläsare, eller blockera cookies från denna webbplats.</p>

EOF;

# ------------------------------------------

$title["howdoesthiswork"] = "Hur fungerar Blogwalk?";
$text["howdoesthiswork"] = <<<EOF
<h2>$title[howdoesthiswork]</h2>

<p>Varning: Denna sida är tekniskt inriktad och handlar om hur det fungerar under huven på webbplatsen. Du kan om du vill istället <a href="/blogwalk/about/whatisthis">läsa om vad Blogwalk är</a>.</p>

<h3 class='padtop'>Hur hamnar blogginläggen här?</h3>

<ol>
	<li>En bloggare pingar <a href="http://svensk.lemonad.org/">tjänsten Var är du?</a> när han har skrivit något nytt</li>
	<li>Var är du? hämtar <a href="http://sv.wikipedia.org/wiki/RSS">RSS</a>/RDF/Atom-flödet från bloggen</li>
	<li>Så småningom hämtar Blogwalk ett XML-flöde från Var är du?</li>
</ol>

<h3 class='padtop'>När och hur hämtas all data?</h3>

<p>Var är du? <a href="http://svensk.lemonad.org/changes.php">exporterar bloggdata i XML-format</a>. Det är inte ett RSS-flöde eller liknande, utan i <a href="http://svensk.lemonad.org/lib/lemonadchanges.dtd">ett eget format</a>. Detta flöde är relativt obearbetat och en mycket god startpunkt (ett tips till dig som funderar på att starta eget).</p>

<p>Med hjälp av ett så kallat <em><a href="http://en.wikipedia.org/wiki/Crontab">cron job</a></em> körs ett PHP-skript på den här servern, som hämtar XML-flödet och lagrar all information i databasen. Detta sker med jämna mellanrum och helt automatiskt.</p>

<h3 class='padtop'>Hur avgörs vilka inlägg som ska visas under &rdquo;Apropå det &hellip;&rdquo;?</h3>

<p>Med hjälp av en ganska enkel algoritm plockas viktiga ord ut ur det ursprungliga inlägget. Sedan görs en sökning genom databasen efter andra inlägg som innehåller dessa ord. Ibland är de valda orden meningsfulla för inlägget, ibland är de inte det. Algoritmen fyller ändå sitt syfte på ett mycket bra sätt.</p>

<h3 class='padtop'>Om allt är så slumpmässigt, varför visas samma saker hela tiden?</h3>

<p>För att minska belastningen på databasen lagras resultaten från tidsödande SQL-frågor i cache-tabeller. Dessa rensas sedan efter hand via samma <em>cron job</em> som ovan. Exempelvis tar det en liten stund när mySQL söker igenom alla inlägg efter en sträng, och att plocka ut slumpmässiga poster ur en stor samling tar också tid.</p>

<p>Efter ett tag kommer det som verkar statiskt bytas ut mot en ny uppsättning &rdquo;statiska&rdquo; länkar.</p>

EOF;

# ------------------------------------------

$title["searchtips"] = "Söktips";
$text["searchtips"] = <<<EOF
<h2>$title[searchtips]</h2>

<p>På det stora hela liknar sökfunktionen Google och många andra sökmotorer. Alla ord du skriver in måste finnas med för att en &rdquo;träff&rdquo; ska uppstå. Det finns dock vissa saker du kan ha i åtanke för att göra dina sökningar mer effektiva.</p>

<h3>Bloggar eller inlägg</h3>

<p>Du väljer att antingen söka igenom alla inlägg eller alla bloggar. Vid inläggssökning jämförs rubrik och inläggstext med söksträngen, vid bloggsökning namn och beskrivning.</p>

<h3>Fraser</h3>

<p>Fraser omges med &rdquo;-tecken. Sökningen kommer då se hela frasen som en term och hittar endast inlägg med hela den angivna frasen.</p>

<h3>Wildcard</h3>

<p>Avsluta ord med * för att matcha alla möjliga ändelser på ordet. Exempelvis ger strängen &rdquo;blogg*&rdquo; resultat som innehåller <em>blogg</em>, <em>bloggosfär</em>, <em>bloggare</em>, <em>blogging</em> etc.</p>

<h3>Ignorerade ord</h3>

<p>Ord som är kortare än fyra tecken ignoreras helt. De allra vanligaste orden i svenskan och engelskan ignoreras också.</p>

<h3>Exkludering</h3>

<p>Om du vill utesluta inlägg som innehåller ett visst ord sätter du - precis framför ordet. &rdquo;blogg -bloggare&rdquo; ger resultat som innehåller <em>blogg</em>, men inte <em>bloggare</em>.</p>

<h3>Specialtecken</h3>

<p>Det enda specialtecken som fungerar som vanligt i en söksträng är &apos;. Punkter, bindestreck och kommatecken ses av mySQL som ordavgränsare och fungerar inget vidare.</p>

EOF;

# ------------------------------------------

$title["bot"] = "Information om BlogwalkBot";
$text["bot"] = <<<EOF
<h2>$title[bot]</h2>

<p><tt>BlogwalkBot/1.0 (+http://www.blogwalk.se/about/bot)</tt> är den sträng som används för att identifiera Blogwalks "robot". Denna robot samlar in data från olika källor; främst <a href="http://svensk.lemonad.org/">Var är du?</a> och individuella bloggar. Data som samlas in används bara på denna webbplats och kommer aldrig användas för marknadsföring eller liknande.</p>

<h3>In English</h3>

<p><tt>BlogwalkBot/1.0 (+http://www.blogwalk.se/about/bot)</tt> is the string that identifies Blogwalk's bot. This bot gathers data from different sources, mainly the Swedish aggregator <a href="http://svensk.lemonad.org/">Var är du?</a> (Where Are You?) and individual blogs. The gathered data is only used on this web site and will never be used for marketing or similar purposes.</p>

EOF;

?>

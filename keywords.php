<?

class Stemmer
{
	# Stamningsalgoritm baserad på http://www.cling.gu.se/~cl0sknub/ExjobbThereseSofia.pdf

	var $suffixes1;
	var $suffixes2;
	var $cache;

	function Stemmer()
	{
		$this->suffixes1 = array("ornas", "andes", "arnas", "ernas", "rnas", "sens", "orna", "arna", "erna", "ande", "ende", "nas", "ens", "ets", "ing", "ns", "ts", "rna", "ade", "are", "dde", "tte", "sen", "na", "or", "ar", "er", "en", "et", "en", "at", "tt", "it", "de", "te", "dd", "ad", "t", "a", "n", "r", "d", "s");

		$this->suffixes2 = array("het", "skap", "lek", "h");

		$this->cache = array();
	}

	function has_vowel_and_consonant($word)
	{
		if(preg_match("/[^qwrtpsdfghjklzxcvbnm]/", $word) == 1
		and preg_match("/[qwrtpsdfghjklzxcvbnm]/", $word) == 1)
			return true;
		else
			return false;
	}

	function has_one_of_suffixes($word, $suffixes)
	{
		foreach($suffixes as $suffix)
			if(string_endswith($word, $suffix))
				return $suffix;

		return false;
	}

	function strip_suffix($word, $suffix)
	{
		$end = mb_strlen($word) - mb_strlen($suffix);
		return mb_substr($word, 0, $end);
	}

	function fine_tune($stem)
	{
		if(string_endswith($stem, "kl"))
			return mb_substr($stem, 0, mb_strlen($stem) - 2)."kel";

		return $stem;
	}

	function stem($word)
	{
		# kollar om vi har en cache-träff
		if(isset($this->cache[$word]))
			return $this->cache[$word];

		$suffix = $this->has_one_of_suffixes($word, $this->suffixes1);
		if($suffix === false)
		{
			# sparar resultatet i cachen
			$this->cache[$word] = $word;
			return $word;
		}

		$stem = $this->strip_suffix($word, $suffix);
		if($this->has_vowel_and_consonant($stem))
		{
			$wordx = $stem;

			$suffix = $this->has_one_of_suffixes($wordx, $this->suffixes2);
			if($suffix !== false)
			{
				$stem = $this->strip_suffix($wordx, $suffix);
				if(!$this->has_vowel_and_consonant($stem))
				{
					# sparar resultatet i cachen
					$this->cache[$word] = $wordx;
					return $wordx;
				}
			}
		}

		$stem = $this->fine_tune($stem);

		# sparar resultatet i cachen
		$this->cache[$word] = $stem;
		return $stem;
	}
}

# -------------------------------------------

class KeywordExtractor
{
	var $stemmer;
	var $pos_scores;
	var $len_scores;
	var $ok_chars = "abcdefghijklmnopqrstuvwxyzåäöæøéèáàóòíìëïüêîôñß1234567890";

	var $stoplist_names = array(
		"Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday",
		"January", "February", "March", "April", "May", "June", "July", "August",
		"September", "October", "November", "December");

	function KeywordExtractor()
	{
		$this->stemmer = new Stemmer();

		# "poäng" för uträkning av viktigaste nyckelorden
		$this->scores = array(3.74, 3.61, 3.46, 3.32, 3.16, 3.00, 2.83, 2.65, 2.45, 2.24, 2.00, 1.73, 1.41, 1.00);
	}

	function increase_word_score($word, $score, &$scorelist)
	{
		if(isset($scorelist[$word]))
		{
			$scorelist[$word] += $sore;
			return;
		}

		foreach($scorelist as $w => $s)
		{
			# om ett liknande ord finns i listan lägger vi poängen där

			if(mb_substr($word, 0, 5) == mb_substr($w, 0, 5)
			and levenshtein($word, $w) < 4)
			{
				if(mb_strlen($word) < mb_strlen($w))
				{
					unset($scorelist[$w]);
					$scorelist[$word] = $s + $score;
				}
				else
					$scorelist[$w] += $score;

				return;
			}
		}

		$scorelist[$word] = $score;
	}

	function extract_words($text)
	{
		global $stoplist;
		$text = html_entity_decode($text);

		$newtext = "";
		$newsentence = true;

		# plockar ut de godkända tecknen ur strängen.
		# låter inledande versaler vara kvar utom när de inleder en ny mening
		foreach(mb_string_to_array($text) as $char)
		{
			if(@mb_strpos($this->ok_chars, mb_strtolower($char)) !== false)
			{
				if(mb_strtoupper($char) == $char and !$newsentence)
					$newtext .= $char;
				else
					$newtext .= mb_strtolower($char);

				$newsentence = false;
			}
			else
			{
				if($char == "." or $char == "!" or $char == "?")
					$newsentence = true;

				$newtext .= " ";
			}
		}

		$words = array();
		foreach(explode(" ", $newtext) as $word)
			if(mb_strlen($word) > 3 and mb_strlen($word) < 17
			and !in_array(mb_strtolower($word), $stoplist))
				array_push($words, $word);

		return $words;
	}

	function process_words(&$words)
	{
		global $stoplist;
		$new_wordlist = array();
		$numbers = "1234567890";

		foreach($words as $word)
		{
			$score = 0;

			# ord med inledande versal får högre poäng (ej inledande siffror)
			$firstletter = mb_substr($word, 0, 1);
			if(mb_strtoupper($firstletter) == $firstletter and !mb_strpos($numbers, $firstletter))
				$score = $this->scores[0];

			$word = $this->stemmer->stem(mb_strtolower($word));

			if(mb_strlen($word) <= 3
			or mb_strlen($word) >= 17
			or in_array($word, $stoplist)) continue;

			# ord som förekommer tidigt i texten får högre poäng
			if($count <= 13)
				$score += $this->scores[$count] * 2;

			# ju närmare X i ordlängd, desto högre poäng
			$score += $this->scores[abs(10 - mb_strlen($word))] - 1;

			$this->increase_word_score($word, $score, &$new_wordlist);
		}

		$words = $new_wordlist;
	}

	function get_keywords($title, $text, $count = 5)
	{
		# begränsar längden på den text som behandlas
		$text = get_first_words($text, $count * 10 * 20);

		$words = $this->extract_words("$title. $text");
		$this->process_words($words);

		# returnerar endast de $count viktigaste nyckelorden
		arsort($words);
		return array_slice(array_keys($words), 0, $count);
	}

	function get_named_entities($title, $text, $count = 5)
	{
		# begränsar längden på den text som behandlas
		$text = get_first_words($text, $count * 10 * 20);

		$words = $this->extract_words("$title. $text");
		$this->process_entities($words);

		# returnerar endast de $count viktigaste nyckelorden
		arsort($words);
		return array_slice(array_keys($words), 0, $count);
	}

	function process_entities(&$words)
	{
		global $stoplist;
		$new_wordlist = array();
		$numbers = "1234567890";

		foreach($words as $word)
		{
			$firstletter = mb_substr($word, 0, 1);
			if(mb_strtoupper($firstletter) == $firstletter and !mb_strpos($numbers, $firstletter))
			{
				# tar bort eventuellt genitiv
				if(string_endswith($word, "s"))
					$word = mb_substr($word, 0, mb_strlen($word) - 1);

				if(mb_strlen($word) <= 2
				or mb_strlen($word) >= 17
				or in_array($word, $stoplist)
				or in_array($word, $this->stoplist_names))
					continue;

				if(isset($new_wordlist[$word]))
					$new_wordlist[$word]++;
				else
					$new_wordlist[$word] = 1;
			}
		}

		$words = $new_wordlist;
	}
}

# -------------------------------------------

class SimilarityCalculator
{
	var $extractor;

	function SimilarityCalculator()
	{
		$this->extractor = new KeywordExtractor();
	}

	function get_from_cache($index, $count, $random)
	{
		$similar = array();

		$query =
 			"SELECT post.`index`,title,summary,post.time,post.url,blog,blog.name,blog.url AS blogurl
			FROM cache_similarposts,post,blog
			WHERE
				original=$index
				AND similar=post.`index`
				AND post.blog=blog.`index`";
		if($random)
			$query .= "ORDER BY rand()";
		$query .= "\nLIMIT $count";

		$result = query($query);
		while($row = fetch_array($result))
			array_push($similar, $row);

		return $similar;
	}

	function get_keywords($index, $count)
	{
		$row = fetch_array(query("SELECT title,summary FROM post WHERE `index`=$index"));
		return $this->extractor->get_keywords($row["title"], $row["summary"], $count);
	}

	function get_similar_posts($index, $keywords, $count = 10, $random = true)
	{
		# försöker hämta färdigt resultat från cache
		$similar = $this->get_from_cache($index, $count, $random);
		if(count($similar) > 0)
			return $similar;

		if(count($keywords) <= 1 and ($keywords[0] == "" or $keywords[0] == "X"))
		{
			# om inlägget inte har några färdiga nyckelord genererar vi ett par stycken
			$keywords = $this->get_keywords($index, 5);
			if(count($keywords) == 0)
				# kunde inte generera några nyckelord
				return array();
		}

		$searchstring = "";
		foreach($keywords as $word)
			$searchstring .= "$word* ";

		# hämtar och cachar minst X inlägg
		list($_, $posts) = search_posts($searchstring, max(10, $count), $index);

		# cachar inläggen
		$time = time();
		foreach($posts as $post)
			$values .= "($index,$post[index],$time),";
		$values = substr($values, 0, strlen($values) - 1);
		query("INSERT INTO cache_similarposts (original,similar,time) VALUES $values", false);

		# returnerar bara så många inlägg som begärts
		return array_slice($posts, 0, $count);
	}
}

?>

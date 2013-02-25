<?

function generate_login_form()
{
	global $self;

	return "<h2>Logga in</h2><form method='post' action='$self?login'>
		<p>Användarnamn:<br /><input type='text' name='username' size='50' /></p>
		<p>Lösenord:<br /><input type='password' name='password' size='50' /></p>
		<p><input class='button' type='submit' value='Logga in' /></p>
		</form>";
}

function generate_post_form($index)
{
	global $self;

	$post = get_post($index);

	$title = str_replace(array("&", "'"), array("&amp;", "&apos;"), $post["title"]);
	$summary = str_replace(array("&", "'"), array("&amp;", "&apos;"), $post["summary"]);

	return "<h2>Redigera inlägg</h2><form method='post' action='$self?editpost'>
		<p><input type='hidden' name='index' value='$index' />
		Rubrik:<br /><input type='text' name='title' size='65' value='$title' /></p>
		<p>Sammanfattning:<br /><textarea cols='65' rows='20' name='summary'>$summary</textarea></p>
		<p><input class='button' type='submit' value='Spara ändringar' /> <input class='button' type='reset' value='Återställ' /></p>
		</form><p><a href='$self?removepost=$index' onclick='return confirm(\"Vill du radera inlägget?\")'>Radera inlägg</a></p>";
}

function generate_blog_form($index)
{
	global $self;

	$blog = get_blog($index);

	$name = str_replace(array("&", "'"), array("&amp;", "&apos;"), $blog["name"]);
	$description = str_replace(array("&", "'"), array("&amp;", "&apos;"), $blog["description"]);

	return

"<script type='text/javascript'>
	function validateForm()
	{
		if(document.getElementById('input_move').checked)
		{
			if(document.getElementById('input_dest').value == '')
			{
				alert('Du måste ange ett bloggnummer.');
				return false;
			}
			else return confirm('Är du säker på att du vill flytta alla inlägg?');
		}
		return true;
	}
</script>

<h2>Redigera blogg</h2><form method='post' action='$self?editblog'>
<p><input type='hidden' name='index' value='$index' />
Namn:<br /><input type='text' name='name' size='65' value='$name' /></p>
<p>Beskrivning:<br /><textarea cols='65' rows='10' name='description'>$description</textarea></p>
<p><input type='checkbox' id='input_move' name='move' value='1' /> Flytta alla inlägg till bloggen: 
<input type='text' id='input_dest' name='destination' size='10' /></p>
<p><input class='button' type='submit' value='Spara ändringar' onclick='return validateForm();' /> <input class='button' type='reset' value='Återställ' /></p>
</form><p><a href='$self?removeblog=$index' onclick='return confirm(\"Vill du radera bloggen och alla inlägg?\")'>Radera blogg</a></p>";
}

function generate_tag_form($tag)
{
	global $self;

	return "<h2>Redigera etikett</h2>
		<form method='post' action='$self?edittag&amp;super'>
		<p>Etikett: $tag <input type='hidden' name='tag1' value='$tag' />	</p>
		<p>Super-etikett:<br /><input type='text' name='tag2' size='65' value='' /></p>
		<p><input class='button' type='submit' value='Spara ändringar' /></p>
		</form>

		<p>Eller ...</p>

		<form method='post' action='$self?edittag&amp;friend'>
		<p>Etikett: $tag <input type='hidden' name='tag1' value='$tag' />	</p>
		<p>Kompis-etikett:<br /><input type='text' name='tag2' size='65' value='' /></p>
		<p><input class='button' type='submit' value='Spara ändringar' /></p>
		</form>";
}

function generate_inactive_blogs()
{
	$html = "<h2>Inaktiva bloggar</h2><ul>";
	$result = query("SELECT * FROM blog");
	while($row = fetch_array($result))
	{
		$post = get_last_post($row["index"]);
		$time = strtotime($post["time"]);
		if($time < time() - 3600 * 24 * 27)
			$html .= generate_blogspot($row);
	}
	$html .= "</ul>";
	return $html;
}

?>

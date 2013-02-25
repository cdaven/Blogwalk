<?

class XMLToArray
{
	# en klass jag hittade på
	# http://www.devarticles.com/c/a/PHP/Converting-XML-Into-a-PHP-Data-Structure/


	//-----------------------------------------
	var $parser;
	var $node_stack = array();
	var $errormsg = "";
	var $curpos = array();

	//-----------------------------------------
	/** PUBLIC
	* Parse a text string containing valid XML into a multidimensional array
	* located at rootnode.
	*/
	function parse($xmlstring="")
	{
		// set up a new XML parser to do all the work for us
		$this->parser = xml_parser_create("UTF-8");
		xml_set_object($this->parser, $this);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($this->parser, "startElement", "endElement");
		xml_set_character_data_handler($this->parser, "characterData");

		// Build a Root node and initialize the node_stack...
		$this->node_stack = array();
		$this->startElement(null, "root", array());

		// parse the data and free the parser...
		xml_parse($this->parser, preg_replace("/&(?!amp;)/", "&amp;", $xmlstring));
		$this->errormsg = xml_error_string(xml_get_error_code($this->parser));
		$this->curpos["line"] = xml_get_current_line_number($this->parser);
		$this->curpos["column"] = xml_get_current_column_number($this->parser);
		xml_parser_free($this->parser);

		// recover the root node from the node stack
		$rnode = array_pop($this->node_stack);
		$rnode = array_shift($rnode["_ELEMENTS"]);

		// return the root node...
		return($rnode);
	}

	//-----------------------------------------
	/** PROTECTED
	* Start a new Element. This means we push the new element onto the stack
	* and reset it's properties.
	*/
	function startElement($parser, $name, $attrs)
	{
		// create a new node...
		$node = array();
		$node["_NAME"] = $name;
		foreach ($attrs as $key => $value) {
			$node[$key] = $value;
		}

		$node["_DATA"] = "";
		$node["_ELEMENTS"] = array();

		// add the new node to the end of the node stack
		array_push($this->node_stack, $node);
	}

	//-----------------------------------------
	/** PROTECTED
	* End an element. This is done by popping the last element from the
	* stack and adding it to the previous element on the stack.
	*/
	function endElement($parser, $name)
	{
		// pop this element off the node stack
		$node = array_pop($this->node_stack);
		$node["_DATA"] = trim($node["_DATA"]);

		// and add it an an element of the last node in the stack...
		$lastnode = count($this->node_stack);
		array_push($this->node_stack[$lastnode-1]["_ELEMENTS"], $node);
	}

	//-----------------------------------------
	/** PROTECTED
	* Collect the data onto the end of the current chars.
	*/
	function characterData($parser, $data)
	{
		// add this data to the last node in the stack...
		$lastnode = count($this->node_stack);
		$this->node_stack[$lastnode-1]["_DATA"] .= $data;
	}
}

//-----------------------------------------

function xml_get_all_elements($root, $element)
{
	$elements = array();
	foreach($root["_ELEMENTS"] as $folder)
		if($folder["_NAME"] == $element)
			array_push($elements, $folder);

	return $elements;
}

function xml_get_element_data($root, $element)
{
	$e = xml_get_element($root, $element);
	return decode_html_entities($e["_DATA"]);
}

function xml_get_element($root, $element)
{
	if(count($root["_ELEMENTS"]) == 0)
		die("no element '$element' found! \n");

	foreach($root["_ELEMENTS"] as $folder)
		if($folder["_NAME"] == $element)
			return $folder;

	return $root;
}

# parsar Atom-formatet till en array (endast titel och url)
function parse_atom($xmldata)
{
	$xmltoarray = new XMLToArray();
	$root_node = $xmltoarray->parse($xmldata);

	if(!isset($root_node["_ELEMENTS"]))
		return false;

	$atomdata = array();
	foreach($root_node["_ELEMENTS"] as $folder)
		if($folder["_NAME"] == "entry")
			foreach($folder["_ELEMENTS"] as $item)
				if($item["_NAME"] == "link" and $item["type"] == "text/html")
				{
					$data = array();
					$data["url"] = $item["href"];
					$data["title"] = $item["title"];
					array_push($atomdata, $data);
				}

	return $atomdata;
}

# parsar RDF-formatet till en array (gör ingen versionskontroll)
function parse_rdf($xmldata)
{
	$xmltoarray = new XMLToArray();
	$root_node = $xmltoarray->parse($xmldata);

	$rssdata = array();
	foreach($root_node["_ELEMENTS"] as $folder)
	{
		if($folder["_NAME"] == "item")
		{
			$item = array();
			foreach($folder["_ELEMENTS"] as $file)
			{
				if($file["_NAME"] == "title")
					$item["title"] = $file["_DATA"];
				elseif($file["_NAME"] == "description")
					$item["description"] = $file["_DATA"];
				elseif($file["_NAME"] == "link")
					$item["link"] = $file["_DATA"];
				elseif($file["_NAME"] == "dc:date")
				{
					$date = str_replace(array("T", "Z"), " ", $file["_DATA"]);
					$item["date"] = strtotime($date);
				}
			}

			array_push($rssdata, $item);
		}
	}

	return $rssdata;
}

# parsar RSS 2.0-formatet till en array (gör ingen versionskontroll)
function parse_rss($xmldata)
{
	$xmltoarray = new XMLToArray();
	$root_node = $xmltoarray->parse($xmldata);

	$channel = xml_get_element($root_node, "channel");

	$rssdata = array();
	foreach($channel["_ELEMENTS"] as $folder)
	{
		if($folder["_NAME"] == "item")
		{
			$item = array();
			foreach($folder["_ELEMENTS"] as $file)
			{
				if($file["_NAME"] == "title")
					$item["title"] = $file["_DATA"];
				elseif($file["_NAME"] == "description")
					$item["description"] = $file["_DATA"];
				elseif($file["_NAME"] == "link")
					$item["link"] = $file["_DATA"];
				elseif($file["_NAME"] == "pubDate")
					$item["date"] = strtotime($file["_DATA"]);
			}

			array_push($rssdata, $item);
		}
	}

	return $rssdata;
}

?>

<?php

include "config/config.php";

/**
 * Run the .jar file that performs a pdf parse/search on a given term and given file.
 * Capture the results and scale them to fit new dimensions (if sizes are different).
 * Echo out the resulting json file for the bookreader to read.
 *
 * This file helps implement BookReader's full-text search feature. The output needs
 * to be in the format listed here: https://openlibrary.org/dev/docs/api/search_inside.
 * For an example of the output we generate, have a look at the README or try breaking 
 * it down and testing it yourself.
**/

// This info is passed from the BookReader api.
$callback = $_GET['callback'];
$item_id  = $_GET['item_id'];
$path     = $_GET['path'];
$q        = $_GET['q'];

$callback = escapeshellarg($callback);
$item_id  = escapeshellarg($item_id);
$path     = escapeshellarg($path);

// Mimic's escapeshellarg except that it doesn't remove special characters (like é).
$q = "'" . str_replace("'", "\'", trim($q, '"')) . "'";

// "abbyy" or "css", Changes the way coordinates are presented. Don't need to change.
$style = escapeshellarg("abbyy");

// Execute the shell command to run the .jar file. Uses the Apache PDFBox library.
// $script       = "java -jar pddfbox_search.jar " . $item_id . " " . $path . " " . $q . " '" . $callback . "' '" . $style . "' ";
$script       = "python3.7 PyPDFSearcher.py " . $item_id . " " . $path . " " . $q . " '" . $callback . "' '" . $style . "' ";

$shell_output = shell_exec('LANG="en_US.utf-8"; ' . $script);

header('Content-Type: application/json');

/**
 * Start of true output from exec_testApi.php
 * The info is passed back to BookReader's search feature.
 *
 * The $shell_output contains a header with details about the book followed by any 
 * matches that followed. You may run the command above on a local pdf file to see 
 * the format. You only need to provide correct values for the $relative_path and 
 * $q-(uery), however all arguments must be present.
 **/

$lines        = explode("\n", $shell_output);
$header_lines = array_slice($lines, 0, 5);
$text_lines   = array();

$cb       = substr($header_lines[0], strpos($header_lines[0], ":") + 1);
$ia       = substr($header_lines[1], strpos($header_lines[1], ":") + 1);
$query    = substr($header_lines[2], strpos($header_lines[2], ":") + 1);
$numPages = substr($header_lines[3], strpos($header_lines[3], ":") + 1);

// If there are no matches found, then $shell_output contains the header and 2 newlines.
if (count($lines) > 6)
	{ 
	$text_lines = array_slice($lines, 5);
	}

echo $cb . "( {" 
	. "\n\t\"ia\": \"" . $ia .  "\","
    . "\n\t\"q\": \"\\\"" . $query . "\\\"\","
    . "\n\t\"page_count\": " . $numPages . ","
    . "\n\t\"leaf0_missing\": true,"
    . "\n\t\"matches\": [\n";

/*
 * Read the rest of output line by line and echo out the completed json file.
 * Modify the dimensions in $shell_output to reflect the image size of the jpgs
 * in your resourcespace -> filestore directory.
 *
 * If there were no matches found, no results would be shown. "matches: []"
 */
foreach ($text_lines as $line)
	{
	$label 	 = substr($line, 0, strpos($line, ":"));
	$content = substr($line, strpos($line, ":") + 1);

	if ($label == "text")
		{
		$text = $content;
		}
	elseif ($label == "page_num")
		{
		$pagenum = $content;
		}
	elseif ($label == "page_size")
		{
		$dimensions = explode(",", $content);
		$ratio_w    = 1.0;
		$ratio_h    = 1.0;
		$pgwidth    = $dimensions[0];
		$pgheight   = $dimensions[1];
		$param6     = $pagenum + 1;
		
		// Use the ResourceSpace api to find the location of each page.
		// For info visit: https://www.resourcespace.com/knowledge-base/api/
		$query = "user=" . $user . "&function=get_resource_path&param1=" . trim($item_id, '"\'') . "&param2=&param3=scr&param4=&param5=&param6=" . $param6;
		$sign  = hash("sha256", $private_key . $query);
		$uri   = file_get_contents($base_url . "api/?" . $query . "&sign=" . $sign);

        if ($uri != "")
        	{
        	$uri = str_replace('"', '', $uri);
        	$uri = str_replace('\\', '', $uri);
        	list($width, $height) = getimagesize($uri);

        	$ratio_w  = $width / $pgwidth;
			$ratio_h  = $height / $pgheight;
			$pgwidth  = $width;
			$pgheight = $height;
    		}
		}
	elseif ($label == "text_bounds")
		{
		$bounds = explode(",", $content);
		$bBound = $bounds[0] * $ratio_h;
		$tBound = $bounds[1] * $ratio_h;
		$rBound = $bounds[2] * $ratio_w;
		$lBound = $bounds[3] * $ratio_w;
		}
	elseif ($label == "term_bounds")
		{
		$coords = explode(",", $content);
		$r = $coords[0] * $ratio_w;
		$b = $coords[1] * $ratio_h;
		$t = $coords[2] * $ratio_h;
		$l = $coords[3] * $ratio_w;
		}
	else
		{	
		echo ("{" . "\n\t\"text\": \"" . $text . "\", " 
        			. "\n\t\"par\": [{" 
        			. "\n\t\t\"page\": " . $pagenum . ", \"page_width\": " . $pgwidth . ", \"page_height\": " . $pgheight . ","
        			. "\n\t\t\"b\": " . $bBound . ", \"t\": " . $tBound . ", \"r\": " . $rBound . ", \"l\": " . $lBound . ","
        			. "\n\t\t\"boxes\": ["
        			. "\n\t\t\t{\"page\": " . $pagenum . ", \"r\": " . $r . ", \"b\": " . $b . ", \"t\": " . $t . ", \"l\": " . $l . "}"
        			. "\n\t\t] \n\t}] \n}," . "\n");
		}
	}

echo "] \n} )";

?>

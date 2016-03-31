<?php 

if (!class_exists('SQLite3')) {
    echo "install sqlite3 first, forexample: 'apt-get install php5-sqlite3'";
    die("SQLite3 class not found!");
}

$params = array();
if(isset($argv))
{
	$params = $argv;
}

$data = array();

$dir = "/var/www/src/grep";

$file = realpath(dirname(__FILE__))."/database.db";

// Open database
$db = new SQLite3($file);
$db->enableExceptions(true);

/*  TOOL SCAN DIRECTORY AND GET ALL FILES  */
function getDirContents($dir, &$results = array()){
    $files = scandir($dir);

    foreach($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
        if(!is_dir($path)) {
            $results[] = array(
            	'path' => $path,
            	'ext'  => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            	'type' => "FILE"
            );
        } else if($value != "." && $value != "..") {
        	$results[] = array(
            	'path' => $path,
            	'ext'  => "",
            	'type' => "DIR"
            );
            getDirContents($path, $results);
        }
    }

    return $results;
}

/*  DB CLEAN TABLES  */
function cleanDB($db)
{
	try {
		$db->exec(
        	"DELETE from files WHERE 1;"
        );

        $db->exec(
        	"DELETE from elements WHERE 1;"
        );
    }
    catch (Exception $exception) {
        echo "ERROR: ".$exception->getMessage();die();
    }
}

/*  DB CLEAN FILE RELATED RECORDS  */
function cleanElement($db, $filepath)
{
	try {
        $db->exec(
        	"DELETE from elements WHERE filepath = '${filepath}';"
        );
    }
    catch (Exception $exception) {
        echo "ERROR: ".$exception->getMessage();die();
    }
}

/*  DB INSERT FILE ELEMENT RELATED RECORD  */
function insertElement($db, $filepath, $filename, $fileext, $element, $elementType, $linenum)
{
	try {
		$query = "INSERT INTO elements (filepath, filename, fileext, element, elementType, linenum) VALUES ('${filepath}', '${filename}', '${fileext}', '${element}', '${elementType}', '${linenum}')";
        $db->exec($query);
    }
    catch (Exception $exception) {
    	echo htmlspecialchars($query);
        echo "ERROR: ".$exception->getMessage();die();
    }
}

/*  DB INSERT FILE LAST SCAN MODIFICATION TIME  */
function insertFile($db, $filepath)
{
	try {
		$query = "SELECT * FROM files  WHERE
			filepath = '${filepath}';
		";

        $results = $db->query($query);
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        $modTime = date ("Y-m-d H:i:s.", filemtime($filepath));

        if(empty($data)) 
        {
			$query = "INSERT INTO files (filepath, modTime) VALUES ('${filepath}', '${modTime}')";
	        $db->exec($query);
	        return true;
    	}
    	else
    	{
    		if($data[0]['modTime'] != $modTime)
    		{
    			
    			$query = "UPDATE files SET modTime = '${modTime}' WHERE id = '".$data[0]['id']."' ";
	        	$db->exec($query);
    			return true;
    		}
    	}
    }
    catch (Exception $exception) {
    	echo htmlspecialchars($query);
        echo "ERROR: ".$exception->getMessage();die();
    }

    return false;
}

/*  DB SEARCH FOR ELEMENTS  */
function searchElements($db, $search)
{
	$data = array();

	try {
		$query = "SELECT * FROM elements 
			WHERE 
			(elementType = 'file' AND (
			filename like '%${search}%' OR 
			fileext like '%${search}%')) OR
			((elementType = 'class' OR elementType = 'function') AND (
			element like '%${search}%'));
		";

        $results = $db->query($query);
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
    }
    catch (Exception $exception) {
    	echo htmlspecialchars($query);
        echo "ERROR: ".$exception->getMessage();die();
    }

    return  $data;
}

/*  FUNCTIONS  */

/*  FUNCTION SAVE TREE TO DB  */
function saveTree(&$tree, &$tokens, $path, $db)
{
	$sClass = false;
	$sFunction = false;

	foreach($tree as $value) 
	{

		if(is_array($value))
		{
			saveTree($value, $tokens, $path, $db);
		}
		else
		{
			$token = $tokens[$value];

			if ('T_CLASS' == token_name($token[0]))
	    	{
	    		$sClass = true;
	    	}
	    	else
	    	if ($sClass && 'T_STRING' == token_name($token[0]))
	    	{
	    		$path_parts = pathinfo($path);
	    		insertElement($db, $path, $path_parts['filename'], $path_parts['extension'], $token[1], 'class', $token[2]);

	    		$sClass = false;
	    	}
	    	else
			if ('T_FUNCTION' == token_name($token[0]))
	    	{
	    		$sFunction = true;
	    	}
	    	else
	    	if ($sFunction && 'T_STRING' == token_name($token[0]))
	    	{
	    		$path_parts = pathinfo($path);
	    		insertElement($db, $path, $path_parts['filename'], $path_parts['extension'], $token[1], 'function', $token[2]);

	    		$sFunction = false;
	    	}

	    	//echo "Line {$token[2]}: ". token_name($token[0]). " ('".htmlspecialchars($token[1])."')", PHP_EOL;
		}
	}
}

/*  FUNCTION BUILD TREE FROM TOKENS  */
function scanTree(&$tree, &$tokens, $i)
{
	for(; $i < count($tokens); $i++) {

		$token = $tokens[$i];

	    if (is_array($token) && $token[1] != '${') 
	    {

	    	if ('T_WHITESPACE' != token_name($token[0]))
	    	{
	    		$tree[] = $i;
	    	}
	    }
	    else
	    if ($token == '{' || (is_array($token) && $token[1] == '${'))
	    {
	    	$subtree = array();
	    	$i = scanTree($subtree, $tokens, $i+1);
	    	$tree[] = $subtree;
	    }
	    else
	    if ($token == '}')
	    {
	    	return $i;
	    }
	    else
	    if ($token == '(')
	    {
	    	$subtree = array();
	    	$i = scanTree($subtree, $tokens, $i+1);
	    	$tree[] = $subtree;
	    }
	    else
	    if ($token == ')')
	    {
	    	return $i;
	    }
	}

	return $i;
}

/*  FUNCTION SCAN FILES IN DIRECTORY  */
function scanPHP(&$paths, $db, $printStatus = false)
{
    foreach($paths as $key => $value){
        if($value['ext'] == 'php') {
        	$path = $value['path'];
        	
        	if(insertFile($db, $path))
        	{
        		cleanElement($db, $path);

	        	if($printStatus)
	        	{
	        		echo "SCAN ".$path.PHP_EOL;
	        	}

	        	$path_parts = pathinfo($path);
		    	insertElement($db, $path, $path_parts['filename'], $path_parts['extension'], "", 'file', 0);

	        	$content = file_get_contents($path);
	        	$tokens = token_get_all($content);
	        	
	        	/*if($path  == '/var/www/src/grep/test.php')
	        	{
		        	echo "<pre>";
		        	var_dump($tokens);
					echo "</pre>";
				}*/

				$tree = array();
				scanTree($tree, $tokens, 0);

				/*if($path  == '/var/www/src/grep/test.php')
	        	{
					echo "<pre>";
		        	var_dump($tree);
					echo "</pre>";
				}*/
				
				saveTree($tree, $tokens, $path, $db);
			}
			else
			{
				if($printStatus)
	        	{
	        		echo "SKIP ".$path.PHP_EOL;
	        	}
			}
        }
    }
}

/*  ACTIONS  */

/*  ACTION SCAN  */

if(@$_REQUEST['action'] == 'scan' || in_array("scan", $params))
{
	$printStatus = in_array("scan", $params);

	$paths = array();
	getDirContents($dir, $paths);
	scanPHP($paths, $db, $printStatus);
	echo "DONE".PHP_EOL;
}

/*  ACTION INIT  */

if(@$_REQUEST['action'] == 'init' || in_array("init", $params))
{
	try {
		$db->exec(
        	"CREATE TABLE IF NOT EXISTS files
	        (
	            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	            filepath VARCHAR(255) NULL,
	            modTime VARCHAR(255) NOT NULL
	        );"
        );

        $db->exec(
        	"CREATE TABLE IF NOT EXISTS elements
	        (
	            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	            filepath VARCHAR(255) NULL,
	            filename VARCHAR(255) NOT NULL,
	            fileext VARCHAR(20) NOT NULL,
	            element VARCHAR(255) NOT NULL,
	            elementType VARCHAR(20) NOT NULL,
	            linenum INTEGER NOT NULL
	        );"
        );
    }
    catch (Exception $exception) {
        echo "ERROR: ".$exception->getMessage();die();
    }
}

/*  ACTION RESET  */

if(@$_REQUEST['action'] == 'reset' || in_array("reset", $params))
{
	cleanDB($db);
}

/*  ACTION VIEW  */

if((!isset($_REQUEST['action']) && !isset($argv)) || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view'))
{
	$search = @$_REQUEST['search'];

	echo "<style>a{text-decoration:none;color:green;} .tfile{color:red;} .tclass{color:green;} .tfunction{color:blue;} .tfelement{background-color:yellow;} .tpath{color:grey;}</style>
	<div><form><input type='hidden' name='action' value='view'><input type='text' name='search' value='".htmlspecialchars($search)."'><input type='submit' value='seach' /></form></div><hr>";

	if(trim($search) != "")
	{

		$data = searchElements($db, $search);

		echo "<table style='width=100%'>";
		foreach($data as $value)
		{
			echo "<tr>";
			if($value['elementType'] == 'file')
			{
				echo "<td><a href='/grep.php?view=view&search=".urlencode($value['element'])."&line=".urlencode($value['linenum'])."&path=".urlencode($value['filepath'])."' target='_blank' >view</a></td>";
				echo "<td class='tfile'>file</td>";
				echo "<td class='tfelement'></td>";
				echo "<td class='tpath'>".htmlspecialchars($value['filepath'])."</td>";
			}

			if($value['elementType'] == 'class')
			{
				echo "<td><a href='/grep.php?view=view&search=".urlencode($value['element'])."&line=".urlencode($value['linenum'])."&path=".urlencode($value['filepath'])."' target='_blank' >view</a></td>";
				echo "<td class='tclass'>class</td>";
				echo "<td class='tfelement'>".htmlspecialchars($value['element'])."</td>";
				echo "<td class='tpath'>".htmlspecialchars($value['filepath'])."</td>";
			}

			if($value['elementType'] == 'function')
			{
				echo "<td><a href='/grep.php?view=view&search=".urlencode($value['element'])."&line=".urlencode($value['linenum'])."&path=".urlencode($value['filepath'])."' target='_blank' >view</a></td>";
				echo "<td class='tfunction' >function</td>";
				echo "<td class='tfelement'>".htmlspecialchars($value['element'])."</td>";
				echo "<td class='tpath'>".htmlspecialchars($value['filepath'])."</td>";
			}
			echo "</tr>";
		};
		echo "</table>";

	}
}
<?php

$host='';
$user='root';
$pass='root';

$db = mysql_connect('localhost',$user,$pass);


$command = @$_REQUEST['command'];
$word = @$_REQUEST['word'];
$selector = @$_REQUEST['selector'];
$excludeString = @$_REQUEST['exclude'];


$dbname = @$_REQUEST['dbname'];
$table = @$_REQUEST['table'];
$column = @$_REQUEST['column'];

$out = "";
if ($command=='structure') {
    $out = getStructure($db, $dbname);
}else if ($command=='indexies') {
    $out = getIndexies($db, $dbname);
}else if ($command=='keys') {
    $out = getForeginKey($db, $dbname);
}else if ($command=='tables') {
    $out = getTableNames($db, $dbname, $table);
}else if ($command=='databases') {    
    $out = getDatabaseNames($db, $dbname, $table);
}else if ($command=='search'&& trim($word)!="" && strlen(trim($word))>2) {

    $dbnames = array();
    $tables = array();
    $columns = array();
    if ($selector!="") {
        $parts = explode("|", $selector);

        if (isset($parts[0])) {
            $dbnames = explode(",", $parts[0]);
        }

        if (isset($parts[1])) {
            $tables = explode(",", $parts[1]);
        }

        if (isset($parts[2])) {
            $columns = explode(",", $parts[2]);
        }
    }

	$exclude = array();
    if ($excludeString!="") {
        $exclude = explode(",", $excludeString);
        foreach ($exclude as &$value) {
        	$value = trim($value);
        }
    }

    $view_columns = array();
    if($column!="") {
        $view_columns = explode("|", $column);
    }


    $out = searchfor($db, $word, $dbnames, $tables, $columns, $view_columns, $exclude);
} 
    
drawInterface($command, $word, $selector, $dbname, $table , $column, $excludeString, $out);


function drawInterface($command, $word, $selector, $dbname, $table, $column, $excludeString,  $out)
{
    echo "<html><head><title>sGrep</title></head><body>";
    echo "<div class='bar'><form action='' method='get'>";    
    echo "<div class='word'><input type='text' id='word' name='word' placeholder='word' value='{$word}' /></div>";
    echo "<div class='selector'><input type='text' id='selector' name='selector' placeholder='selector' value='{$selector}' /></div>";
    echo "<div class='exclude'><input type='text' id='exclude' name='exclude' placeholder='exclude' value='{$excludeString}' /></div>";
    echo "<div class='column'><input type='text' id='column' name='column' placeholder='out columns' value='{$column}' /></div>";
    echo "<div class='btn'><input type='submit' name='command' value='search' /></div>";

    echo "<div class='dbname'><input type='text' id='dbname' name='dbname' placeholder='dbname' value='{$dbname}' /></div>";
    echo "<div class='table'><input type='text' id='table' name='table' placeholder='table' value='{$table}' /></div>";
    echo "<div class='btn'><input type='submit' name='command' value='structure' /></div>";
    echo "<div class='btn'><input type='submit' name='command' value='indexies' /></div>";
    echo "<div class='btn'><input type='submit' name='command' value='keys' /></div>";
    echo "<div class='btn'><input type='submit' name='command' value='tables' /></div>";
    echo "<div class='btn'><input type='submit' name='command' value='databases' /></div>";        
    echo "<div class='btn'><input type='submit' name='set' value='set' /></div>";
    echo "</form></div>";
    echo "<div class='output'><pre>".htmlspecialchars(print_r($out, true))."</pre></div>";
    echo "</body></html>";
    die();
}


function gettables($db, $dbname)
{
    mysql_select_db($dbname, $db);

    $sql = mysql_query("SHOW TABLES;");

    $data = array();

    while ($row = mysql_fetch_assoc($sql))
        $data[] = $row;

    return $data;
}


function getdatabases($db)
{

    $sql = mysql_query("SHOW DATABASES;");

    $data = array();

    while ($row = mysql_fetch_assoc($sql))
        $data[] = $row;

    return $data;
}

function getcolumns($db, $dbname, $dbtable)
{

    $sql = mysql_query("SHOW COLUMNS FROM ".$dbtable.";");

    if (!$sql) {
        echo "Error: e001: Table not exist!\n";die;
    }

    $data = array();

    while ($row = mysql_fetch_assoc($sql))
        $data[] = $row;

    return $data;
}

function searchfor($db, $word, $dbname_select = array(), $table_select = array(), $column_select = array(), $view_columns = array(), $exclude = array())
{

    $dbnames = array();
    if(count($dbname_select)==0) {
        $dbnametmp = getdatabases($db); //get all databases

        foreach ($dbnametmp as $dbname)
            $dbnames[] = $dbname['Database'];
    } else {
        $dbnames = $dbname_select;
    }

    $data = array();
    foreach($dbnames as $dbname){
        $ecode = mysql_select_db($dbname, $db);

        if (!$ecode) {
            echo "Error: e002: Database not exist!\n";die;
        }

        $tables = array();
        if(count($table_select)==0) {
            $tabletmp = gettables($db, $dbname); // get all tables in database

            foreach ($tabletmp as $tablex)
            {
            	$tablename = $tablex['Tables_in_'.$dbname];
            	if(!in_array($tablename, $exclude))
            	{
                	$tables[] = $tablename ;
            	}
            }
        } else {
            $tables = $table_select;
        }

        $data = array();
        foreach($tables as $table){

            $columns = array();
            if(count($column_select)==0) {
                $columnstmp = getcolumns($db, $dbname, $table);

                foreach ($columnstmp as $columnx) {
                    $columns[] = $columnx['Field'];
                }

            } else {
                $columns = $column_select;
            }

            $like='';
            for($i=0;$i<count($columns);$i++) {
                $like .= " `".$columns[$i]."` like '%".mysql_real_escape_string($word)."%'";
                if($i!=count($columns)-1) $like .= " OR";
            }

            $order='';
            if (count($view_columns)>0) {
                $order="ORDER BY";
                for($i=0;$i<count($view_columns);$i++) {
                    $order .= " `".$view_columns[$i]."` ASC";
                    if($i!=count($view_columns)-1) $order .= ",";
                }
            }

            $query = "SELECT * FROM ".$table." WHERE ".$like." ".$order;

            //print_r($query);

            $sql = mysql_query($query);

            if (!$sql) {
                echo "Error: e003: Column not exist!\n";die;
            }

            while ($sql && $row = mysql_fetch_assoc($sql)) {

                if (count($view_columns)>0) {
                    foreach ($row as $key=>$val) {
                        if (!in_array($key, $view_columns)) {
                            unset($row[$key]);
                        }
                    }
                }

                $data[$dbname][$table][] = $row;
            }
        }
    }

    return $data;
}

function getStructure($db, $dbname)
{
    /*SELECT *
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'test' AND TABLE_NAME ='products';*/

    $sql = mysql_query("
        SELECT *
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = '{$dbname}'
        ORDER BY TABLE_SCHEMA ASC, TABLE_NAME ASC, ORDINAL_POSITION ASC
    ");

    $dbstructure = array();
    if (!$sql) {
        echo "Error: e004: Table not exist!\n";die;
    }

    $data = array();

    while ($row = mysql_fetch_assoc($sql)) {
        $data[] = $row;

        $databasename = $row['TABLE_SCHEMA'];
        $tablename = $row['TABLE_NAME'];
        $columnname = $row['COLUMN_NAME'];

        $dbstructure[$databasename][$tablename][$columnname] = $row;
    }

    return $dbstructure;
}


function getIndexies($db, $dbname)
{
    $sql = mysql_query("
        SELECT
            *
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = '{$dbname}';
    ");

    $dbstructure = array();
    if (!$sql) {
        echo "Error: e005: Table not exist!\n";die;
    }

    $data = array();

    while ($row = mysql_fetch_assoc($sql)) {
        $data[] = $row;

        $databasename = $row['TABLE_SCHEMA'];
        $tablename = $row['TABLE_NAME'];
        $columnname = $row['COLUMN_NAME'];

        $dbstructure[$databasename][$tablename][$columnname] = $row;
    }

    return $dbstructure;
}

function getForeginKey($db, $dbname)
{

    mysql_select_db('information_schema', $db);

    $sql = mysql_query("
        SELECT *
        FROM KEY_COLUMN_USAGE
        WHERE  CONSTRAINT_SCHEMA = '{$dbname}'
    ");

    $dbstructure = array();
    if (!$sql) {
        echo "Error: e006: Table not exist!\n";die;
    }

    $data = array();

    while ($row = mysql_fetch_assoc($sql)) {
        $data[] = $row;

        $databasename = $row['TABLE_SCHEMA'];
        $tablename = $row['TABLE_NAME'];
        $columnname = $row['COLUMN_NAME'];
        $reftablename = $row['REFERENCED_TABLE_NAME'];
        $refcolumnname = $row['REFERENCED_COLUMN_NAME'];

        $dbstructure[$databasename][$tablename][$columnname] = $reftablename.'.'.$refcolumnname;
    }

    return $dbstructure;
}

function getTableNames($db, $dbname, $table)
{
    mysql_select_db($dbname, $db);

    $sql = mysql_query("SHOW TABLES FROM {$dbname}", $db);
    if (!$sql) {
        echo 'Error: e009: '.mysql_error($db);die;
    }

    $data = array();

    while ($row = mysql_fetch_assoc($sql)) {
        $data[] = $row['Tables_in_'.$dbname];
    }

    return $data;
}

function getDatabaseNames($db, $dbname, $table)
{

    $sql = mysql_query("SHOW DATABASES", $db);
    if (!$sql) {
        echo 'Error: e010: '.mysql_error($db);die;
    }

    $data = array();

    while ($row = mysql_fetch_assoc($sql)) {
        $data[] = $row['Database'];
    }

    return $data;
}
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
$table = @$_REQUEST['tables'];
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

$tree = getTree($db);
drawInterface($command, $word, $selector, $dbname, $table , $column, $excludeString, $tree, $out);


function drawInterface($command, $word, $selector, $dbname, $table, $column, $excludeString, $tree , $out)
{
    echo "<html>";

    echo "<head>";
    echo "<title>sGrep</title>";

    echo "<script src='jquery-2.2.2.min.js'></script>";

    echo "<style>
        body,
            html {
            margin:0;
            padding:0;
            color:#000;
            background:#a7a09a;
        }
            #wrap {
                width:1000px;
                margin:0 auto;
                background:#99c;
            }
        #header {
        padding:5px 10px;
                background:#ddd;
            }
            h1 {
            margin:0;
        }
        #nav {
            padding:5px 10px;
            background:#c99;
        }
        #nav ul {
            margin:0;
            padding:0;
            list-style:none;
        }
        #nav li {
            display:inline;
            margin:0;
            padding:0;
        }
        #main {
            float:left;
            width:580px;
            padding:10px;
            background:#9c9;
        }
        h2 {
        margin:0 0 1em;
        }
        #sidebar {
            float:right;
            width:380px;
            padding:10px;
            background:#99c;
        }
        #footer {
            clear:both;
            padding:5px 10px;
            background:#cc9;
        }
        #footer p {
            margin:0;
        }
        * html #footer {
            height:1px;
        }

        .bar-search div,.bar-other div, .bar-buttons div {
            float:left;
        }

        </style>";

    echo "</head>";


    echo "<body>";


    echo "<div id='wrap'>";

    echo "<form action='' method='get'>";


    echo "<div id='header'>";
        echo "<div class='bar'>";
            echo "<div class='bar-search'>";
            echo "<div class='word'><input type='text' id='word' name='word' placeholder='word' value='{$word}' /></div>";
            echo "<div class='selector'><input type='text' id='selector' name='selector' placeholder='selector' value='{$selector}' /></div>";
            echo "<div class='exclude'><input type='text' id='exclude' name='exclude' placeholder='exclude' value='{$excludeString}' /></div>";
            echo "<div class='column'><input type='text' id='column' name='column' placeholder='out columns' value='{$column}' /></div>";
            echo "<div class='btn'><input type='submit' name='command' value='search' /></div>";
            echo "<div style='clear:both'></div>";
            echo "</div>";

            echo "<div class='bar-other'>";
            echo "<div class='dbname'><input type='text' id='dbname' name='dbname' placeholder='dbname' value='{$dbname}' /></div>";
            echo "<div class='table'><input type='text' id='table' name='table' placeholder='table' value='{$table}' /></div>";
            echo "<div style='clear:both'></div>";
            echo "</div>";

            echo "<div class='bar-buttons'>";
            echo "<div class='btn'><input type='submit' name='command' value='structure' /></div>";
            echo "<div class='btn'><input type='submit' name='command' value='indexies' /></div>";
            echo "<div class='btn'><input type='submit' name='command' value='keys' /></div>";
            echo "<div class='btn'><input type='submit' name='command' value='tables' /></div>";
            echo "<div class='btn'><input type='submit' name='command' value='databases' /></div>";
            echo "<div class='btn'><input type='submit' name='set' value='set' /></div>";
            echo "<div style='clear:both'></div>";
            echo "</div>";

            echo "<div style='clear:both'></div>";  
        echo "</div>";
    echo "</div>";

    echo "<div id='nav'>";
        echo "<ul>
			<li><a href=''></a></li>
			<li><a href=''></a></li>
		</ul>";
    echo "</div>";

    echo "<div id='main' class='element-results' >";
        echo "<h2>Result</h2>";
        echo "<div class='output'><pre>".printData($out, $word)."</pre></div>";
    echo "</div>";

    echo "<div id='sidebar'>";
        echo "<h2>Tree</h2>";
        echo "<div class='tree-block'>";
        echo $tree;
        echo "</div>";
    echo "</div>";

    echo "<div id='footer'>
		<p>sGrep</p>
	</div>";

    echo "</form>";

    echo "</div>";



    echo "</body>";

    echo "</html>";
    die();
}

function printData($data, $word)
{
    static $i = 0;    

    $html = "";

    foreach($data as $key => $value) {
        $i++;

        if (is_array($value)) {
            $html .= "<div class='sub-values' style='padding-left:30px;'><div><b>".htmlspecialchars($key)."</b></div><div style='padding-left:30px;' >".printData($value, $word)."</div></div>";
        }
        else
        {
            $found = false;
            $islong = strlen($value) > 30;            
            if (strpos($value, $word) !== false) {
                $found = true;
                $value = str_replace($word, "<span style='color:red;' >".$word."</span>", htmlspecialchars($value));
            } else {
                $value = htmlspecialchars($value);
            }            

            $html .= "<div class='sub-values' ><i style='color:".(($found)?"blue":"grey").";'><small onclick='$(\"#data-$i\").toggle();' >".htmlspecialchars($key)."</small></i> <span>".substr($value, 0 , 30).(($islong)?"...":"")."</span></div>";
            if ($islong) {
                $html .= "<div id='data-$i' class='data' style='display:none;'><textarea style='width:100%;height:300px;'>".$value."</textarea></div>";
            }    
        }
        
    }

    return "<div class='values' >".$html."</div>";
}

function getTree($db)
{
    $allTables = getAllTables($db);

    $html = "<style>
        .tree-db {            
            padding-left:0px;
        }

        .tree-table {
            display:none;
            padding-left:5px;
        }

        .tree-column {
            display:none;
            padding-left:15px;
            color:grey;
        }

    </style><script>

    <script>";

    $dbname = "";
    $table = "";
    foreach($allTables as $item) {

        if($dbname == "" || $dbname != $item['dbl']) {

            $dbname = $item['dbl'];

            $html .= "<div class='tree-db' >";
            $html .= "<input type='checkbox' name='d[".$dbname."]' ".((isset($_REQUEST['d'][$dbname]))? "checked":"")." >";
            $html .= "<b><span onclick='$(\"#tree-d-".$dbname."\").toggle();'>".$dbname."</span></b>";
            $html .= "</div>";
        }

        if($table == "" || $table != $item['tbl']) {

            $table = $item['tbl'];            

            $html .= "<div class='tree-table' >";
            $html .= "<input type='checkbox' name='t[".$dbname."][".$table."]' ".((isset($_REQUEST['t'][$dbname][$table]))? "checked":"")." >";
            $html .= "<i><span onclick='$(\"#tree-t-".$dbname."-".$table."\").toggle();'>".$table."</span></i>";
            $html .= "</div>";
        }

        $column = $item['clm'];

        $html .= "<div class='tree-column' >";
        $html .= "<input type='checkbox' name='c[".$dbname."][".$table."][".$column."]' ".((isset($_REQUEST['t'][$dbname][$table][$column]))? "checked":"")." >";
        $html .= "<span onclick='$(\"#tree-c-".$dbname."-".$table."-".$column."\").toggle();'>".$column."</span>";
        $html .= "</div>";
    }


    return "<div class='tree'>".$html."</div>";
}

function getAllTables($db)
{
    mysql_select_db("information_schema", $db);

    $sql = mysql_query("select  
        COLUMN_NAME as clm, 
        TABLE_NAME as tbl, 
        TABLE_SCHEMA as dbl
    from
        information_schema.COLUMNS
    order by TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME");

    $data = array();

    while ($row = mysql_fetch_assoc($sql))
        $data[] = $row;

    //var_dump($data);

    return $data;
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
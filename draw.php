<?php

$host='';
$user='root';
$pass='root';

$db = @mysql_connect('localhost',$user,$pass);

//$out = getStructure($db, $dbname);

$dbname = @$_REQUEST['dbname'];

$out = array();
if(trim($dbname) != "")
{
    $out = getForeginKey($db, $dbname);
    $struct = getStructure($db, $dbname);

}
$databases = getDatabaseNames($db);

drawInterface($dbname, $out, $struct, $databases);
function drawInterface( $dbname, $out, $struct, $databases)
{
    echo "<html><head><title>sGrep</title></head><body>";
    echo "<style>a:link, a:hover, a:active, a:visited{color:black;text-decoration: none;}</style>";
    echo "<div class='bar'>";


    echo "<span><b>Databases</b>: </span>";
    foreach($databases as $database)
    {
        echo " <span><a href='?dbname={$database}' style='color:blue;'>{$database}</a></span> | ";
    }
    echo "</div>";

    echo "<h1>Graf</h1>";
    echo "<div class='draw' >";
    draw($out);
    echo "</div>";

    echo "<div class='structure' >";

    echo "<h1>Short structure</h1>";
    echo "<table class='short_structure' >";
    foreach($struct as $tablename => $column)
    {
        echo "<tr id='table_structure_short_".$tablename."' colspan='2' style='background-color:red;'><td><b>".$tablename."</b><span style='color:grey'>(".count($column).")</span></td></tr>";
        
        foreach($column as $columnname => $columnvalues)
        {
            echo "<tr colspan='2'><td><a href='#table_structure_column_".$tablename."_".$columnname."'>".$columnname."</a></td></tr>";
        }      
        
    }
    echo "</table>";

    echo "<h1>Long structure</h1>";
    echo "<table class='long_structure' >";
    foreach($struct as $tablename => $column)
    {
        echo "<tr colspan='2' style='background-color:red;'><td><b>".$tablename."</b></td></tr>";
        
        foreach($column as $columnname => $columnvalues)
        {
            echo "<tr id='table_structure_column_".$tablename."_".$columnname."' colspan='2'><td style='background-color:green;'><b><i>".$columnname."</i></b></td></tr>";
            foreach($columnvalues as $columnvaluename => $columnvalue)
            {
                echo "<tr>";                    
                echo "<td>".$columnvaluename."</td>";  
                echo "<td>".$columnvalue."</td>";  
                echo "</tr>";  
            }
        }      
        
    }
    echo "</table>";

    echo "</div>";

    echo "</body></html>";
}

function draw($out)
{
    foreach($out as &$item)
    {
        $item['color'] = randomColor (128, 255);
    }

    echo "<table><tr style='font-size:200%;text-align:center;'><td style='width:30%;'>Table</td><td style='width:30%;'>Keys</td><td style='width:30%;'>Parents</td></tr>";
    foreach($out as $table => &$item)
    {
        echo "<tr style='border:solid 1px grey;'>";
        echo "<td><a href='#table_structure_short_".$table."'><div id='table_".$table."' style='float:left;margin:10px;padding:10px;background-color:".$item['color']."' >".$table."<span style='color:grey;font-size:small;'>(".$item['num'].")</span></div></a></td>";
        echo "<td>";
        if(isset($item['ref']))foreach($item['ref'] as $columname => &$data)
        {
            echo "<a href='#table_".$data['table']."'><div style='float:left;margin:10px;padding:10px;background-color:".$out[$data['table']]['color']."' >".$data['table'].":".$data['column']."<span style='color:grey;font-size:small;'>(".$out[$data['table']]['num'].")</span></div>";
        }
        echo "</td>";
        echo "<td>";
        if(isset($item['parents']))foreach($item['parents'] as &$parent)
        {
            echo "<a href='#table_".$parent['table']."'><div style='float:left;margin:10px;padding:10px;background-color:".$out[$parent['table']]['color']."' >".$parent['table'].":".$parent['column']."<span style='color:grey;font-size:small;'>(".$out[$parent['table']]['num'].")</span></div>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table><div style='clear:both;'></div>";

    /*echo "<pre>";
    print_r($out);
    echo "</pre>";*/
}

function randomColor ($minVal = 0, $maxVal = 255)
{

    // Make sure the parameters will result in valid colours
    $minVal = $minVal < 0 || $minVal > 255 ? 0 : $minVal;
    $maxVal = $maxVal < 0 || $maxVal > 255 ? 255 : $maxVal;

    // Generate 3 values
    $r = mt_rand($minVal, $maxVal);
    $g = mt_rand($minVal, $maxVal);
    $b = mt_rand($minVal, $maxVal);

    // Return a hex colour ID string
    return sprintf('#%02X%02X%02X', $r, $g, $b);

}

function getStructure($db, $dbname)
{
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

        $dbstructure[$tablename][$columnname] = $row;
    }

    return $dbstructure;
}

function getForeginKey($db, $dbname)
{

    $dbstructure = array();

    mysql_select_db($dbname, $db);

    $sql = mysql_query("SHOW TABLES FROM {$dbname}", $db);
    if (!$sql) {
        echo 'Error: e009: '.mysql_error($db);die;
    }

    $data = array();

    $i = 1;
    while ($row = mysql_fetch_assoc($sql)) {
        $dbstructure[$row['Tables_in_'.$dbname]] = array( 'num' => $i++);
    }

    mysql_select_db('information_schema', $db);

    $sql = mysql_query("
        SELECT *
        FROM KEY_COLUMN_USAGE
        WHERE  CONSTRAINT_SCHEMA = '{$dbname}'
    ");

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

        if($reftablename != "" && $refcolumnname != ""){
            $dbstructure[$tablename]['ref'][$columnname] = array("table" => $reftablename, "column" => $refcolumnname);
            $dbstructure[$reftablename]['parents'][] = array("table" => $tablename, "column" => $refcolumnname);
        }

    }

    return $dbstructure;
}

function getDatabaseNames($db)
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
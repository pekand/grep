<?php
  /*
    Andrej Pekar
    2014-08-14 odstranene result a finish z vyhladavania
    2014-10-27 zmena title na strankach pohladov

  */
  /*********/

  // #SETTINGS
  ini_set('memory_limit', '2048M');
  error_reporting(E_ALL);
  ini_set('display_errors', '1');
  ini_set("auto_detect_line_endings", true);

  /*********/

  // #OPTIONS
  $options = Array();

  $options['default_extensions'] = 'php css sass js xml json yml twig htm html sql';
  $options['max_file_size'] = 5*1024*1024; // max file size in B

  $html = "";

  $options['exclude_dir'] = array(
  );

  /*********/

  // #ROUTES
  $search = "";
  $path = "";
  $view = "homescreen";
  $listdir = "";
  $searchall = false; // list all files in dyrectory (search is empty and find was pressed)

  // hladane slovo
  if(isset($_REQUEST['search']))
  {
    $search = $_REQUEST['search'];

    if(trim($search)=="" && isset($_REQUEST['find']))
    {
      $searchall = true;
    }
  }

  // aktualny pohlad
  if(isset($_REQUEST['view']))
  {
    $view = $_REQUEST['view'];
  }

  // #PARAMETER LINE - vybrany riadok na ktory sa ma skocit
  $select_line = "";
  if(isset($_REQUEST['line']))
  {
    $select_line = $_REQUEST['line'] + 0;
  }

  // #PARAMETER DIR - cesta pre replace
  $dir = getcwd();
  $directory_exist = false; // selected directory dont exist
  if(isset($_REQUEST['dir']))
  {
    $dir = $_REQUEST['dir'];
    $directory_exist = false;
    if(file_exists($_REQUEST['dir']) && is_dir($_REQUEST['dir']))
    {
      $directory_exist = true;
    }
  }
  else
  // #PARAMETER VIEW
  if(isset($_REQUEST['path']))
  {
    if(file_exists($_REQUEST['path']) && is_file($_REQUEST['path']))
    {
      $dir = dirname($_REQUEST['path']);
      $path = $_REQUEST['path'];
    }
    else
    {
      echo "<div>Error : File not exist</div>";
      exit();
    }
  }
  else
  // #PARAMETER LISTDIR
  if(isset($_REQUEST['listdir']))
  {
    if(file_exists($_REQUEST['listdir']) && is_dir($_REQUEST['listdir']))
    {
      $listdir = $_REQUEST['listdir'];
    }
    else
    {
      echo "<div>Error : Directory not exist</div>";
      exit();
    }
  }

  // #PARAMETER EXT_CHECK
  $check_extension = false;
  if(isset($_REQUEST['ext_check']))
  {
    if($_REQUEST['ext_check']=='ext_check' )
    {
      $check_extension = true;
    }
  }

  // #PARAMETER EXT
  $ext =Array();
  if(isset($_REQUEST['ext']) && trim($_REQUEST['ext'])!='' )
  {
    $ext = trim($_REQUEST['ext']);
    $ext = preg_replace('/\s+/', ' ', $ext);
    $ext = explode(' ', $ext);
  }

  // #PARAMETER datelimit
  $datelimit = false;
  $datestart = null;
  $dateend = null;
  if(isset($_REQUEST['datelimit']) && isset($_REQUEST['datestart']) && isset($_REQUEST['dateend']))
  {
    if($_REQUEST['datelimit']=='datelimit')
    {
      $datelimit = true;
    }

    $date_pattern = "/^[0-9]{0,4}-[0-3]{0,1}[0-9]{1}-[0-3]{0,1}[0-9]{1}(\s(0[0-9]|1[0-9]|2[0-4]|[0-9])(:([0-5][0-9]|[0-9])){0,1}){0,1}$/";
    if(preg_match($date_pattern, $_REQUEST['datestart']))
      $datestart = strtotime($_REQUEST['datestart']);
    if(preg_match($date_pattern, $_REQUEST['dateend']))
      $dateend   = strtotime($_REQUEST['dateend']);
  }

  // #PARAMETER FIND
  $find = true;
  if(isset($_REQUEST['find']))
  {
    if($_REQUEST['find']=='find')
    {
      $find = true;
    }
  }

  // #PARAMETER GREP
  $grep = false;
  if(isset($_REQUEST['grep']))
  {
    if($_REQUEST['grep']=='grep' && trim($search)!="")
    {
      $grep = true;
    }
  }

  // #PARAMETER CASE
  $matchcase = false;
  if(isset($_REQUEST['matchcase']))
  {
    if($_REQUEST['matchcase']=='matchcase')
    {
      $matchcase = true;
    }
  }

  // #PARAMETER FIND
  $wait = false;
  if(isset($_REQUEST['wait']))
  {
      $wait = true;
  }

  // #PARAMETER REGEX
  $regex = false;
  if(isset($_REQUEST['regex']))
  {
    if($_REQUEST['regex']=='regex')
    {
      $regex = true;
    }
  }

  // #PARAMETER EXCLUDE
  if(isset($_REQUEST['exclude']))
  {
    if($_REQUEST['exclude']!='')
    {
      $temp_path = explode(';', $_REQUEST['exclude']);
      foreach ($temp_path as $temp) {
        if (trim($temp) != "") {
            if (file_exists($temp)) {
                $options['exclude_dir'][] =  realpath($temp);
            } else {
                $temp_dir = realpath($dir). DIRECTORY_SEPARATOR .$temp;
                if (file_exists($temp_dir)) {
                    $options['exclude_dir'][] =  realpath($temp_dir);
                }
            }
        }
      }
    }
  }

  // #REPLACE
  $replace_replace ='';
  if(isset($_REQUEST['replacestring']))
  {
    $replace_replace = $_REQUEST['replacestring'];
  }

  /*********/

  // #TEMPLATES

  function template_view_file_body($params = array())
  {
    return "<!DOCTYPE html>
    <html>
      <head>
        <meta charset='utf-8' />
        <title>".$params['title']."</title>

        <style>
          .clear{clear:both;}
          .left{float:left;}

          *{font-family:'Courier New';}
          body{margin:0px;padding:0px;}
          b{color:red;font-weight:normal;}
          pre{margin:0px;padding:0px;}

          table, tr, td {
            margin:0px;padding:0px;
            border:0px solid black;
            border-spacing:0px;
          }
          .no_select{
           -webkit-touch-callout: none;
           -webkit-user-select: none;
           -khtml-user-select: none;
           -moz-user-select: none;
           -ms-user-select: none;
           user-select: none;
         }
         .highlight:hover{
           background-color:#CFF29B;
         }

         .st1{background-color:gray;padding:10px;}
         .st2{margin:0px;padding:0px;}
         .st3{width:100%;}
         .st4{width:50px;}
         .st5{width:99%;}

         .st6{line-height:18px;}

         .line_number{padding:0px 10px;margin-right:5px;height:18px;line-height:18px;}
         .higlight_row{background-color:red;}
         .normal_row{background-color:grey;}

         .higlight_word{background-color:#FF6666;}
         .higlight_symbol{color:blue;}
         .higlight_number{color:green;}
        </style>

        <script>

            function gotoline(eid)
            {
              document.getElementById('element-'+eid).scrollIntoView();
            }

            function escapeHtml(unsafe)
            {
              return unsafe
                  .replace('&', '&amp;')
                  .replace('<', '&lt;')
                  .replace('>', '&gt;')
                  .replace('\"', '&quot;')
                  .replace('\'', '&#039;');
            }

            function higligter(element,word)
            {
                var symbols = '\\\[]+-\\\"\\'{}=()*/<>;,.!@#$%^&_~?:|_';
                var numbers = '1234567890';
                var text = document.getElementById(element).textContent;
                var len = text.length;
                var wordlen = word.length;
                var out = '';
                var is_in = false;
                for(var i=0;i<len;i++){
                  is_in = false;
                  if(wordlen>0 && i+wordlen<=len && text.substr(i,wordlen)==word ){
                    out += '<span class=\\\"higlight_word\\\">'+escapeHtml(word)+'</span>';
                    i=i+wordlen-1;
                    continue;
                  }
                  for(var j=0;j<symbols.length;j++){
                    if(text[i]==symbols[j]){
                      is_in = true;
                      break;
                    }
                  }
                  if(is_in){
                    out += '<span class=\\\"higlight_symbol\\\">'+escapeHtml(text[i])+'</span>';
                  }
                  else
                  if('0'<=text[i] && text[i]<='9'){
                     out += '<span class=\\\"higlight_number\\\">'+escapeHtml(text[i])+'</span>';
                  }
                  else{
                    out +=escapeHtml(text[i]);
                  }
                }

                document.getElementById(element).innerHTML = out;
            }

            higligter('file-content','".$params['search']."');
        </script>

      </head>

      <body>".$params['html']."</body>

      <script>
        higligter('file-content','".$params['search']."');
      </script>
    </html>";
  }

  function template_view_file_head($params = array())
  {
    return "
      <div class='template-view-file-head st1' >
        <form method='get' action='edit' class='st2' >
          <table class='st3' >
            <tr>
              <td class='st4'>
                <span>Grep</span>
              </td>
              <td>
                <input type='text' class='st5' name='filepath' id='filepath' value='".$params['path']."' />
              </td>
            </tr>
          </table>
        </form>
      </div>
    ";
  }

  function template_view_file_content($params = array())
  {
    return "
    <div class='template-view-file-content'>
      <table>
        <tr>
          <td>".$params['numbers']."</td>
          <td>
            <pre id='file-content' class='st6'>".$params['lines']."</pre>
          </td>
        </tr>
      </table>
    </div>
    ";
  }

  function template_view_file_number($params = array())
  {
    return "<div id='element-".$params['ln']."' class='template-view-file-number ".$params['color']." line_number no_select' >".$params['lnPad']."</div>";
  }

  function template_view_file_goto($params = array())
  {
    return "<div class='template-view-file-goto' ><script>gotoline(".$params['select_line'].");</script></div>";
  }

  function template_list_dir_body($params = array())
  {
    return "<!DOCTYPE html>
    <html>
      <head>
        <meta charset='utf-8' />
        <title>".$params['title']."</title>

        <style>
         .clear{clear:both;}
         .left{float:left;}

         *{font-family:'Courier New';}
         body{margin:0px;padding:0px;}
         b{color:red;}

         .content{background-color:gray;padding:10px;}
         .r1{width:100px;}
         .r2{width:600px;}
         .r3{background-color:purple;width:10px;height:10px;margin:5px;}
         .r4{background-color:purple;width:10px;height:10px;margin:5px;}
         .r5{color:purple;}
         .r6{color:purple;}
        </style>

      </head>
      <body>
        <div class='content' >
          <table>
            <tr>
              <td class='r1'>
                Directory:
              </td>
              <td>
                <input type='text' class='r2' name='directorypath' id='directorypath' value='".$params['listdir']."' />
              </td>
            </tr>
          </table>
        </div>

        ".$params['html']."
      </body>
    </html>";
  }

  function template_list_dir_row_dir($params = array())
  {
    return "
      <hr/>
      <div class='template-list-dir-row-dir'>
        <a href='".$params['url']."' target='_blank' ><div class='r3 left' ></div></a>
        <div class='left' >
          <span class='r6' id='element-".$params['order']."' onclick='gotonext(".$params['order_next'].")' >".$params['type']."</span> : ".$params['entry']."
        </div>
        <div class='clear'></div>
      </div>";
  }

  function template_list_dir_row_file($params = array())
  {
    return "
    <hr/>
    <div class='template-list-dir-row-file' >
      <a href='".$params['url']."' target='_blank'><div class='r4 left' ></div></a>
      <div class='left'>
        <span class='r5' id='element-".$params['order']."' onclick='gotonext(".$params['order_next'].")' >".$params['type']."</span> : ".$params['entry']."
      </div>
      <div class='clear' ></div>
    </div>";
  }

  function template_search_body($params = array())
  {
    return "<!DOCTYPE html>
      <html>
        <head>
          <meta charset='utf-8' />
          <title>".$params['title']."</title>
          <style>
            .clear{clear:both;}
            .left{float:left;}

            *{font-family:'Courier New';}
            body{margin:0px;padding:0px;}

            b{color:red;}
            hr{display: block; height: 1px;
            border: 0; border-top: 1px solid #ccc;
            margin: 1em 0; padding: 0;}
            .filePath{font-size:75%;}

            .t1{background-color:gray;padding:10px;}
            .t2{margin:0px;padding:0px;}
            .t3{width:100px;}
            .t4{width:600px;}
            .t5{width:100px;}
            #replacestring{width:600px;}
            #dir{width:600px;}
            #exclude{width:600px;}
            #ext{width:569px;}
            .t9{width:200px;}
            .t10{width:200px;}
            .t11{background-color:purple;width:10px;height:10px;margin:5px;}
            .t12{color:purple;}
            .t13{background-color:purple;width:10px;height:10px;margin:5px;}
            .t14{color:purple;}
            .t15{background-color:purple;width:10px;height:10px;margin:5px;}
            .t16{color:purple;}
            .t17{background-color:green;width:10px;height:10px;margin:5px;}
            .t18{color:green;}
            .t19{color:blue;}

          </style>

          <script>
            function gotonext(eid)
            {
              document.getElementById('element-'+eid).scrollIntoView();
            }

            function promptCopy(e) {
              window.prompt('Copy to clipboard: Ctrl+C, Enter', e.textContent);
            }

            document.onkeydown=function(e){
                if( (e.which == 83) && (!e.altKey) && e.ctrlKey ) {
                  window.location.hash = '#search';
                  document.getElementById('search').focus();
                  return false;
                }
            }
          </script>
        </head>
        <body>
          ".$params['menu']."
          ".$params['html']."
        </body>
      </html>";
  }

  function template_search_menu($params = array())
  {
    return "<div class='template-search-menu t1' >
        <form method='get' action='' class='t2' >
          <table>
            <tr>
              <td class='t3' >Search: </td>
              <td>
                <input type='text' name='search' id='search' class='t4' value=\"".$params['search']."\" />
                <input type='hidden' name='view' value='search' />
                <input type='submit' name='find' value='Find' />
              </td>
            </tr>
            <tr>
              <td>Directory: </td>
              <td>
                <input type='text' name='dir' id='dir' value='".$params['dir']."' style='background-color:".($params['directory_exist'] ? "" : "#FFCCCC")."'/>
              </td>
            </tr>
             <tr>
              <td>Exclude: </td>
              <td>
                <input type='text' name='exclude' id='exclude' value='".$params['exclude']."' />
              </td>
            </tr>
            <tr>
              <td>Extensions: </td>
              <td>
                <input id='ext_check' name='ext_check' value='ext_check' type='checkbox' ".$params['ext_check'].">
                <input type='text' name='ext' id='ext' value='".$params['extensions']."' />
              </td>
            </tr>
            <tr>
              <td>Date: </td>
              <td>
                <input type='checkbox' name='datelimit' id='datelimit' value='datelimit' ".$params['datelimit']." />
                <input type='text' class='t9' name='datestart' id='datestart' value='".$params['datestart']."' />
                <input type='text' class='t10' name='dateend' id='dateend' value='".$params['dateend']."' />
              </td>
            </tr>
            <tr>
              <td>Options: </td>
              <td>
                <input type='checkbox' name='file_find' id='file_find' value='file_find' ".$params['file_find']." />
                <label for='file_find'>Find</label>
                <input type='checkbox' name='grep' id='grep' value='grep' ".$params['grep']." />
                <label for='grep'>Grep</label>
                <input type='checkbox' name='matchcase' id='matchcase' value='matchcase' ".$params['matchcase']." />
                <label for='matchcase'>Match case</label>
                <input type='checkbox' name='regex' id='regex' value='regex' ".$params['regex']." />
                <label for='regex'>Regex</label>
              </td>
            </tr>
          </table>
        </form>
      </div>";
  }

  function template_search_find_row_directory($params = array())
  {
    return "
      <hr/>
      <div class='template-search-find-row-directory' >
        <a href='".$params['url']."' target='_blank'><div class='t11 left' ></div></a>
        <div class='left'>
          <span class='t12' id='element-".$params['order']."' onclick='gotonext(".$params['order_next'].")' >".$params['type']."</span> : ".$params['dir_name']."
        </div>
        <div class='clear' ></div>
      </div>";
  }

  function template_search_find_row_file($params = array())
  {
    return "
      <hr/>
      <div class='template-search-find-row-file' >
        <a href='".$params['url']."' target='_blank'><div class='t13 left' ></div></a>
        <div class='left'>
          <span class='t14' id='element-".$params['order']."' onclick='gotonext(".$params['order_next'].")' >".$params['type']."</span> : ".$params['file_name']."
        </div>
        <div class='clear' ></div>
      </div>";
  }

  function template_search_grep_row_file($params = array())
  {
    return "
      <hr/>
      <div class='template-search-grep-row-file' >
          <a href='".$params['url']."' target='_blank' >
            <div class='t15 left' ></div>
          </a>
          <div class='left' >
            <span class='t16' id='element-".$params['order']."' onclick='gotonext(".$params['order_next'].")' >File</span> : <span ondblclick='promptCopy(this);' class='filePath' >".$params['param_file_name']."</span>
          </div>
          <div class='clear'></div>
      </div>";
  }

  function template_search_grep_row_in_file($params = array())
  {
    return "
      <div class='template-search-grep-row-in-file' >
        <a href='".$params['url']."' target='_blank' ><div class='t17 left' ></div></a>
        <div class='left'>
          <span class='t18' id='element-".$params['order']."' onclick='gotonext(".$params['order_next'].")' >Line</span>(<span class='t19' >".$params['param_line_number']."</span>) : ".$params['param_line_text']."
        </div>
        <div class='clear' ></div>
      </div>";
  }

  /*********/

  // #LIB

  function excludepath($path)
  {
    global $options;
    foreach($options['exclude_dir'] as $exc)
    {
      if($pos = strpos($path, $exc) !== false)
      {
        return true;
      }
    }

    return false;
  }

  /*********/
  // replace script
  /*if($options['allowreplace'] && $search!='' && isset($_REQUEST['replace']) && $_REQUEST['replace']=='Replace' && isset($_REQUEST['replace_check']) && $_REQUEST['replace_check']=='replace_check' && file_exists($dir) && is_dir($dir))
  {
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file)
    {
      if($datelimit && !($datestart<=filemtime($file) && filemtime($file)<=$dateend))
        continue;

      if(is_file($file) && (!$check_extension || in_array(pathinfo($file, PATHINFO_EXTENSION), $ext)))
      {
        $replace_content = file_get_contents($file);
        $pos = false;
        if($matchcase)
          $pos = strpos($replace_content, $search);
        else
          $pos = stripos($replace_content, $search);
        if($pos!==false)
        {
          if($matchcase)
            file_put_contents($file,str_replace($search, $replace_replace, $replace_content));
          else
            file_put_contents($file,str_ireplace($search, $replace_replace, $replace_content));
        }
      }
    }
    header("Location:http://".$_SERVER['HTTP_HOST'].str_replace("?replace=Replace","",str_replace("&replace=Replace","",$_SERVER['REQUEST_URI'])));
    exit();
  }*/

  /*********/
  // #VIEW VIEW FILE CONTENT
  if($path!='')
  {
    // horny panel
    $html .= template_view_file_head(array(
      'path' => htmlspecialchars($path),
    ));

    $higlight_word = false;
    if(trim($search)!='') $higlight_word = true;

    $html .= "";
    $lines = "";
    $numbers = "";
    $f = fopen ( $path , "r");
    $ln = 0;
    $cnt = 0;
    while ($line = fgets ($f))
    {
        ++$ln;
        if ($line===FALSE)
        {
          print ("FALSE\n");
        }
        else
        {
          if($searchall){
            $color = "normal_row";
          }
          else
          if($higlight_word && ($pos = (($matchcase)?strpos($line, $search):stripos($line, $search)))!==false)
          {
            $color = "higlight_row";
          }
          else
          {
            $color = "normal_row";
          }
          $numbers.= template_view_file_number(array(
            'ln' => $ln,
            'color' => $color,
            'lnPad' => str_pad($ln, 4, '0', STR_PAD_LEFT),
          ));
          $lines .= $line;
        }
    }
    fclose ($f);
    $html .= template_view_file_content(array(
      'numbers' => $numbers,
      'lines' => htmlspecialchars($lines),
    ));

    if($select_line!='')
      $html .= template_view_file_goto(array(
      'select_line' => $select_line
    ));

    $path_parts = pathinfo($path);

    echo template_view_file_body(array(
      'title' => $path_parts['basename'],
      'html' => $html,
      'search' => addslashes($search)
    ));
  }
  /*********/
  //* LIST DIRECTORY*\\
  if($view == 'listdir') // #VIEW listdir
  {

    $order = 0;
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
          if ($entry != "." && $entry != "..") {
            $entry = $dir . DIRECTORY_SEPARATOR . $entry;

            if(is_dir($entry))
            {
              $type = "Dir";
              $url = "?view=listdir&dir=".urlencode(dirname($entry))."";
              $html .= template_list_dir_row_dir(array(
                'url' => $url,
                'order' => $order,
                'order_next' => $order+1,
                'type' => $type,
                'entry' => htmlspecialchars(dirname($entry)),
              ));

            }
            else
            if(is_file($entry))
            {
              $type = "File";
              $url = "?view=view&path=".urlencode($entry)."";
              $html .= template_list_dir_row_file(array(
                'url' => $url,
                'order' => $order,
                'order_next' => $order+1,
                'type' => $type,
                'entry' => htmlspecialchars(dirname($entry)),
              ));
            }

            $order = $order + 1;
          }
        }
        closedir($handle);
    }

    echo template_list_dir_body(array(
      'title' =>(($dir!='')?"..".substr($dir, -20)." - Grep":"Grep"),
      'listdir' => htmlspecialchars($dir),
      'html' => $html,
    ));
  }

  /*********/
  //* VIEW SEARCH *\\

  function findTextPos($search, $line, $regex, $matchcase)
  {
    $pos = -1;
    if ($regex)
    {
        if ($matchcase)
        {
            $pos = preg_match('~'.$search.'~', $line);
        }
        else
        {
            $pos = preg_match('~'.$search.'~i', $line);
        }
    }
    else
    {
        if ($matchcase)
        {
            $pos = strpos($line , $search);
        }
        else
        {
            $pos = stripos($line, $search);
        }
    }

    return $pos;
  }

  if($view == 'search' || $view == 'homescreen' ){
    $count_all = 0;
    $count_grep = 0;
    $count_file = 0;

    if(($search!='' || $searchall) && $directory_exist && !$wait)
    {
      $html .= "<div>";
      $order = 0;
      $time_start = microtime(true);
      $it = new RecursiveDirectoryIterator($dir);
      foreach(new RecursiveIteratorIterator($it) as $file)
      {

        // skip files whits is not in time interval
        if($datelimit && !($datestart<=filemtime($file) && filemtime($file)<=$dateend))
        {
          continue;
        }

        // skip files with bad extension
        if( $check_extension && !in_array(pathinfo($file, PATHINFO_EXTENSION), $ext) )
        {
            continue;
        }

        // skip large files
        if(isset($options['max_file_size']) && @filesize($file) > $options['max_file_size'])
        {
          continue;
        }

        // skip paths whitch contain string
        if(excludepath(realpath($file)))
        {
          continue;
        }


        if($find == true)  // hladanie súboru (find)
        {

          if(basename($file)=='.' && ( $searchall ||
            $pos = findTextPos($search, basename(dirname($file)), $regex, $matchcase) ) )// directory
          {
            if(is_dir($file))
            {
              $type = "Dir";
              $url = "?view=listdir&dir=".urlencode(dirname($file))."";
            }

            $count_all++;
            $count_file++;

            $html .= template_search_find_row_directory(array(
              'url' => $url,
              'order' => $order,
              'order_next' => $order+1,
              'type' => $type,
              'dir_name' => preg_replace("~(".preg_quote($search).")~i", "<b>$1</b>", htmlspecialchars(dirname($file))),
            ));

            $order = $order + 1;
          }
          else
          if(basename($file)=='..') // skip ..
          {
          }
          else
          if
          (
            $searchall ||
            ($pos = (($matchcase)?strpos(basename($file), $search):stripos(basename($file), $search)))!==false
          ) // file
          {

            $url = '';
            $type = '';
            if(is_file($file))
            {
              $type = "File";
              $url = "?view=view&path=".urlencode($file)."";
            }

            $count_all++;
            $count_file++;

            $html .= template_search_find_row_file(array(
              'url' => $url,
              'order' => $order,
              'order_next' => $order+1,
              'type' => $type,
              'file_name' => preg_replace("~(".preg_quote($search).")~i", "<b>$1</b>", htmlspecialchars($file)),
            ));
            $order = $order + 1;
          }
        }

        if($grep == true && !$searchall) // prehladavanie súboru (grep)
        {
          if(is_file($file)){

              $f = @fopen($file, "r");
              if($f==null) continue;

              $ln = 0;
              $cnt = 0;

              $line_block = "";
              $found_in_file = false;
              while ($line = fgets ($f)) // po riadku
              {
                  ++$ln;
                  if ($line===FALSE)
                  {
                    print ("FALSE\n");
                  }
                  else
                  {
                    $pos = findTextPos($search, $line, $regex, $matchcase);
                    //var_dump($pos);die();
                    if((!$regex && $pos !== false) || ($regex && $pos!=0)) // vyhladanie slova
                    {

                      $found_in_file = true;
                      $count_all++;
                      $count_grep++;

                      $param_search = urlencode($search);
                      $param_file = urlencode($file);
                      $param_line_number = str_pad($ln,4,'0', STR_PAD_LEFT);
                      $param_file_name = preg_replace("~(".preg_quote($search).")~i", "<b>$1</b>", htmlspecialchars($file));
                      $param_line_text = preg_replace("~(".preg_quote($search).")~i", "<b>$1</b>", htmlspecialchars(substr($line,0,1500)));

                      if($cnt == 0 ) // subor s najdenim textom
                      {
                        $line_block .= template_search_grep_row_file(array(
                          'url' => "?view=view&search=".$param_search."&path=".$param_file,
                          'order' => $order,
                          'order_next' => $order+1,
                          'param_file_name' => $param_file_name,
                        ));
                        $order = $order + 1;
                      }

                      // riadok v subore
                      $line_block .= template_search_grep_row_in_file(array(
                          'url' => "?view=view&search=".$param_search."&line=".$ln."&path=".$param_file,
                          'order' => $order,
                          'order_next' => $order+1,
                          'param_line_number' => $param_line_number,
                          'param_line_text' => $param_line_text,
                        ));
                      $order = $order + 1;
                      $cnt = $cnt +1;
                    }
                  }
              }
              fclose ($f);

              if ($found_in_file) {
                  $html .= "<div data-file='".htmlspecialchars($file)."'>{$line_block}</div>";
              }

          }
        }
      }
      $time_end = microtime(true);
      $time = $time_end - $time_start;
      $html .= "</div>";
      $html .= "<hr/><span>(".round($time,3)."s)</span><span>(Count: All: {$count_all} Grep: {$count_grep} File: {$count_file})</span>";
    }

    $menu = template_search_menu(array(
      'search' => htmlspecialchars($search),
      'replace_replace' => htmlspecialchars($replace_replace),
      'dir' => htmlspecialchars($dir),
      'ext_check' => ($check_extension ? "checked='yes'" : ""),
      'extensions' => (isset($_REQUEST['ext'])) ? htmlspecialchars($_REQUEST['ext']) : $options['default_extensions'],
      'datelimit' => (($datelimit) ? "checked='yes'" : ""),
      'datestart' => ($datestart!=null) ? date("Y-m-d H:i", $datestart) : date("Y-m-d H:i"),
      'dateend' => ($dateend!=null) ? date("Y-m-d H:i", $dateend) : date("Y-m-d H:i"),
      'file_find' => (isset($_REQUEST['file_find']) || !isset($_REQUEST['find']) ? "checked='yes'" : ""),
      'grep' => (isset($_REQUEST['grep']) || !isset($_REQUEST['find']) ? "checked='yes'" : ""),
      'exclude' => (isset($_REQUEST['exclude']) ? $_REQUEST['exclude'] : ""),
      'matchcase' => (isset($_REQUEST['matchcase'])?"checked='yes'":""),
      'regex' => ($regex ? "checked='yes'" : ""),
      'directory_exist' => $directory_exist,
    ));

    $path_parts = pathinfo($dir);

    echo template_search_body(array(
      'title' => $path_parts['basename'],
      'html' => $html,
      'menu' => $menu,
    ));
  }
  /*********/
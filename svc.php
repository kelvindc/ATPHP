<?php
/*

Copyright 2012 Ancientec Co., Ltd.
ancientec.com

MIT License
Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/


//memcache for saving sessions, set it to false if you want to use default php session function
$memcache = memcache_connect('localhost', 11211);


include_once('include/session.php');
include_once('include/models.php');

//session life time
define('_CoreSessionLifeTime', 86400);
//domain for the cookie
define('_CoreCookieDomain', isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : '');

//initialize variables
$CoreSession = array();
$User = '';
$Cfg['Token'] = 'dcfdb1186007d46268d936a68415e807';

//database setup,change it to your own server information
$Cfg['DBLINK'] = mysql_connect("127.0.0.1:3306", "user","password");

//change the db
mysql_select_db("mydb",$Cfg['DBLINK']);
//force the connection to be utf8
mysql_query("SET NAMES 'utf8';",$Cfg['DBLINK']);

//Session ID is passed using GET or COOKIE, using the variable of SID
if(isset($_GET['SID'])) $CoreSID = CoreValidate($_GET['SID'],'Default', true);
elseif(isset($_COOKIE['SID'])) $CoreSID = CoreValidate($_COOKIE['SID'],'Default', true);
else $CoreSID = '';

//initialize session if id is found
if($CoreSID) CoreSessionInitial();

//initialize the output variable
$Output['Pages'] = array('Total' => 0, 'Page' => 0, 'PerPage' => 0);
$Output['Data'] = array();

if(isset($_GET['m']) && strpos($_GET['m'], '/') === false){
	switch($_GET['m']){
		case 'Login':
			$Output['Error'] = Login();
			if(isset($User['Password'])) unset($User['Password']);
			if($Output['Error'] == '') $Output['Data'][] = $User;
		break;
		//codes to generate the create table sql, do not enable this in production site
		case 'CreateTables':
			CreateTables();
		break;
		case 'ChkLogin':
			if(ChkLogin()){
            	if(isset($User['Password'])) unset($User['Password']);
				$Output['Data'][] = array('ID' => $CoreSession['User']);
			}else $Output['Error'] = 'ERR_Not_Login';
		break;
		case 'Logout':
			CoreSessionDestory($CoreSID);
			CoreCookie($CoreSID, '', -1);
			$CoreSID = '';
		break;
		default:
			$f = $_GET['m'].$_GET['a'];
			if(function_exists($f)) $f();
			else{
				if($_GET['a'] == 'List') $_GET['a'] =  'Listing';
				if(function_exists($_GET['a'])) $_GET['a']($_GET['m']);
				else $Output['Error'] = 'ERR_Acess';
			}
		break;
		
	}

}else $Output['Error'] = 'ERR_Acess';

//process the output
Output();
//commit & save the session data
CoreSessionCommit();
function CoreCookie($k, $v = '', $t = 0, $d = ''){
    if($t == 0) $t = _CoreSessionLifeTime;
    setcookie($k, $v, time()+$t, '/', $d ? $d : _CoreCookieDomain);
}
function Output(){
    global $Output;
    echo json_encode($Output);
}

function CoreValidate($d, $t = 'Default', $r = '', $e = '', $e2 = ''){
    global $Cfg;
    switch($t){
	case 'Number':
            if($r) $k = preg_replace('/[^0-9]/i', '', $d);
	    else{
		if(ereg("^[0-9]+$",$d)) $k = TRUE;
		else $k = FALSE;
	    }
	break;
	case 'Url':
	    if(ereg('^(https?|ftp)\:\/\/([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-z0-9+\$_-]\.?)+)*\/?(\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?(#[a-z_.-][a-z0-9+\$_.-]*)?\$', $d))
	    $k = TRUE;
	    else $k = FALSE;
	break;
	case '<=':
	    if(strlen($d) <= $e)$k = TRUE;
	    else $k = FALSE;
	break;
        case '<':
	    if(strlen($d) < $e)$k = TRUE;
	    else $k = FALSE;
	break;
	case '>=':
	    if(strlen($d) >= $e)$k = TRUE;
	    else $k = FALSE;
	break;
	case '>':
	    if(strlen($d) > $e)$k = TRUE;
	    else $k = FALSE;
	break;
        case '=':
	    if($d == ''.$e)$k = TRUE;
	    else $k = FALSE;
	break;
	case '!=':
	    if($d != ''.$e)$k = TRUE;
            else $k = FALSE;
	break;
	case 'Range':
	    $l = strlen($d);
	    if( $l >= $e && $l <= $e2)$k = TRUE;
	    else $k = FALSE;
	break;
	case 'NotNull':
	    if(strlen($d) > 0) $k = TRUE;
	    else $k = FALSE;
	break;
        case 'NonZero':
	    if($d > 0) $k = TRUE;
	    else $k = FALSE;
	break;
	case 'Within':
	    if(array_search($d, $e) !== FALSE) $k = TRUE;
	    else $k = FALSE;
	break;
	case 'Date':
	    $len = strlen($d);
	    if($len == 10 || $len == 8){
		if($len == 10) $s = array(substr($d, 0, 4), substr($d, 5, 2), substr($d, 8, 2));
		else $s = array(substr($d, 0, 4), substr($d, 4, 2), substr($d, 6, 2));
		if(strlen($s[0]) == 4 && strlen($s[1]) == 2 && strlen($s[2]) == 2 && $s[1] > 0 && $s[1] < 13 && $s[2] > 0 && $s[2] <= date($t,mktime(0, 0, 0, $s[1], 1, $s[0]))){
		    $k = TRUE;
		}else $k = FALSE;
	    }else $k = FALSE;
	break;
	case 'Email':
	    if(ereg('^[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})$', $d)) $k = TRUE;
	    else $k = FALSE;
	break;
	case 'Default':
	default:
            if($r) $k = preg_replace('/[^a-z0-9\.\-\_]/i', '', $d);
	    else{
		if(ereg('^[a-zA-Z0-9\.\-\_]+$',$d)) $k = TRUE;
		else $k = FALSE;
	    }
	break;
    }
    if(!$k) $Cfg['CoreValidate'] = FALSE;
    return $k;
}
function Get($t){Select($t);}
function Select($t){
    global $_POST, $Output, $Cfg;
	if(isset($Cfg['TBL'.$t]['Access']['View']) && $Cfg['TBL'.$t]['Access']['View']()){
		$Output['Error'] = 'ERR_Access';
		return false;
	}
    $f = $Cfg['TBL'.$t]['Fields'];
    $table = $Cfg['TBL'.$t]['Name'];
    if(isset($_POST[$f[0]]))    $s = mysql_fetch_assoc(mysql_query("SELECT * FROM $table WHERE `".$f[0]."`='".addslashes($_POST[$f[0]])."'"));
    else{
        $s = mysql_fetch_assoc(mysql_query("SELECT * FROM $table WHERE ".fields($f,false)));
    }
    if(!$s){
        $Output['Error'] = 'ERR_Select';
    }else $Output['Data'][] = $s;
}

function Listing($t){
    global $_POST, $Output, $Cfg;
	if(isset($Cfg['TBL'.$t]['Access']['View']) && $Cfg['TBL'.$t]['Access']['View']()){
		$Output['Error'] = 'ERR_Access';
		return false;
	}
    $f = $Cfg['TBL'.$t]['Fields'];
    $table = $Cfg['TBL'.$t]['Name'];
    $ff = fields($f,false);
    if($ff != '') $ff = 'WHERE '.$ff;
	if(isset($_POST['p']) && isset($_POST['pp'])){
		$p = ($_POST['p'] - 1)*$_POST['pp'];
		$Output['Pages']['Total'] = mysql_result(mysql_query("SELECT COUNT(ID) FROM $table $ff",$Cfg['DBLINK']), 0);
		$Output['Pages']['PerPage'] = $_POST['pp'];
		$Output['Pages']['Page'] = $p;
		$q = mysql_query("SELECT * FROM $table $ff LIMIT $p,".addslashes($_POST['pp']).$ff);
	}else{
		$q = mysql_query("SELECT * FROM $table ".$ff);
	}
    if(!$q){
        $Output['Error'] = 'ERR_List';
    }else{
        while($s = mysql_fetch_assoc($q)){
            $Output['Data'][] = $s;
        }
    }
}
function Add($t, $g = false){
    global $_POST, $Output, $Cfg;
	if(isset($Cfg['TBL'.$t]['Access']['Edit']) && $Cfg['TBL'.$t]['Access']['Edit']()){
		$Output['Error'] = 'ERR_Access';
		return false;
	}
    $f = $Cfg['TBL'.$t]['Fields'];
    $c = count($f);
    $table = $Cfg['TBL'.$t]['Name'];
    $s = "INSERT INTO $table VALUES (NULL,";
    for($i = 1; $i < $c; $i++){
        $s .= "'".(isset($_POST[$f[$i]]) ? addslashes($_POST[$f[$i]]) : '')."',";
    }
    $s = substr($s, 0, strlen($s)-1).')';
    if(!mysql_query($s)){
        $Output['Error'] = 'ERR_Add';
    }else{
        $_POST[$f[0]] = mysql_insert_id();
        if(!$g) Select($t);
    }
}
function AddBatch($t, $a = true){
    global $_POST, $Output, $Cfg;
	if(isset($Cfg['TBL'.$t]['Access']['Edit']) && $Cfg['TBL'.$t]['Access']['Edit']()){
		$Output['Error'] = 'ERR_Access';
		return false;
	}
    $f = $Cfg['TBL'.$t]['Fields'];
    $c = count($f);
    $y = count($_POST['Data']);
    $table = $Cfg['TBL'.$t]['Name'];
    $s = "INSERT INTO $table VALUES ";
    for($x = 0; $x < $y; $x++){
        if(!isset($_POST['Data'][$x][$f[0]]) || !$_POST['Data'][$x][$f[0]]){
            $s .= '(NULL,';
            for($i = 1; $i < $c; $i++){
            $s .= "'".(isset($_POST['Data'][$x][$f[$i]]) ? addslashes($_POST['Data'][$x][$f[$i]]) : '')."',";
            }
            $s = substr($s, 0, strlen($s)-1).'),';
        }
    }
    $s = substr($s, 0, strlen($s)-1);

    if($s != "INSERT INTO $table VALUES" && !mysql_query($s)){
	
        $Output['Error'] = 'ERR_Add';
    }else{
        if($a) UpdateBatch($t, false);
    }
}
function UpdateBatch($t, $a = true){
    global $_POST, $Cfg;
	if(isset($Cfg['TBL'.$t]['Access']['Edit']) && $Cfg['TBL'.$t]['Access']['Edit']()){
		$Output['Error'] = 'ERR_Access';
		return false;
	}
    $f = $Cfg['TBL'.$t]['Fields'];
    $c = count($f);
    $y = count($_POST['Data']);
    $table = $Cfg['TBL'.$t]['Name'];
    $s = "Update $table SET ";
    for($x = 0; $x < $y; $x++){
		if(isset($_POST['Data'][$x][$f[0]]) && $_POST['Data'][$x][$f[0]]){
			if(isset($_POST['Data'][$x]['Deleted'])){
			mysql_query("DELETE FROM $table WHERE `".$f[0]."`='".addslashes($_POST['Data'][$x][$f[0]])."'");
			}else{
			for($i = 1; $i < $c; $i++){
				if(isset($_POST['Data'][$x][$f[$i]])) $s .= "`".$f[$i]."`='".addslashes($_POST['Data'][$x][$f[$i]])."',";
			}
			$s = substr($s, 0, strlen($s)-1);
			mysql_query($s." WHERE `".$f[0]."`='".addslashes($_POST['Data'][$x][$f[0]])."'");
			$s = "Update $table SET ";
			}
		}
    }
    if($a) AddBatch($t, false);
}
function Update($t){
    global $_POST, $Output, $Cfg;
	if(isset($Cfg['TBL'.$t]['Access']['Edit']) && $Cfg['TBL'.$t]['Access']['Edit']()){
		$Output['Error'] = 'ERR_Access';
		return false;
	}
    $f = $Cfg['TBL'.$t]['Fields'];
    $c = count($f);
    $table = $Cfg['TBL'.$t]['Name'];
    $s = "UPDATE $table SET ";
    for($i = 1; $i < $c; $i++){
        if(isset($_POST[$f[$i]])) $s .= "`".$f[$i]."`='".addslashes($_POST[$f[$i]])."',";
    }
    $s = substr($s, 0, strlen($s)-1);
    if(!mysql_query($s." WHERE `".$f[0]."`='".addslashes($_POST[$f[0]])."'")){
        $Output['Error'] = 'ERR_Update';
    }else $Output['Data'][] = mysql_fetch_assoc(mysql_query("SELECT * FROM ".$Cfg['TBL'.$t]['Name']." WHERE `".$f[0]."`='".addslashes($_POST[$f[0]])."'",$Cfg['DBLINK']));
}

function Delete($t){
    global $_POST,$Output, $Cfg;
	if(isset($Cfg['TBL'.$t]['Access']['Edit']) && $Cfg['TBL'.$t]['Access']['Edit']()){
		$Output['Error'] = 'ERR_Access';
		return false;
	}
    $f = $Cfg['TBL'.$t]['Fields'];
    $table = $Cfg['TBL'.$t]['Name'];
	if($f[count($f)-1] == 'isDeleted') $s = "UPDATE $table SET `isDeleted`='1' WHERE `".$f[0]."`='".addslashes($_POST[$f[0]])."'";
    else $s = "DELETE FROM $table WHERE `".$f[0]."`='".addslashes($_POST[$f[0]])."'";
    if(!mysql_query($s)) $Output['Error'] = 'ERR_Delete';
}
function fields($f, $t){
    global $_POST;
    $s = '';$c = count($f);
    for($i = 1; $i < $c; $i++){
        if(isset($_POST[$f[$i]])) $s .= " && `".$f[$i]."`='".addslashes($_POST[$f[$i]])."'";
    }
    if(!$t) $s = substr($s,3);
    return $s;
}

function CreateTables(){
	global $Cfg;
	
	foreach($Cfg as $k => $v){

		if(strpos($k, 'TBL') == 0 && isset($v['Fields']) && isset($v['Types'])){
			$q .= "DROP TABLE IF EXISTS `".$v['Name']."`;CREATE TABLE IF NOT EXISTS `".$v['Name']."` (\r\n";
			$c = count($v['Fields']);
			for($i = 0; $i < $c; $i++){
				$q .= $v['Fields'][$i]." ".$v['Types'][$i].",\r\n";
			}
			$q .= " PRIMARY KEY (`ID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;\r\n\r\n";
		}
	}
  echo $q;
  exit;
}
function Restore(){
    global $_GET, $_POST;
    $f = '';
    if(isset($_GET['f'])) $f = $_GET['f'];
    if(isset($_POST['f'])) $f = $_POST['f'];
    $zip = new ZipArchive();
    if ($zip->open($f) === TRUE) {
        $s = $zip->getFromName('backup/backup.sql');
        $zip->close();

        file_put_contents('backup/b.sql',$s);
        exec('F:/xampplite/mysql/bin/mysql --user=ap --password=ap < backup/b.sql');

        @unlink('backup/b.sql');
    }
}


?>
<?php
/*
ATPHP
=======
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


/*
Table Rules:
1. Name - the table name, Case Sensitive in Unix
2. First Column in fields requires to be "ID"
3. If all deleted recorded are meant to be kept, the last column should be isDeleted, having an isDeleted on the last column will cause the delete function to mark the column as deleted instead of actually deleting it
*/
$Cfg['TBLUser'] = array('Name' => 'User', 'Fields' => array('ID','User','Password','isDeleted'));
$Cfg['TBLToDo'] = array('Name' => 'ToDo', 'Fields' => array('ID','Name','Date','Completed'));

//customize function to check for access permission
$Cfg['TBLUser']['Access'] = array('Edit' => function(){return isLogin();}, 'View' => function(){return isLogin();});
$Cfg['TBLToDo']['Access'] = array('Edit' => function(){return isLogin();}, 'View' => function(){return isLogin();});

//this is for generating the create table sql statement
$Cfg['TBLUser']['Types'] = array("int(11) NOT NULL AUTO_INCREMENT","varchar(20) NOT NULL","varchar(20) NOT NULL");
$Cfg['TBLTodo']['Types'] = array("int(11) NOT NULL AUTO_INCREMENT","INT(11) NOT NULL","varchar(255) NOT NULL","Date NOT NULL","enum('0','1') NOT NULL default '0'");

//customization sample
//the following function will be trigger when m=Todo, a=List is called
function ToDoList(){
	/*
	do your customize action here
	*/
	
	//call the default function
	Listing('ToDo');
}


//change the following functions based on your own model
function ChkLogin(){
    global $CoreSession,$Cfg, $User;
    if(isset($CoreSession['SID']) && isset($CoreSession['User'])){
        if(isset($CoreSession['Login']) && $CoreSession['Login'] == 1) return true;
        $User = mysql_fetch_assoc(@mysql_query("SELECT * FROM ".$Cfg['TBLUser']['Name']." WHERE `ID`='".addslashes($CoreSession['UID'])."'",$Cfg['DBLINK']));
    }
    return $User ? true : false;
}

function isLogin(){
    global $CoreSession;
    if(isset($CoreSession['Login']) && $CoreSession['Login'] == 1) return true;
}
function Login(){
    global $Cfg, $User, $_POST;
    if(!isset($_POST['Password']) || !$_POST['Password']) return 'ERR_Code_Required';
    $User = mysql_fetch_assoc(@mysql_query("SELECT * FROM ".$Cfg['TBLUser']['Name']." WHERE `User`='".addslashes($_POST['User'])."'",$Cfg['DBLINK']));
    $Err = '';
    if(!$User) $Err = 'ERR_User_Not_Exists';
    elseif($User['Password'] != $_POST['Password']) $Err = 'ERR_Password';
    
    if(!$Err){
        $SID = CoreSessionCreate(array('Password' => $_POST['Password'],'UID'=> $User['ID'],'Login' => 1));
        CoreCookie('SID',$SID);
    }
    return $Err;
}
?>
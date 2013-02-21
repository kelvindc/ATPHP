<?php
/*
ATPHP
=======
Copyright 2013 Ancientec Co., Ltd.
ancientec.com

MIT License

*/

function CoreCacheAdd($key, $var, $expire){
	global $memcache,$_SESSION;
    if(!$memcache){
        $_SESSION['key'] = $var;
        return false;
    }
	$memcache->delete($key, 0);
	return $memcache->set($key, $var, false, $expire);
}
function CoreCacheUpdate($key, $var, $expire){
	global $memcache,$_SESSION;
    if(!$memcache){
        $_SESSION['key'] = $var;
        return false;
    }

	$result = $memcache->replace($key, $var, false, $expire);
	if($result == false) 
	{
	    $result = $memcache->set($key, $var, false, $expire); 
	}
	return $result;
}
function CoreCacheRefresh($key, $expire){
	return CoreCacheUpdate($key, CoreCacheGet($key), $expire);
}
function CoreCacheDelete($key){
	global $memcache;
    if(!$memcache){
        //if(session_id() == $key) 
		session_destroy();
        return true;
    }
	return $memcache->delete($key,0);
}
function CoreCacheGet($key){
	global $memcache,$_SESSION;
    if(!$memcache){
        //if(session_id() != $key) session_start($key);
        return $_SESSION['key'];
    }
	return $memcache->get($key);
}
function CoreSessionId(){
	GLOBAL $CoreSID;
	return $CoreSID;
}
function CoreSessionInitial(){
	GLOBAL $CoreSession, $_COOKIE,$CoreSID;
	if($CoreSID){
	    if($CoreSession = CoreCacheGet($CoreSID)){
		define('_CoreSessionInitialized', 1);
		return true;
	    }else $CoreSID = '';
	}
	return false;
}

function CoreSessionCreate($d){
	GLOBAL $CoreSessionCommited, $CoreSession, $CoreSID;
	$CoreSID = md5(microtime().'Core');
	$d['SID'] = $CoreSID;
	CoreCacheAdd($CoreSID, $d, _CoreSessionLifeTime);
	$CoreSession = $d;
	$CoreSessionCommited = true;
	return $CoreSID;
}
function CoreSessionDestory($sid){
	return $sid ? CoreCacheDelete($sid) : true;
}
function CoreSessionUnset($k){
	GLOBAL $CoreSessionCommited, $CoreSession;
	if(!isset($CoreSession[$k])) return;

	$CoreSessionCommited = false;
	unset($CoreSession[$k]);
}
function CoreSessionUpdate($k, $v){
	GLOBAL $CoreSessionCommited, $CoreSession;
	$CoreSessionCommited = false;
	$CoreSession[$k] = $v;
}
function CoreSessionCommit(){
	GLOBAL $CoreSession,$CoreSessionCommited;
	$sid = CoreSessionId();
	if($sid && (!isset($CoreSessionCommited) || !$CoreSessionCommited)){
		CoreCacheUpdate($sid, $CoreSession,_CoreSessionLifeTime);
		$CoreSessionCommited = true;
	}
}

?>
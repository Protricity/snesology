<?php
use Site\SiteMap;

set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
	switch ($errno) {
		case E_USER_NOTICE:
		case E_USER_WARNING:
		case E_USER_ERROR:
		default:
//			if (!headers_sent()) {
//				header("HTTP/1.1 400 " . preg_replace('/[\n]/', '|', $errstr));
//			}
			break;
	}
	if(!headers_sent()) {
		header("X-Error: $errno: $errstr - $errfile:$errline");
	}
	return false;
});

require_once('Site/SiteMap.php');
include 'config.php';
SiteMap::route();


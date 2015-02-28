<?php
use CPath\Request\Request;
use CPath\Route\CPathMap;

chdir('..');

require_once(dirname(__DIR__) . '/Site/SiteMap.php');
require_once(dirname(__DIR__) . '/config.php');

$Request = Request::create('CLI /cpath/test', array(), new \CPath\Render\Text\TextMimeType());
//$Request->setMimeType(new TextMimeType());
CPathMap::route($Request);
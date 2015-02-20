<?php
use CPath\Request\Request;
use CPath\Route\CPathMap;

require_once('Site/SiteMap.php');

$Request = Request::create('CLI /cpath/test', array(), new \CPath\Render\Text\TextMimeType());
//$Request->setMimeType(new TextMimeType());
CPathMap::route($Request);
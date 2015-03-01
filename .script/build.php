<?php
use CPath\Render\Text\TextMimeType;
use CPath\Request\Request;

chdir('..');

$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once(dirname(__DIR__) . '/Site/SiteMap.php');
require_once(dirname(__DIR__) . '/config.php');
$Request = Request::create('/cpath/build', array(), new TextMimeType());
$Build = new \CPath\Build\Handlers\BuildRequestHandler();
$Response = $Build->execute($Request);
echo $Response->getMessage();
//CPathMap::route($Request);
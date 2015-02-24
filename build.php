<?php
use CPath\Render\Text\TextMimeType;
use CPath\Request\Request;

$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once('Site/SiteMap.php');
require_once('config.php');
$Request = Request::create('/cpath/build', array(), new TextMimeType());
$Build = new \CPath\Build\Handlers\BuildRequestHandler();
$Response = $Build->execute($Request);
echo $Response->getMessage();
//CPathMap::route($Request);
<?php
use CPath\Request\Request;
use CPath\Route\CPathMap;


require_once(dirname(__DIR__) . '/Site/SiteMap.php');

$SocketServer = new \Site\Relay\WebSocket('/snesology/');
$SocketServer->run();
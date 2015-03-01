<?php
use CPath\Request\Request;
use CPath\Route\CPathMap;


require_once(dirname(__DIR__) . '/Site/SiteMap.php');
require_once(dirname(__DIR__) . '/config.php');

$SocketServer = new \Site\Relay\WebSocket();
$SocketServer->run();
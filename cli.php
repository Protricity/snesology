<?php
require_once('Site/SiteMap.php');
\Site\SiteMap::route() ||
\CPath\Route\CPathMap::route();

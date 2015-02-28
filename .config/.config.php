<?php
error_reporting(E_ALL & ~E_NOTICE);
\CPath\Build\BuildConfig::$BUILD_FILES = true;
\Site\Config::$RequireInvite = false;
\Site\Config::$ChatSocketURI = 'ws://snesology.com:7845/';
\Site\PGP\PGPConfig::$GPGPath = '/usr/bin/gpg';
//\Site\PGP\PGPConfig::$GPGPath = 'C:/cygwin/bin/gpg.exe';
//\Site\DB\DBConfig::$DB_NAME = 'ari_snesology';
//\Site\DB\DBConfig::$DB_PASSWORD = 'password';
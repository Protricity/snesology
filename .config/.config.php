<?php
error_reporting(E_ALL & ~E_NOTICE);
\CPath\Build\BuildConfig::$BUILD_FILES = false;

\Site\Config::$RequireInvite = false;
\Site\Config::$ChatSocketURI = 'ws://snesology.com:7846/';

\Site\PGP\PGPConfig::$GPGPath = '/usr/bin/gpg';
//\Site\PGP\PGPConfig::$GPGPath = 'C:/cygwin/bin/gpg.exe';

\Site\DB\DBConfig::$DB_NAME = 'abobo_snesology';
\Site\DB\DBConfig::$DB_PASSWORD = 'password';
\Site\DB\DBConfig::$DB_USERNAME = 'abobo_snesology';

















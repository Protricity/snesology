<?php

\Site\PGP\PGPConfig::$GPGPath = '/usr/bin/gpg';

error_reporting(E_ALL & ~E_NOTICE);
\Site\Config::$RequireInvite = false;

\Site\Config::$ChatSocketURI = 'ws://snesology.com:7845/';

// \CPath\Build\BuildConfig::$BUILD_FILES = true;

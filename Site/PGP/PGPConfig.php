<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/3/14
 * Time: 7:33 PM
 */
namespace Site\PGP;

class PGPConfig
{
	static $DEFAULT_MESSAGE_HEADERS = array(
		'Version' => 'GnuPG v1',
	);

	static $KEYRING_USER = '.pubring.user.gpg';
	//static $KEYRING_TEST_USER = '.pubring.user.test.gpg';
//	static $KEYRING_GRANT = '.pubring.grant.gpg';
	static $KEYRING_SESSION = '.pubring.session.gpg';
	static $KEYRING_WALLET = '.pubring.wallet.gpg';

	static $GPGPath = 'gpg';
	static $GPGEnv = array(
		'PATH' => '/bin;/usr/bin;C:/cygwin/bin'
	);

	static $HomeDir = null;
	static $KeyRingFormat = '.pubring.%s.gpg';

	static $UseTempFiles = true;
}

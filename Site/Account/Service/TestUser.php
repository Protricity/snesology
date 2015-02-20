<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/22/14
 * Time: 2:06 PM
 */
namespace Site\Account\Service;

use CPath\Request\IRequest;

class TestUser extends AbstractServiceUser
{
	const USERNAME = 'test';
	const PASSPHRASE = 'test';
	public function __construct(IRequest $Request, $username=null, $passphrase=null) {
		parent::__construct($Request, static::USERNAME . ($username ? '-' . $username : ''), $passphrase ?: static::PASSPHRASE);
	}

}
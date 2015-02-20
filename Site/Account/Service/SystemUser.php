<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/22/14
 * Time: 1:45 PM
 */
namespace Site\Account\Service;

use CPath\Request\IRequest;

class SystemUser extends AbstractServiceUser
{
	const USERNAME = 'system';
	const PASSPHRASE = null;
	public function __construct(IRequest $Request) {
		parent::__construct($Request, static::USERNAME, static::PASSPHRASE);
	}

}

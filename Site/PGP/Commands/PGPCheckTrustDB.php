<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/18/2014
 * Time: 5:40 PM
 */
namespace Site\PGP\Commands;

use CPath\Request\IRequest;
use Site\PGP\Exceptions\PGPPrivateKeyNotFound;
use Site\PGP\PGPCommand;

class PGPCheckTrustDB extends PGPCommand
{
	const STD_ERR_FIRST = true;
	const ALLOW_STD_ERROR = true;
	const CMD = "--batch --check-trustdb";

	public function __construct() {
		parent::__construct(static::CMD);
	}

	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws Exceptions\PGPCommandException
	 * @throws PGPPrivateKeyNotFound
	 * @throws \Exception
	 * @return PGPImportPublicKeyCommand the execution response
	 */
	function execute(IRequest $Request = null) {
		$Response = parent::execute($Request);

		if ($Exs = $Response->getExceptions())
			throw $Exs[0];

		return $this->update("Trust DB will be checked");
	}
}

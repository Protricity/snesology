<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/19/14
 * Time: 3:34 PM
 */
namespace Site\PGP\Commands;

use CPath\Request\IRequest;
use Site\PGP\Exceptions\PGPPrivateKeyNotFound;
use Site\PGP\PGPCommand;

class PGPDeletePublicKeyCommand extends PGPCommand
{
	const ALLOW_STD_ERROR = true;
	const CMD             = "--batch --status-fd 1 --delete-key --yes %s";

	public function __construct($fingerprint) {
		$command = sprintf(static::CMD, preg_replace('/[^\w\d]*/', '', $fingerprint));
		parent::__construct($command);
	}

	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws Exceptions\PGPCommandException
	 * @throws PGPPrivateKeyNotFound
	 * @throws \Exception
	 * @return PGPImportPublicKeyCommand the execution response
	 */
	function execute(IRequest $Request=null) {
		$Response = parent::execute($Request);

		if ($Exs = $Response->getExceptions())
			throw $Exs[0];

		return $this->update("Public key deleted");
	}
}


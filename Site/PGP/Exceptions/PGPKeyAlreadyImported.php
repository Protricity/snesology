<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 9/29/14
 * Time: 11:40 PM
 */
namespace Site\PGP\Exceptions;

use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\PGPCommand;

class PGPKeyAlreadyImported extends PGPCommandException
{
	private $mKeyID;
	function __construct(PGPCommand $CMD, $keyID, $message, $statusCode = null) {
		$this->mKeyID = $keyID;
		parent::__construct($CMD, $message, $statusCode);
	}

	public function getKeyID() {
		return $this->mKeyID;
	}
}
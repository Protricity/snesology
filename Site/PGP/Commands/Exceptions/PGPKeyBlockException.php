<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/5/14
 * Time: 11:56 PM
 */
namespace Site\PGP\Commands\Exceptions;

use CPath\Request\IRequest;
use Site\PGP\PGPCommand;

class PGPKeyBlockException extends PGPCommandException
{
	public function __construct(PGPCommand $CMD, $message = null, $statusCode = null) {
		parent::__construct($CMD, $message, $statusCode);
	}

	public function getKeyBlockPath() {
		if (!preg_match('/gpg: keyblock resource (.*):/i', $this->getMessage(), $matches))
			throw new \InvalidArgumentException("Key block could not be parsed: " . $this->getMessage());
		return trim($matches[1], "'`");
	}

	public function tryCreateMissingKeyBlock(IRequest $Request) {
		$path = $this->getKeyBlockPath();
		if(file_exists($path))
			throw new \InvalidArgumentException("Key block already exists: " . $path);
		$i = file_put_contents($path, '');
		if(!$i) {
			$Request->log("Could not create key block: " . $path);
			throw $this;
		}
		$Request->log("Missing key block created: " . $path);
	}
}
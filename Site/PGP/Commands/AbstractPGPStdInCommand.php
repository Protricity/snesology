<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/24/2014
 * Time: 12:58 AM
 */
namespace Site\PGP\Commands;

use CPath\Request\IRequest;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\PGPCommand;

abstract class AbstractPGPStdInCommand extends PGPCommand
{
	const ALLOW_STD_ERROR = true;
	const ALLOWED_CMD_REGEX = '\w\s\/:._-';

	const CMD = null; // " --trust-model always --batch --encrypt";

	public function __construct($command=null) {
		$command ?: $command = static::CMD;
		parent::__construct($command);
	}

	function getOutputString() {
		$output = $this->getOutputString();
		if (!$output)
			throw new PGPCommandException($this, "No output\n" . $this->getCommandResponse()->getSTDErr());
		return $output;
	}

	/**
	 * @param IRequest $Request
	 * @param String|null $stdIn
	 * @param null $filePath
	 * @throws
	 * @throws PGPCommandException
	 * @throws \Exception
	 * @return \Site\PGP\PGPCommandResponse
	 */
	function execute(IRequest $Request = null, $stdIn = null, $filePath=null) {
		if($filePath) {
			$this->setCommand('%s ' . ' -o- ' . $filePath);

		} else if ($stdIn) {
			$this->addSTDIn($stdIn);

		} else {
			throw new \InvalidArgumentException("STDin or Filepath required");
		}

		$Response = parent::execute($Request);

		if ($Exs = $Response->getExceptions()) {
			throw $Exs[0];
		}

		if ($filePath) {
			$Response->update("File processed: " . $filePath);

		} else {
			$Response->update("String encrypted");
		}

		return $Response;
	}
}
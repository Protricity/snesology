<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/18/2014
 * Time: 7:22 PM
 */
namespace Site\PGP\Commands;

use CPath\Request\IRequest;
use CPath\Request\Log\ILogListener;
use Site\PGP\PGPCommand;

class PGPEditKeyCommand extends PGPCommand
{
	const LOG_STDOUT = ILogListener::VERBOSE;
	const ALLOW_STD_ERROR = true;
	const CMD             = "--passphrase-fd 0 --status-fd 1 --command-fd 0 --edit-key";

	private $mFingerprint;
	private $mCommand;
	public function __construct($fingerprint, $command='trust') {
		$this->mFingerprint = $fingerprint;
		$this->mCommand = $command;
		parent::__construct('');
	}

	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @param String|null $stdIn
	 * @throws
	 * @throws \Exception
	 * @return PGPImportPublicKeyCommand the execution response
	 */
	function execute(IRequest $Request = null, $stdIn=null) {
		$tmpFile = tmpfile();
		$info    = stream_get_meta_data($tmpFile);
		$tmpPath = $info['uri'];
		fwrite($tmpFile, $stdIn);

		$command = static::CMD . ' ' . $this->mFingerprint . ' ' . $this->mCommand;
		$command = "cat -e {$tmpPath} | %s --no-tty " . $command;
		$this->setCommand($command);

		$Response = parent::execute($Request);

		if ($Exs = $Response->getExceptions())
			throw $Exs[0];

		return $this->update("Edit complete");
	}
}
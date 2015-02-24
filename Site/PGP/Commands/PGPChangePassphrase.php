<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/21/14
 * Time: 3:27 PM
 */
namespace Site\PGP\Commands;

use CPath\Request\IRequest;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\PGPCommand;
use Site\PGP\PGPCommandResponse;

class PGPChangePassphrase extends PGPCommand
{
	const INCLUDE_STDERR = true;
	const STD_ERR_FIRST = true;
	const ALLOW_STD_ERROR = false;
//	const CMD             = "printf '%s' | %s --no-tty --status-fd 2 --command-fd 0 --edit-key %s passwd";
//	const CMD             = "--no-tty --status-fd 2 --command-fd 0 --edit-key %s passwd";
	const CMD             = "--verbose --status-fd 2 --command-fd 0 --no-tty --edit-key %s passwd ";
//	const CMD             = " --edit-key %s passwd";
// --status-fd 1 --command-fd 0 --no-tty
//$   echo -e "def346\nomfg\nomfg\nsave\n" | gpg --command-fd 0 --edit-key omfgs passwd


	private $mFingerprint;
	private $mNewPassphrase;
	private $mOldPassphrase;

	public function __construct($fingerprint, $newPassword, $oldPassword=null) {
		$this->mFingerprint = $fingerprint;
		$this->mNewPassphrase = $newPassword;
		$this->mOldPassphrase = $oldPassword;
		parent::__construct(self::CMD);
	}

	public function setPassphrase($passphrase) {
		$this->setOption('passphrase', $passphrase);
	}


	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @param null $oldPassword
	 * @param String|null $newPassword
	 * @throws Exceptions\PGPCommandException
	 * @throws \Exception
	 * @return PGPCommandResponse the execution response
	 */
	function execute(IRequest $Request=null) {
		$stdIn = array();
		if($this->mOldPassphrase)
			$stdIn[] = $this->mOldPassphrase;
//		$stdIn[] = 'passwd';
//		$stdIn[] = $this->mNewPassphrase;
//		$stdIn[] = $this->mNewPassphrase;
		$stdIn[] = 'save';
		$stdIn[] = 'quit';

		$this->addSTDIn($this->mNewPassphrase);
		$this->addSTDIn($this->mNewPassphrase);

		$tmpFile = tmpfile();
		$info    = stream_get_meta_data($tmpFile);
		$tmpPath = $info['uri'];
		fwrite($tmpFile, implode("\n", $stdIn));

		$command = sprintf(static::CMD, $this->mFingerprint) . ' ' . $tmpPath;
		$this->setCommand($command);


		$Response = parent::execute($Request);
		$output = $this->getOutputString();

		if(strpos($output, 'GOOD_PASSPHRASE') === false)
			throw new PGPCommandException($this, "Passphrase Error: string not found: GOOD_PASSPHRASE");
		if(strpos($output, 'NEED_PASSPHRASE_SYM') === false)
			throw new PGPCommandException($this, "Passphrase Error: string not found: NEED_PASSPHRASE_SYM");
		if(strpos($output, 'GOT_IT') === false)
			throw new PGPCommandException($this, "Passphrase Error: string not found: GOT_IT");
		if(strpos($output, 'GET_BOOL keyedit.save.okay') === false)
			throw new PGPCommandException($this, "Passphrase Error: string not found: GET_BOOL keyedit.save.okay");

		return $Response->update("Passphrase apparently changed");
	}

//
//	/**
//	 * Execute a command and return a response. Does not render
//	 * @param IRequest $Request
//	 * @param null $oldPassword
//	 * @param String|null $newPassword
//	 * @throws Exceptions\PGPCommandException
//	 * @throws \Exception
//	 * @return PGPCommandResponse the execution response
//	 */
//	function execute1(IRequest $Request=null, $oldPassword = null, $newPassword = null) {
//		$tmpFile = tmpfile();
//		$info    = stream_get_meta_data($tmpFile);
//		$tmpPath = $info['uri'];
//		fwrite($tmpFile, $oldPassword . "\n" . $newPassword . "\n" . $newPassword . "\nsave\nquit\n");
//
//		$this->setCommand(static::CMD . ' ' . $tmpPath);
//
//		$command = sprintf(static::CMD, $this->mFingerprint);
//		$command = "cat -e {$tmpPath} | %s --no-tty " . $command;
//		$this->setCommand($command);
//		$Response = parent::execute($Request);
//		fclose($tmpFile);
//
//		if ($Exs = $Response->getExceptions())
//			throw $Exs[0];
//
//		$output = $Response->getOutput();
//		if(!$output) {
//			throw new PGPCommandException($this, "No output"); }
//
//		$this->log($output, static::VERBOSE);
//
//		if(strpos($output, 'GOOD_PASSPHRASE') === false)
//			throw new PGPCommandException($this, "Passphrase Error: string not found: GOOD_PASSPHRASE");
//		if(strpos($output, 'NEED_PASSPHRASE_SYM') === false)
//			throw new PGPCommandException($this, "Passphrase Error: string not found: NEED_PASSPHRASE_SYM");
//		if(strpos($output, 'GOT_IT') === false)
//			throw new PGPCommandException($this, "Passphrase Error: string not found: GOT_IT");
//
//		return $Response->update("Passphrase apparently changed");
//	}
}
//[GNUPG:] GOOD_PASSPHRASE
//[GNUPG:] NEED_PASSPHRASE_SYM 3 3 2
//[GNUPG:] GET_LINE keyedit.prompt
//[GNUPG:] GOT_IT
//[GNUPG:] GET_LINE keyedit.prompt
//[GNUPG:] GOT_IT
//[GNUPG:] GET_LINE keyedit.prompt
//[GNUPG:] GOT_IT
//[GNUPG:] GET_LINE keyedit.prompt
//[GNUPG:] GOT_IT
//[GNUPG:] GET_LINE keyedit.prompt
//[GNUPG:] GOT_IT
//[GNUPG:] GET_BOOL keyedit.save.okay
//[GNUPG:] GOT_IT

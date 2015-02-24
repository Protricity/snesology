<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/16/14
 * Time: 6:49 PM
 */
namespace Site\PGP;

use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Request\Executable\IExecutable;
use CPath\Request\IRequest;
use CPath\Request\Log\ILogListener;
use CPath\Response\IResponse;
use Site\PGP\Commands\Exceptions\PGPCommandException;

class PGPCommand implements IExecutable, ILogListener, IResponse, IKeyMap
{
	const ALLOWED_CMD_REGEX = '\w\s\\\\\/:%._-';
	const ALLOWED_OPTION_VALUE_CHARACTERS = '\w';

	const OPTION_PASSPHRASE = '--passphrase';
	const OPTION_DRY_RUN = '--dry-run';
	const OPTION_HOMEDIR = '--homedir';
	const OPTION_KEYRING = '--keyring';
	const OPTION_PRIMARY_KEYRING = '--primary-keyring';
	const OPTION_OPTIONS_FILE = '--options';

	const OPTION_ARMOR = '--armor';
	const OPTION_SIGN = '--sign';
	const OPTION_LOCAL_USER = '--local-user';

	const PASSTHROUGH = false;
//	const ALLOW_STD_ERROR = false;

	const READ_STDOUT = true;
	const RECORD_STDOUT = true;
	const LOG_STDOUT = null;

	const INCLUDE_STDERR = false;

	private $mSTDErr = array();
	private $mSTDOut = array();
	private $mSTDIn = array();

	private $mOptions = array();
	/** @var PGPCommandResponse */
	private $mCommandResponse = null;

	/** @var IRequest */
	private $mRequest = null;

	private $mCommand = null;

	/** @var ILogListener[] */
	private $mLogListeners = array();
	public function __construct($command=null) {
		if($command)
			$this->setCommand($command);
		if(PGPConfig::$HomeDir)
			$this->setHomeDir(PGPConfig::$HomeDir);
	}

	function setCommand($command) {
		if(static::ALLOWED_CMD_REGEX && preg_match('/[^' . static::ALLOWED_CMD_REGEX . ']/', $command, $matches))
			throw new \InvalidArgumentException("Invalid command characters found: (" . $matches[0] . ') => ' . htmlspecialchars($command));
//		if($protectPasswords)
//			$cmd = preg_replace('/' . self::OPTION_PASSPHRASE . ' ([^ ]+)/i', '', $cmd); // self::OPTION_PASSPHRASE . ' *****'
		$this->mCommand = $command;
	}

	function addSTDIn($stdin) {
		$this->mSTDIn[] = $stdin;
	}

	function getCommand($protectPasswords=true) {
		$cmd = PGPConfig::$GPGPath;
		foreach($this->mOptions as $name => $value) {
			if(is_array($value)) {
				foreach($value as $val) {
					$cmd .= ' ' . $name;
					if($val !== null)
						$cmd .= ' ' . $val;
				}
			} else {
				$cmd .= ' ' . $name;
				if ($value !== null)
					$cmd .= ' ' . $value;
			}
		}
		if(strpos($this->mCommand, '%s'))
			$cmd = sprintf($this->mCommand, $cmd);
		else
			$cmd .= ' ' . $this->mCommand;

		if($protectPasswords)
			$cmd = preg_replace('/' . self::OPTION_PASSPHRASE . ' ([^ ]+)/i', '', $cmd); // self::OPTION_PASSPHRASE . ' *****'

		return $cmd;
	}

	/**
	 * Get the request status code
	 * @return int
	 */
	function getCode() {
		if($this->mCommandResponse)
			return $this->mCommandResponse->getCode();
		return IResponse::HTTP_ERROR;
	}

	/**
	 * Get the IResponse Message
	 * @return String
	 */
	function getMessage() {
		if($this->mCommandResponse)
			return $this->mCommandResponse->getMessage();
		return "Command not executed";
	}

	protected function update($message=null, $code=IResponse::HTTP_SUCCESS) {
		if(!$this->mCommandResponse)
			$this->mCommandResponse = new PGPCommandResponse($this, "", "", 0);

		$this->mCommandResponse->update($message, $code);
		$this->log($message);
		return $this;
	}

	public function appendOption($option, $value=null) {
		if(static::ALLOWED_CMD_REGEX && preg_match('/[^' . static::ALLOWED_CMD_REGEX . ']/', $value, $matches))
			throw new \InvalidArgumentException("Option value for '{$option}' has disallowed characters: (" . $matches[0] . ') => ' . htmlspecialchars($value));

		if($option[0] !== '-')
			$option = '--' . $option; // substr($option, $option[1] === '-' ? 2 : 1);

		if(isset($this->mOptions[$option])) {
			if(!is_array($this->mOptions[$option]))
				$this->mOptions[$option] = array($this->mOptions[$option]);
			$this->mOptions[$option][] = $value;
		} else {
			$this->mOptions[$option] = $value;
		}
		return $this;
	}

	public function removeOption($optionName) {
		unset($this->mOptions[$optionName]);
		return $this;
	}

	public function setOption($optionName, $value=null) {
		if($optionName[0] !== '-')
			$optionName = '--' . $optionName; // substr($option, $option[1] === '-' ? 2 : 1);

		$this->mOptions[$optionName] = $value;
		return $this;
	}

	public function getOption($optionName) {
		if($optionName[0] !== '-')
			$optionName = '--' . $optionName;
		return isset($this->mOptions[$optionName])
			? $this->mOptions[$optionName]
			: null;
	}


	public function addRecipient($recipient) {
		if(!$recipient) {
			throw new \InvalidArgumentException("Invalid recipient"); }
		$this->appendOption('recipient', $recipient);
	}

	public function setArmored() {
		$this->setOption(self::OPTION_ARMOR);
	}

	public function localUser($userID) {
		if(!$userID)
			throw new \InvalidArgumentException("Invalid local user");
//		$this->setOption(self::OPTION_SIGN);
		$this->setOption(self::OPTION_LOCAL_USER, $userID);
	}

	public function addKeyRing($path) {
		foreach(func_get_args() as $arg) {
			foreach((array)$arg as $path) {
				if(strpos($path, '.') === false)
					$path = sprintf(PGPConfig::$KeyRingFormat, $path);
				$this->appendOption(static::OPTION_KEYRING, $path);
			}
		}
		return $this;
	}

	public function setPrimaryKeyRing($path) {
		return $this->appendOption(static::OPTION_PRIMARY_KEYRING, $path);
	}


	public function setHomeDir($path) {
//		if($realPath = realpath($path))
		return $this->setOption(static::OPTION_HOMEDIR, str_replace('\\', '/', $path));
//		throw new \InvalidArgumentException("Invalid or non-existant Path: " . $path);
	}

	public function setPassphrase($passphrase) {
		if(!$passphrase) {
			throw new \InvalidArgumentException("Invalid passphrase"); }
		$this->setOption(self::OPTION_PASSPHRASE, $passphrase);
		return $this;
	}


	public function setDryRun() {
		return $this->appendOption(static::OPTION_DRY_RUN);
	}

	function getOutputString() {
		$output = $this->getSTDOut();
		if (!$output && static::RECORD_STDOUT) {
			throw new PGPCommandException($this, "No output\n" . implode("\n", $this->mSTDErr));}
		return $output;
	}

	public function getSTDOut() {
		return implode("", $this->mSTDOut);
	}

	protected function readLine($line) {
	}

	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws PGPCommandException
	 * @return PGPCommandResponse the execution response
	 */
	function execute(IRequest $Request) {
		$command = $this->getCommand(false);

		if(static::ALLOWED_CMD_REGEX && preg_match('/[^' . static::ALLOWED_CMD_REGEX . ']/', $command, $matches))
			throw new \InvalidArgumentException("Invalid command characters found: (" . $matches[0] . ') => ' . htmlspecialchars($command));

		$this->log("$ {$command}", $this::VERBOSE);
		$t = microtime(true);
		if(static::INCLUDE_STDERR)
			$command = "($command)2>&1";
		$process=proc_open($command,
			array(
				0=>array('pipe', 'r'),
				1=>array('pipe', 'w'),
				2=>array('pipe', 'w')
			),
			$pipes,
			null,
			PGPConfig::$GPGEnv);

		$l = 0;
		foreach($this->mSTDIn as $stdIn)
			$l += fwrite($pipes[0], $stdIn);
		fclose($pipes[0]);

		while(!feof($pipes[1]) && $line = fgets($pipes[1])) {
			$this->readLine($line, 1);
			if(static::RECORD_STDOUT)
				$this->mSTDOut[] = $line;
			if(static::LOG_STDOUT)
				$this->log(trim($line), static::LOG_STDOUT);
		}
		fclose($pipes[1]);

		$this->mSTDErr = array();
		if(!static::INCLUDE_STDERR) {
			while(!feof($pipes[2]) && $line = fgets($pipes[2])) {
				$this->readLine($line, 2);
				$this->mSTDErr[] = $line;
				if(static::LOG_STDOUT)
					$this->log(trim($line), static::LOG_STDOUT);
			}
			fclose($pipes[2]);
		}

		$returnValue = proc_close($process);
		$this->log(sprintf("  Execution complete(%d) in %.2f seconds.\n", $returnValue, microtime(true) - $t), $this::VERBOSE);

		$Response = new PGPCommandResponse($this, $returnValue, implode("\n", $this->mSTDErr));
		$Exs = $Response->getExceptions();
		if ($Exs) {
			throw $Exs[0]; }

		return $this->mCommandResponse = $Response;
	}

	protected function getCommandResponse() {
		return $this->mCommandResponse;
	}

	/**
	 * Add a log entry
	 * @param mixed $msg The log message
	 * @param int $flags [optional] log flags
	 * @return int the number of listeners that processed the log entry
	 */
	function log($msg, $flags = 0) {
		$c = 0;
		foreach($this->mLogListeners as $Log)
			$c += $Log->log($msg, $flags);
		if($this->mRequest)
			$c += $this->mRequest->log($msg, $flags);
		return $c;
	}

	/**
	 * Add a log listener callback
	 * @param ILogListener $Listener
	 * @return void
	 * @throws \InvalidArgumentException if this log listener inst does not accept additional listeners
	 */
	function addLogListener(ILogListener $Listener) {
		if(!in_array($Listener, $this->mLogListeners))
			$this->mLogListeners[] = $Listener;
	}

	function removeLogListener(ILogListener $Listener) {
		foreach($this->mLogListeners as $i => $Listener2) {
			if($Listener === $Listener2) {
				unset($this->mLogListeners[$i]);
				return true;
			}
		}
		return false;
	}

	/**
	 * Map data to the key map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
	function mapKeys(IKeyMapper $Map) {
		$Map->map(IResponse::STR_MESSAGE, $this->getMessage());
		$Map->map(IResponse::STR_CODE, $this->getCode());
		$Map->map('class', basename(get_class($this)));
		//$Map->map('command', $this->getCommandResponse());
	}

	// Static


	/**
	 * @see http://tools.ietf.org/html/rfc4880#section-6
	 * @see http://tools.ietf.org/html/rfc4880#section-6.2
	 * @see http://tools.ietf.org/html/rfc2045
	 * @param $data
	 * @param string $marker
	 * @param array $headers
	 * @return string
	 */
	static function enarmor($data, $marker = 'MESSAGE', array $headers = null) {
		$headers = PGPConfig::$DEFAULT_MESSAGE_HEADERS + (array)$headers + PGPConfig::$DEFAULT_MESSAGE_HEADERS;

		$text = self::header($marker) . "\n";
		foreach ($headers as $key => $value)
			$text .= $key . ': ' . (string)$value . "\n";

		$text .= "\n" . implode("\n", str_split(base64_encode($data), 64));
		$text .= "\n".'=' . base64_encode(substr(pack('N', self::crc24($data)), 1)) . "\n";
		$text .= self::footer($marker) . "\n";
		return $text;
	}

	/**
	 * @see http://tools.ietf.org/html/rfc4880#section-6
	 * @see http://tools.ietf.org/html/rfc2045
	 */
	static function unarmor($text, $header = 'PGP PUBLIC KEY BLOCK') {
		$header = self::header($header);
		$text = str_replace(array("\r\n", "\r"), array("\n", ''), $text);
		if (($pos1 = strpos($text, $header)) !== FALSE &&
			($pos1 = strpos($text, "\n\n", $pos1 += strlen($header))) !== FALSE &&
			($pos2 = strpos($text, "\n=", $pos1 += 2)) !== FALSE) {
			return base64_decode($text = substr($text, $pos1, $pos2 - $pos1));
		}
	}


	/**
	 * @see http://tools.ietf.org/html/rfc4880#section-6.2
	 */
	static function header($marker) {
		return '-----BEGIN ' . strtoupper((string)$marker) . '-----';
	}

	/**
	 * @see http://tools.ietf.org/html/rfc4880#section-6.2
	 */
	static function footer($marker) {
		return '-----END ' . strtoupper((string)$marker) . '-----';
	}


	/**
	 * @see http://tools.ietf.org/html/rfc4880#section-6
	 * @see http://tools.ietf.org/html/rfc4880#section-6.1
	 */
	static function crc24($data) {
		$crc = 0x00b704ce;
		for ($i = 0; $i < strlen($data); $i++) {
			$crc ^= (ord($data[$i]) & 255) << 16;
			for ($j = 0; $j < 8; $j++) {
				$crc <<= 1;
				if ($crc & 0x01000000) {
					$crc ^= 0x01864cfb;
				}
			}
		}
		return $crc & 0x00ffffff;
	}
}
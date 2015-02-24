<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/16/14
 * Time: 8:22 PM
 */
namespace Site\PGP\Commands;

use CPath\Data\Map\IKeyMapper;
use CPath\Data\Map\ISequenceMap;
use CPath\Data\Map\ISequenceMapper;
use CPath\Data\Map\SequenceMapWrapper;
use CPath\Request\IRequest;
use CPath\Request\Log\ILogListener;
use CPath\Response\IResponse;
use CPath\UnitTest\ITestable;
use CPath\UnitTest\IUnitTestRequest;
use Site\Account\User;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\Commands\Exceptions\PGPNotFoundException;
use Site\PGP\Commands\Exceptions\PGPSearchException;
use Site\PGP\PGPCommand;

class PGPSearchCommand extends PGPCommand implements ISequenceMap, ITestable
{
	const ALLOWED_CMD_REGEX = '\w\s\/:._@*=-';
	const ALLOWED_OPTION_VALUE_CHARACTERS = '\w';
	const ALLOW_STD_ERROR = true;

	const LOG_STDOUT = ILogListener::VERBOSE;

	const RECORD_STDOUT = false;

	const INCLUDE_STDERR = true;

	const OPTION_SHOW_KEYRING = '--show-keyring';

	const CMD = "--fingerprint %s";

	private $mFingerprint = null;
	private $mEmail = null;
	private $mUserID = null;
	private $mComment = null;
	private $mPubDate = null;
	private $mPubShortCode = null;
	private $mSubDate = null;
	private $mSubShortCode = null;

	private $mCount = 0;

	/** @var \Closure */
	private $mCallback = null;

	private $mSearch = null;
	private $mKeyRing = null;


	function __construct($search, $searchMode='=', $allowedRegex = '/[^a-zA-Z0-9_-]/') {
		if($allowedRegex && preg_match($allowedRegex, $search))
			throw new \InvalidArgumentException("Invalid search characters found: " . htmlspecialchars($search));
		if(preg_match('/^([0-9A-F]{8}|[0-9A-F]{16}|[0-9A-F]{40})$/i', $search))
			$searchMode = null;
		$this->mSearch = $searchMode . $search;
		parent::__construct(sprintf(static::CMD, $this->mSearch));
		//$this->setShowKeyRing();
	}

	public function setShowKeyRing() {
		return $this->setOption(self::OPTION_SHOW_KEYRING);
	}

	public function getSearchString() {
		return $this->mSearch;
	}

	protected function reset() {
		$this->mFingerprint = null;
		$this->mEmail = null;
		$this->mUserID = null;
		$this->mPubDate = null;
		$this->mPubShortCode = null;
		$this->mSubDate = null;
		$this->mSubShortCode = null;
	}

	protected function readLine($line) {
		if($line === false)
			return $line;

		switch (substr($line, 0, 3)) {
			default:
				$line = trim($line);
				if (preg_match('/Key fingerprint = (.*)$/i', $line, $matches)) {
					if ($this->mFingerprint !== null) {
						$this->mCount++;
						$ret = ($callback = $this->mCallback) && $callback($this);
						$this->reset();
						if($ret)
							return false;
					}
					$this->mFingerprint = trim($matches[1]);

				} else if (preg_match('/Keyring: (.*)$/i', $line, $matches)) {
					$this->mKeyRing = trim($matches[1]);

				}

//					throw new RequestException("Invalid line: " . $line);
				break;

			case 'pub':

				if (!preg_match('/^pub\s+([^\/]+)\/([^ ]+)\s+(\d\d\d\d-\d\d-\d\d)/i', trim($line), $matches))
					throw new PGPCommandException($this, "Invalid line: " . $line);

				$this->mPubShortCode = $matches[2];
				$this->mPubDate = $matches[3];
				break;

			case 'uid':
				if(!preg_match('/^uid\s+(?:\[([^\]]+)\] )?([^<(]+)(?: \(([^)]+)\))?(?: <([^>]+)>)?$/i', trim($line), $matches))
					throw new PGPCommandException($this, "Invalid line: " . $line);
				$this->mUserID = trim($matches[2]);
				if(isset($matches[3]))
					$this->mComment = trim($matches[3]);
				if(isset($matches[4]))
					$this->mEmail = trim($matches[4]);
				break;

			case 'sub':
				if (!preg_match('/^sub\s+([^\/]+)\/([^ ]+)\s+(\d\d\d\d-\d\d-\d\d)/i', trim($line), $matches))
					throw new PGPCommandException($this, "Invalid line: " . $line);

				$this->mSubShortCode = $matches[2];
				$this->mSubDate = $matches[3];
				break;
		}
		return $line;
	}

	function executeWithCallback(IRequest $Request, $callback) {
		$this->mCallback = $callback;
		$this->execute($Request);
		$this->mCallback = null;
	}

	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws Exceptions\PGPCommandException
	 * @throws \Exception
	 * @return PGPSearchCommand the execution response
	 */
	function execute(IRequest $Request) {
		$this->reset();
		$this->mCount = 0;

		$Response = parent::execute($Request);

		if ($this->mFingerprint !== null) {
			$callback = $this->mCallback;
			$this->mCount++;
			$ret = $callback && $callback($this);
			$this->reset();
			if($ret)
				return true;
		}

		$this->reset();

		return $this->update($this->getCount() . " Keys found");
	}

	/**
	 * @param \CPath\Request\IRequest $Request
	 * @throws PGPNotFoundException
	 * @return PGPSearchCommand
	 */
	function queryFirst(IRequest $Request) {
		$Result = null;
		$this->executeWithCallback($Request, function($Search) use (&$Result) {
			$Result = clone $Search;
			return false;
		});

		if($this->getCount() === 0)
			throw new PGPNotFoundException($this, "No results found: " . $this->getSearchString(), IResponse::HTTP_NOT_FOUND);
		return $Result;
	}

	/**
	 * @param \CPath\Request\IRequest $Request
	 * @throws \Site\Account\Exceptions\UserNotFoundException
	 * @throws PGPNotFoundException
	 * @throws PGPSearchException
	 * @return PGPSearchCommand
	 */
	function queryOne(IRequest $Request) {
		$All = $this->queryAll($Request);
		if(sizeof($All) <= 0)
			throw new PGPNotFoundException($this, "No results found: " . $this->getSearchString());

		if(sizeof($All) > 1) {
			throw new PGPSearchException($this, "Multiple user keys found: " . $this->getSearchString()); }

		return reset($All);
	}

	/**
	 * @param \CPath\Request\IRequest $Request
	 * @return PGPSearchCommand[]
	 */
	function queryAll(IRequest $Request) {
		$Results = array();
		$this->executeWithCallback($Request, function($Search) use (&$Results) {
			$Results[] = clone $Search;
		});
		return $Results;
	}

	/**
	 * Map data to the key map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
	function mapKeys(IKeyMapper $Map) {
		if($this->mFingerprint === null) {
			parent::mapKeys($Map);
			$Map->map('results', new SequenceMapWrapper($this));
			return;
		}

		$this->mapEntryKeys($Map);
	}

	protected function mapEntryKeys(IKeyMapper $Map) {
		$Map->map('user_id', $this->mUserID);
		$Map->map('email', $this->mEmail);
		$Map->map('created', $this->mPubDate);
		$Map->map('fingerprint', $this->mFingerprint);
	}

	/**
	 * Map sequential data to the map
	 * @param ISequenceMapper $Map
	 */
	function mapSequence(ISequenceMapper $Map) {
		$this->executeWithCallback(null, function ($Search) use ($Map) {
			$Map->mapNext(clone $Search);
		});
	}

	public function getCount() {
		return $this->mCount;
	}

	public function getUser() {
		return new User($this->getFingerprint(), $this->mUserID);
	}

	/**
	 * @param bool $stripSpaces
	 * @return string
	 */
	public function getFingerprint($stripSpaces=true) {
		if($stripSpaces)
			return str_replace(' ', '', $this->mFingerprint);
		return rtrim($this->mFingerprint);
	}

	public function getEmail() {
		return $this->mEmail;
	}

	public function getUserID() {
		return $this->mUserID;
	}

	public function getComment() {
		return $this->mComment;
	}

	public function getPubDate() {
		return $this->mPubDate;
	}

	public function getSubDate() {
		return $this->mSubDate;
	}

	public function getPubShortCode() {
		return $this->mPubShortCode;
	}

	public function getSubShortCode() {
		return $this->mSubShortCode;
	}

    /**
     * Perform a unit test
     * @param IUnitTestRequest $Test the unit test request inst for this test session
     * @return void
     * @test --disable 0
     * Note: Use doctag 'test' with '--disable 1' to have this ITestable class skipped during a build
     */
    static function handleStaticUnitTest(IUnitTestRequest $Test) {
        $PGPSearch = new PGPSearchCommand('abc');
        $Result = $PGPSearch->execute($Test);
        $Test->assert($Result !== null);
    }
}



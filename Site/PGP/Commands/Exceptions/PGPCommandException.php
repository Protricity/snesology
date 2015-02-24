<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/4/14
 * Time: 1:23 PM
 */
namespace Site\PGP\Commands\Exceptions;

use CPath\Data\Map\IKeyMapper;
use CPath\Request\Exceptions\RequestException;
use Site\PGP\Commands\PGPSearchCommand;
use Site\PGP\PGPCommand;

class PGPCommandException extends RequestException
{
	const STR_COMMAND = 'command';
	private $mCommand;

	public function __construct(PGPCommand $CMD, $message = null, $statusCode = null) {
		$this->mCommand = $CMD;
		parent::__construct($message ?: $CMD->getMessage(), $statusCode ?: $CMD->getCode());
	}

	/**
	 * @return PGPSearchCommand
	 */
	public function getPGPCommand() {
		return $this->mCommand;
	}

	/**
	 * Map data to the key map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
	function mapKeys(IKeyMapper $Map) {
		parent::mapKeys($Map);
		$Map->map(self::STR_COMMAND, $this->getPGPCommand()->getCommand(true));
	}
}


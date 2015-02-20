<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/19/14
 * Time: 2:17 PM
 */
namespace Site\PGP\Commands\Exceptions;

use Site\PGP\Commands\PGPSearchCommand;

class PGPSearchException extends PGPCommandException
{
	public function __construct(PGPSearchCommand $Search, $message = null, $statusCode = null) {
		parent::__construct($Search, $message, $statusCode);
	}
}


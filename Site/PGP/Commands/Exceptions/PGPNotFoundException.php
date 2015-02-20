<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/24/2014
 * Time: 4:14 PM
 */
namespace Site\PGP\Commands\Exceptions;

use Site\PGP\Commands\PGPSearchCommand;

class PGPNotFoundException extends PGPSearchException
{
	public function __construct(PGPSearchCommand $Search, $message = null, $statusCode = null) {
		parent::__construct($Search, $message, $statusCode);
	}
}
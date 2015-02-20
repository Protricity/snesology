<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/21/14
 * Time: 3:59 PM
 */
namespace Site\PGP\Commands\Exceptions;

use CPath\Request\Exceptions\RequestException;

class MissingSecretKeyException extends RequestException
{
}
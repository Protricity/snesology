<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/19/14
 * Time: 8:10 PM
 */
namespace Site\Account\Session;

use BC\Config;

class SessionConfig
{
	static $SessionSalt = 'eS6ZZJlQaKMAJVGUCGtbw';
	static $SessionContentPath = 'user/session/';
	static $SessionRoleDefaultGroup = 'general';

	static function getContentPath($additionalPath = null) {
		return Config::getContentPath(static::$SessionContentPath, $additionalPath);
	}
}

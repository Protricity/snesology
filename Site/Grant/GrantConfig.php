<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/20/14
 * Time: 1:11 PM
 */
namespace Site\Grant;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use Site\Config;

class GrantConfig implements IBuildable
{
	static $DB_USERNAME = null;
	static $DB_PASSWORD = null;
	static $GrantSalt = 'eS6bZZlQaKM66ZZCGetAJVGJA6ZfZ3UsUbw';
	static $GrantContentPath;

	static function getContentPath($additionalPath=null) {
		return self::$GrantContentPath . ($additionalPath ? '/' . $additionalPath : '');
	}

	/**
	 * Handle this request and render any content
	 * @param IBuildRequest $Request the build request inst for this build session
	 * @return void
	 * @build --disable 1
	 * Note: Use doctag 'build' with '--disable 1' to have this IBuildable class skipped during a build
	 */
	static function handleBuildStatic(IBuildRequest $Request) {
//		$grantPath = GrantConfig::getContentPath();
//		if(!is_dir($grantPath))
//			mkdir($grantPath, 0777, true);
	}
}

GrantConfig::$GrantContentPath = Config::getContentPath('grant/');

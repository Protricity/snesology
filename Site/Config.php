<?php
/**
 * Project: CleverPath Framework
 * IDE: JetBrains PhpStorm
 * Author: Ari Asulin
 * Email: ari.asulin@gmail.com
 * Date: 4/06/11 */
namespace Site;

define('CONFIG_CONTENT_PATH', dirname(__DIR__));

class Config {
    static $RequireInvite = true;

    static $ProfileSalt = 'QtbeMAJCJlGtZZaJlGbeS6mVGUw';
    static $ContentPath = CONFIG_CONTENT_PATH;

	static $DefaultKeySize = 1024;
	static $REGISTRATION_LIMIT = 8640000;

	public static $TemplateClass = 'Site\\Render\\DefaultTemplate';

	static function getContentPath($additionalPath=null) {
		return self::$ContentPath . ($additionalPath ? '/' . $additionalPath : '');
	}
}


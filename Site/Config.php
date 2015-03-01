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

    public static $ChatSocketURI = 'ws://localhost:7845/';
    public static $ChatSocketPath = 'socket';
    public static $SocketDomainPath = null;

    public static $TemplateClass = 'Site\\Render\\DefaultTemplate';
    public static $AllowedTags = array(
        'b',
        'i',
        'em',
        'strong',
        'small',
        'mark',
        'del',
        'ins',
        'sub',
        'sup',
//        "&#60;b&#62;" => "<b>",
//        "&#60;/b&#62;" => "</b>",
//        "&#60;/b&#62;" => "</b>",
//        "&#60;/b&#62;" => "</b>",
//        "&#60;/b&#62;" => "</b>",
    );

    static function getContentPath($additionalPath=null) {
		return self::$ContentPath . ($additionalPath ? '/' . $additionalPath : '');
	}
}


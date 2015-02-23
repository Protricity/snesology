<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 9/17/14
 * Time: 8:15 AM
 */
namespace Site;

use CPath\Autoloader;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Request\IRequest;
use CPath\Request\Request;
use CPath\Route\CPathMap;
use CPath\Route\IRouteMap;
use CPath\Route\IRouteMapper;
use CPath\Route\RouteBuilder;
use CPath\Route\RouteRenderer;

require_once(__DIR__ . '/../CPath/Autoloader.php');
Autoloader::addLoader(basename(__NAMESPACE__), __DIR__);

class SiteMap implements IRouteMap, IBuildable
{

    /**
     * Maps all routes to the route map. Returns true if the route prefix was matched
     * @param IRouteMapper $Map
     * @return bool if true the route prefix was matched, otherwise false
     * @build routes --disable 0
     * Note: Set --disable 1 or remove doc tag to stop code auto-generation on build for this method
     */
    function mapRoutes(IRouteMapper $Map) {
		return
			// @group Site\Account\AccountHome
			$Map->route('ANY /home', 'Site\\Account\\AccountHome', 272, 'My Account') ||

			// @group Site\Account\Invite
			$Map->route('ANY /invite/', 'Site\\Account\\Invite') ||

			// @group Site\Account\Login
			$Map->route('ANY /login/:fingerprint', 'Site\\Account\\Login', 288, 'Login') ||

			// @group Site\Account\Logout
			$Map->route('ANY /logout/', 'Site\\Account\\Logout', 272, 'Logout') ||

			// @group Site\Account\ManageAccount
			$Map->route('ANY /a/:id', 'Site\\Account\\ManageAccount') ||
			$Map->route('ANY /account/:id', 'Site\\Account\\ManageAccount') ||
			$Map->route('ANY /manage/account/:id', 'Site\\Account\\ManageAccount') ||

			// @group Site\Account\Register
			$Map->route('ANY /register/', 'Site\\Account\\Register', 256, 'Register') ||
			$Map->route('ANY /register', 'Site\\Account\\Register', 288, 'Register') ||

			// @group Site\Account\SearchAccounts
			$Map->route('ANY /accounts', 'Site\\Account\\SearchAccounts') ||
			$Map->route('ANY /search/accounts', 'Site\\Account\\SearchAccounts') ||

			// @group Site\SiteIndex
			$Map->route('ANY /', 'Site\\SiteIndex') ||

			// @group Site\Song\CreateSong
			$Map->route('ANY /create/song/', 'Site\\Song\\CreateSong') ||
			$Map->route('ANY /songs', 'Site\\Song\\CreateSong', 272, 'Songs') ||

			// @group Site\Song\ManageSong
			$Map->route('ANY /manage/song/:id', 'Site\\Song\\ManageSong') ||

			// @group Site\Song\SearchSongs
			$Map->route('ANY /songs', 'Site\\Song\\SearchSongs') ||
			$Map->route('ANY /search/songs', 'Site\\Song\\SearchSongs') ||

			// @group Site\Song\ViewSong
			$Map->route('ANY /s/:id', 'Site\\Song\\ViewSong') ||
			$Map->route('ANY /view/song/:id', 'Site\\Song\\ViewSong') ||

			// @group __blank_template
			$Map->route('ANY /order', 'Site\\Render\\BlankTemplate') ||

			// @group __default_template
			$Map->route('ANY *', 'Site\\Render\\DefaultTemplate') ||

			// @group _cpath
			$Map->route('ANY *', new CPathMap());
	}

    /**
     * Handle this request and render any content
     * @param IRequest $Request the IRequest inst for this render
     * @return bool returns true if the route was rendered, false if no route was matched
     */
    static function route(IRequest $Request=null) {
        if(!$Request)
            $Request = Request::create();

        $Renderer = new RouteRenderer($Request);
	    $Index = new SiteMap;
	    return $Renderer->renderRoutes($Index);
    }

	/**
	 * Handle this request and render any content
	 * @param IBuildRequest $Request the build request inst for this build session
	 * @return void
	 * @build --disable 0
	 * Note: Use doctag 'build' with '--disable 1' to have this IBuildable class skipped during a build
	 */
	static function handleBuildStatic(IBuildRequest $Request) {
		$RouteBuilder = new RouteBuilder($Request, new static, '_cpath');
		$RouteBuilder->writeRoute('ANY *', 'new CPathMap()');
	}
}
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
     * @param IRequest $Request
     * @param IRouteMapper $Mapper
     * @return bool if true the route prefix was matched, otherwise false
     * @build routes --disable 0
     * Note: Set --disable 1 or remove doc tag to stop code auto-generation on build for this method
     */
    function mapRoutes(IRequest $Request, IRouteMapper $Mapper) {
		return
			// @group Site\Account\AccountHome
			$Mapper->route('ANY /home/', 'Site\\Account\\AccountHome', 272, 'My Account') ||

			// @group Site\Account\Invite
			$Mapper->route('ANY /invite/', 'Site\\Account\\Invite') ||

			// @group Site\Account\Login
			$Mapper->route('ANY /login/:fingerprint', 'Site\\Account\\Login', 288, 'Login') ||

			// @group Site\Account\Register
			$Mapper->route('ANY /register/', 'Site\\Account\\Register', 288, 'Register') ||

			// @group Site\Account\SearchAccounts
			$Mapper->route('ANY /accounts/', 'Site\\Account\\SearchAccounts') ||
			$Mapper->route('ANY /search/accounts/', 'Site\\Account\\SearchAccounts') ||

			// @group Site\Account\ViewAccount
			$Mapper->route('ANY /a/:id', 'Site\\Account\\ViewAccount') ||
			$Mapper->route('ANY /account/:id', 'Site\\Account\\ViewAccount') ||
			$Mapper->route('ANY /view/account/:id', 'Site\\Account\\ViewAccount') ||

			// @group Site\Path\CreatePath
			$Mapper->route('ANY /create/path/', 'Site\\Path\\CreatePath') ||
			$Mapper->route('ANY /paths/', 'Site\\Path\\CreatePath') ||

			// @group Site\Path\ManagePath
			$Mapper->route('ANY /manage/path/:path', 'Site\\Path\\ManagePath') ||

			// @group Site\Path\SearchPaths
			$Mapper->route('ANY /paths/', 'Site\\Path\\SearchPaths') ||
			$Mapper->route('ANY /search/paths/', 'Site\\Path\\SearchPaths') ||

			// @group Site\Relay\CreateLogEntry
			$Mapper->route('ANY /create/relay-log', 'Site\\Relay\\CreateLogEntry') ||

			// @group Site\Relay\PathLog
			$Mapper->route('ANY /relay/:path', 'Site\\Relay\\PathLog') ||

			// @group Site\SiteIndex
			$Mapper->route('ANY /', 'Site\\SiteIndex') ||

			// @group Site\SitePages
			$Mapper->route('ANY /blog/', 'Site\\SitePages', 288, 'Blog') ||
			$Mapper->route('ANY /chat/', 'Site\\SitePages', 272, 'Chat') ||

			// @group Site\Song\Album\CreateAlbum
			$Mapper->route('ANY /create/album/', 'Site\\Song\\Album\\CreateAlbum') ||
			$Mapper->route('ANY /albums/', 'Site\\Song\\Album\\CreateAlbum', 272, 'Albums') ||

			// @group Site\Song\Album\ManageAlbum
			$Mapper->route('ANY /manage/album/:id', 'Site\\Song\\Album\\ManageAlbum') ||

			// @group Site\Song\Album\ReviewAlbum
			$Mapper->route('ANY /review/album/:id', 'Site\\Song\\Album\\ReviewAlbum') ||

			// @group Site\Song\Album\SearchAlbums
			$Mapper->route('ANY /albums/', 'Site\\Song\\Album\\SearchAlbums', 256, 'Albums') ||
			$Mapper->route('ANY /search/albums/', 'Site\\Song\\Album\\SearchAlbums') ||

			// @group Site\Song\Artist\ManageArtist
			$Mapper->route('ANY /manage/artist/:name', 'Site\\Song\\Artist\\ManageArtist') ||

			// @group Site\Song\Artist\ViewArtist
			$Mapper->route('ANY /sa/:id', 'Site\\Song\\Artist\\ViewArtist') ||
			$Mapper->route('ANY /song/artist/:id', 'Site\\Song\\Artist\\ViewArtist') ||

			// @group Site\Song\CreateSong
			$Mapper->route('ANY /create/song/', 'Site\\Song\\CreateSong') ||
			$Mapper->route('ANY /songs/', 'Site\\Song\\CreateSong', 272, 'Songs') ||

			// @group Site\Song\Genre\ManageGenre
			$Mapper->route('ANY /manage/genre/:name', 'Site\\Song\\Genre\\ManageGenre') ||

			// @group Site\Song\Genre\SearchGenres
			$Mapper->route('ANY /genres/', 'Site\\Song\\Genre\\SearchGenres') ||
			$Mapper->route('ANY /search/genres/', 'Site\\Song\\Genre\\SearchGenres') ||

			// @group Site\Song\Genre\ViewGenre
			$Mapper->route('ANY /sg/:name', 'Site\\Song\\Genre\\ViewGenre') ||
			$Mapper->route('ANY /song/genre/:name', 'Site\\Song\\Genre\\ViewGenre') ||

			// @group Site\Song\ManageSong
			$Mapper->route('ANY /manage/song/:id', 'Site\\Song\\ManageSong') ||

			// @group Site\Song\ReviewSong
			$Mapper->route('ANY /review/song/:id', 'Site\\Song\\ReviewSong') ||

			// @group Site\Song\SearchSongTags
			$Mapper->route('ANY /search/songtags/:name/:value', 'Site\\Song\\SearchSongTags') ||

			// @group Site\Song\SearchSongs
			$Mapper->route('ANY /songs/', 'Site\\Song\\SearchSongs', 256, 'Songs') ||
			$Mapper->route('ANY /search/songs/', 'Site\\Song\\SearchSongs') ||

			// @group Site\Song\System\ManageSystem
			$Mapper->route('ANY /manage/system/:name', 'Site\\Song\\System\\ManageSystem') ||

			// @group Site\Song\System\ViewSystem
			$Mapper->route('ANY /ss/:name', 'Site\\Song\\System\\ViewSystem') ||
			$Mapper->route('ANY /song/system/:name', 'Site\\Song\\System\\ViewSystem') ||

			// @group Site\Song\ViewSong
			$Mapper->route('ANY /s/:id', 'Site\\Song\\ViewSong') ||
			$Mapper->route('ANY /view/song/:id', 'Site\\Song\\ViewSong') ||

			// @group _1logout
			$Mapper->route('ANY /logout/', 'Site\\Account\\Logout', 272, 'Logout') ||

			// @group __default_template
			$Mapper->route('ANY *', 'Site\\Render\\DefaultTemplate') ||

			// @group _cpath
			$Mapper->route('ANY *', new CPathMap());
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
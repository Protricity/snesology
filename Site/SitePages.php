<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/1/2015
 * Time: 12:12 AM
 */
namespace Site;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\IRequest;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use Site\Relay\HTML\HTMLRelayChat;
use Site\Relay\PathLog;

class SitePages implements IExecutable, IBuildable, IRoutable
{
    const PATH_BLOG = '/blog/';
    const PATH_CHAT = '/chat/';

    /**
     * Execute a command and return a response. Does not render
     * @param IRequest $Request
     * @return IResponse the execution response
     */
    function execute(IRequest $Request) {
        switch($Request->getPath()) {
            case self::PATH_BLOG:
                return new HTMLElement('div', null, '<a href="http://snesology.tumblr.com">Tumbler</a>');

            case self::PATH_CHAT:
                return new HTMLRelayChat($Request, 'chat');

            default:
                return new HTMLElement('div', null, 'Invalid Page: ' . $Request->getPath());

        }
    }

    // Static

    /**
     * Handle this request and render any content
     * @param IBuildRequest $Request the build request inst for this build session
     * @return void
     * @build --disable 0
     * Note: Use doctag 'build' with '--disable 1' to have this IBuildable class skipped during a build
     */
    static function handleBuildStatic(IBuildRequest $Request) {
        $RouteBuilder = new RouteBuilder($Request, new SiteMap());
        $RouteBuilder->writeRoute('ANY ' . self::PATH_BLOG, __CLASS__, IRequest::NAVIGATION_ROUTE | IRequest::MATCH_NO_SESSION, "Blog");
        $RouteBuilder->writeRoute('ANY ' . self::PATH_CHAT, __CLASS__, IRequest::NAVIGATION_ROUTE | IRequest::MATCH_SESSION_ONLY, "Chat");
    }


    /**
     * Route the request to this class object and return the object
     * @param IRequest $Request the IRequest inst for this render
     * @param array|null $Previous all previous response object that were passed from a handler, if any
     * @param null|mixed $_arg [varargs] passed by route map
     * @return void|bool|Object returns a response object
     * If nothing is returned (or bool[true]), it is assumed that rendering has occurred and the request ends
     * If false is returned, this static handler will be called again if another handler returns an object
     * If an object is returned, it is passed along to the next handler
     */
    static function routeRequestStatic(IRequest $Request, Array &$Previous = array(), $_arg = null) {
        return new ExecutableRenderer(new static(), true);
    }
}
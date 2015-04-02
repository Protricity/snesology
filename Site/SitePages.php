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
use CPath\Render\HTML\HTMLContainer;
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
    const PATH_ABOUT = '/about/';

    /**
     * Execute a command and return a response. Does not render
     * @param IRequest $Request
     * @return IResponse the execution response
     */
    function execute(IRequest $Request) {
        switch($Request->getPath()) {
            case self::PATH_BLOG:
                return new HTMLElement('div', null, '<a href="http://snesology.tumblr.com">Tumbler</a><iframe width="1000" height="1000" src="http://snesology.tumblr.com" frameborder="0"></iframe>');

            case self::PATH_CHAT:
                return new HTMLRelayChat($Request, 'public-chat');

            case self::PATH_ABOUT:
                return new HTMLContainer(
                    "<ul style='display:inline-block'>
                        <li>Snesology.com is about <i>Game Music</i> which means
                            <ul>
                                <li>Chip tune originals and covers</li>
                                <li>Game OST sample libraries</li>
                            </ul>
                        </li><br/>

                        <li>Snesology.com is <i>Community Source</i> which means
                            <ul>
                                <li><a href='https://github.com/Protricity/snesology'>Open Source</a></li>
                                <li>Anyone can <a href='mailto:ari@asu.edu?subject=I want to help'>contribute</a></li>
                                <li>Community Operated <small>(No administrator accounts)</small></li>
                            </ul>
                        </li><br/>

                        <li>Snesology.com is <i>Under Construction</i>
                            <ul>
                                <li><b>Throw rupees into the fountain. See what happens</b></li>
                                <li>Bitcoin: <a href='https://blockchain.info/address/1AT6o3mmPRZVdzXPh7SbThgAhv9g4o3j92'>1AT6o3mmPRZVdzXPh7SbThgAhv9g4o3j92</a></li>
                                <li>Paypal:
                                    <form action='https://www.paypal.com/cgi-bin/webscr' method='post' target='_top'>
                                        <input type='hidden' name='cmd' value='_donations'>
                                        <input type='hidden' name='business' value='ktkinkel@gmail.com'>
                                        <input type='hidden' name='lc' value='US'>
                                        <input type='hidden' name='item_name' value='Snesology'>
                                        <input type='hidden' name='no_note' value='0'>
                                        <input type='hidden' name='currency_code' value='USD'>
                                        <input type='hidden' name='bn' value='PP-DonationsBF:btn_donate_SM.gif:NonHostedGuest'>
                                        <input type='image' src='https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif' border='0' name='submit'>
                                        <img alt='' border='0' src='https://www.paypalobjects.com/en_US/i/scr/pixel.gif' width='1' height='1'>
                                    </form>
                                </li>
                            </ul>
                        </li><br/>
                     </ul>
                     "

                );

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
        $RouteBuilder->writeRoute('ANY ' . self::PATH_BLOG, __CLASS__, IRequest::NAVIGATION_ROUTE, "Blog");
        $RouteBuilder->writeRoute('ANY ' . self::PATH_CHAT, __CLASS__, IRequest::NAVIGATION_ROUTE | IRequest::NAVIGATION_LOGIN_ONLY, "Chat");
        $RouteBuilder->writeRoute('ANY ' . self::PATH_ABOUT, __CLASS__, IRequest::NAVIGATION_ROUTE, "About");
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
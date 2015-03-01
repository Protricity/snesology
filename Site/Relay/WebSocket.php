<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 1/16/2015
 * Time: 12:07 AM
 */
namespace Site\Relay;


use CPath\Render\Text\TextMimeType;
use CPath\Request\Session\SessionRequest;
use Site\Account\DB\AccountEntry;
use Site\Config;
use Site\Relay\Socket\SocketRequest;
use Site\SiteMap;
use Wrench\Application\Application;
use Wrench\Client;
use Wrench\Connection;
use Wrench\Payload\Payload;
use Wrench\Server;

$wrenchPath = dirname(__DIR__) . '/libs/php/wrench/lib';
require($wrenchPath . '/SplClassLoader.php');

$classLoader = new \SplClassLoader('Wrench');
$classLoader->setIncludePath($wrenchPath);
$classLoader->register();

if(!empty($argv) && realpath($argv[0]) ===  __FILE__) {
	require_once(dirname(__DIR__) . '/SiteMap.php');
}

class WebSocket extends Application 
{
	/** @var Server */
	private $mServer = null;

    /** @var AccountEntry[] */
	private $mSessionClients = array();

    private $domain = null;

    public function __construct($domain=null) {
        $this->domain = $domain;
    }

	function run() {

		$this->mServer = new Server(Config::$ChatSocketURI, array(
            'check_origin'               => false,
			'allowed_origins'            => array()
//				'localhost',
//                '192.168.0.108',
//                '127.0.0.1',
//			),
		));

		$this->mServer->registerApplication(Config::$ChatSocketPath, $this);
		$this->mServer->run();
	}

	/**
	 * @param $eventName
	 * @param $msg
	 * @param Connection $connection
	 * @throws \Wrench\Exception\ConnectionException
	 * @throws \Wrench\Exception\HandshakeException
	 * @return bool
	 */
	private function send($eventName, $msg, $connection) {
		$connection->send($eventName . ' ' . $msg);
		echo $eventName . ' ' . $msg . "\n";
		return true;
	}

	/**
	 * @param Connection $client
	 * @return bool
	 */
	private function hasSessionID($client) {
		$headers = $client->getHeaders();
		if($cookies = $headers['Cookie']) {
			parse_str(str_replace('; ', '&', $cookies), $cookies);
			if (!empty($cookies[session_name()]))
				return $cookies[session_name()];
		}
		return false;
	}

	/**
	 * @param Connection $client
	 */
	public function onConnect($client)
	{
		echo "Client connected: " . $client->getIp() . "\n";
		if($sessionID = $this->hasSessionID($client)) {
			session_id($sessionID);
			@session_start();
			$SessionRequest = new SessionRequest();
            if(AccountEntry::hasActiveSession($SessionRequest)) {
                $Account = AccountEntry::loadFromSession($SessionRequest);
                $this->mSessionClients[$client->getId()] = $Account;
                echo "Session detected: ", $Account->getName(), "\n";
            }
			session_write_close();
		}
	}

	/**
	 * Handle data received from a client
	 * @param Payload $payload A payload object, that supports __toString()
	 * @param Connection $connection
	 * @return bool
	 */
	public function onData($payload, $connection) {
        $json = json_decode($payload, true);

//        $Account = $this->mSessionClients[$connection->getId()];

        if(empty($json['action']))
            return false; // TODO

        $action = $json['action'];
        unset($json['action']);

        if($this->domain) {
            if(strpos($action, $this->domain) !== 0) {
                echo "Not within domain: ", $action;
                return false;
            }

            $action = substr($action, strlen($this->domain));
            $action = '/' . ltrim($action, '/');
        }

        $isSession = false;
        if($sessionID = $this->hasSessionID($connection)) {
            session_id($sessionID);
            @session_start();
            $isSession = true;
        }

        $Request = new SocketRequest($connection, 'POST', $action, $json, new TextMimeType());
        $rendered = SiteMap::route($Request);
        if(!$rendered) {
            echo "Nothing rendered: " . $Request->getPath();
        }

        if($isSession) {
            session_write_close();
        }
		return false;
	}

}

//
//$WebSocket = new WebSocket();
//$WebSocket->execute(Request::create());
if(!empty($argv) && realpath($argv[0]) ===  __FILE__) {
	$WebSocket = new WebSocket();
	$WebSocket->run();
}
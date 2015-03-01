<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 1/16/2015
 * Time: 1:29 PM
 */
namespace Site\Relay\Socket;

use CPath\Request\Form\IFormRequest;
use CPath\Request\MimeType\IRequestedMimeType;
use CPath\Request\Request;
use CPath\Request\Session\ISessionRequest;
use CPath\Request\Session\SessionRequest;
use CPath\Request\Session\SessionRequestException;
use Wrench\Connection;
use Wrench\Exception\Exception;

class SocketRequest extends Request implements IFormRequest, ISessionRequest
{
	const METHOD_SOCKET = 'SOCK';
	private $mSessionRequest = null;
	private $mSocket;

	public function __construct($socket, $method, $path, $parameters = array(), IRequestedMimeType $MimeType = null) {
		$this->mSocket = $socket;
		parent::__construct($method, $path, $parameters, $MimeType);
	}

    function getDomainPath($withDomain = false) {
        return "NO DOMAIN";
    }


    /**
	 * @return Connection
	 */
	public function getSocketConnection() {
		return $this->mSocket;
	}

	public function getSessionRequest() {
		return $this->mSessionRequest ?:
			$this->mSessionRequest = new SessionRequest();
	}

	/**
	 * Returns true if the session is active, false if inactive
	 * @return bool
	 */
	function hasSessionCookie() {
		return (isset($_COOKIE[session_name()]));
	}


	/**
	 * Returns true if the session is active, false if inactive
	 * @return bool
	 */
	function isStarted() {
		return $this->getSessionRequest()->isStarted();
	}

	/**
	 * Return a referenced array representing the request session
	 * @param String|null [optional] $key if set, retrieves &$[Session][$key] instead of &$[Session]
	 * @throws SessionRequestException if no session was active
	 * @return array
	 */
	function &getSession() {
		return $this->getSessionRequest()->getSession();
	}

	/**
	 * Start a new session
	 * @throws SessionRequestException
	 * @return bool true if session was started, otherwise false
	 */
	function startSession() {
		return $this->getSessionRequest()->startSession();
	}

	/**
	 * End current session
	 * @throws SessionRequestException
	 * @return bool true if session was started, otherwise false
	 */
	function endSession() {
		return $this->getSessionRequest()->endSession();
	}

	/**
	 * Destroy session data
	 * @return bool true if session was destroyed, otherwise false
	 * @throws SessionRequestException if session wasn't active
	 */
	function destroySession() {
		return $this->getSessionRequest()->destroySession();
	}

    /**
     * Return a request value
     * @param $fieldName
     * @param int $filter
     * @return mixed|null the form field value or null if not found
     */
    function getFormFieldValue($fieldName, $filter = FILTER_SANITIZE_SPECIAL_CHARS) {
        return $this->getRequestValue($fieldName, $filter);
    }
}
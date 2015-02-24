<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 9/29/14
 * Time: 8:30 PM
 */
namespace Site\PGP;

use CPath\Data\Describable\IDescribable;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use Site\PGP\Exceptions\ParseException;

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/assets/libs/phpseclib/');
require_once __DIR__ . '/assets/libs/openpgp/lib/openpgp.php';
require_once __DIR__ . '/assets/libs/openpgp/lib/openpgp_crypt_rsa.php';


abstract class AbstractEncryptionKey implements IKeyMap, IDescribable
{
    private $mFingerprint=null;
    private $mKeyID=null;

    /**
     * @return \OpenPGP_Message
     */
    abstract protected function parse();

    abstract function exportKey();

    /**
     * @throws ParseException
     * @return \OpenPGP_PublicKeyPacket
     */
    function getPublicKeyPacket() {
        $parse = $this->parse();
        foreach ($parse->packets as $packet) {
            if ($packet instanceof \OpenPGP_PublicKeyPacket) {
                return $packet;
            }
        }
        throw new ParseException("Public Key packet could not be determined");
    }

    /**
     * @throws ParseException
     * @return \OpenPGP_UserIDPacket
     */
    function getUserIDPacket() {
        $parse = $this->parse();
        foreach ($parse->packets as $packet) {
            if ($packet instanceof \OpenPGP_UserIDPacket) {
                return $packet;
            }
        }
        throw new ParseException("UserID packet could not be determined");
    }

	function getUserID() {
		return $this->getUserIDPacket()->name;
	}

	function getUserIDName() {
		return $this->getUserIDPacket()->name;
	}

	function getUserIDEmail() {
		return $this->getUserIDPacket()->email;
	}

    public function getKeyID() {
        return $this->mKeyID ?: $this->mKeyID = $this->getPublicKeyPacket()->key_id;
    }

	public function getFingerprint() {
		return $this->mFingerprint ?: $this->mFingerprint = $this->getPublicKeyPacket()->fingerprint;
	}

	public function getTimestamp() {
		return $this->mFingerprint ?: $this->mFingerprint = $this->getPublicKeyPacket()->timestamp;
	}

	/**
	 * Map data to a data map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
    function mapKeys(IKeyMapper $Map) {
        $Map->map('id', $this->getKeyID()) ||
        $Map->map('fingerprint', $this->getFingerprint());
    }

}
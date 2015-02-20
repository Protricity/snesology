<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/19/14
 * Time: 10:59 PM
 */
namespace Site\PGP;

use Site\PGP\Exceptions\ParseException;

include_once('AbstractEncryptionKey.php');

class PGPMessage
{
	private $mParse = null;

	public function __construct($armoredMessageString, $header = 'PGP MESSAGE') {
		$data         = \OpenPGP::unarmor($armoredMessageString, $header);
		$this->mParse = \OpenPGP_Message::parse($data);
	}

	public function getParse() {
		return $this->mParse;
	}

	/**
	 * @throws ParseException
	 * @return \OpenPGP_PublicKeyPacket[]
	 */
	function getPublicKeyPackets() {
		$packets = array();
		foreach ($this->getParse()->packets as $packet) {
			if ($packet instanceof \OpenPGP_PublicKeyPacket) {
				$packets[] = $packet;
			}
		}
		return $packets;
	}

	/**
	 * @throws ParseException
	 * @return array
	 */
	function getPublicKeyFingerprints() {
		$fingerprints = array();
		foreach ($this->getParse()->packets as $packet) {
			if ($packet instanceof \OpenPGP_PublicKeyPacket) {
				$fingerprints[] = $packet->fingerprint();
			}
		}
		return $fingerprints;
	}
}
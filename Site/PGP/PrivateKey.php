<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 9/29/14
 * Time: 8:33 PM
 */
namespace Site\PGP;

class PrivateKey extends AbstractEncryptionKey
{
    private $mParse = null;

    public function __construct($publicKeyData) {
        $data = \OpenPGP::unarmor($publicKeyData, 'PGP PRIVATE KEY BLOCK');
        $this->mParse = \OpenPGP_Message::parse($data);
    }

    protected function parse() {
        return $this->mParse;
    }

    function exportKey() {
        $parse = $this->parse();
        return \OpenPGP::enarmor($parse->to_bytes(), "PGP PRIVATE KEY BLOCK");
    }

    /**
     * Get a simple public-visible title of this object as it would be displayed in a header (i.e. "Mr. Root")
     * @return String title for this Object
     */
    public function getTitle() {
        return "Private Key: " . $this->getFingerprint();
    }

    /**
     * Get a simple public-visible description of this object as it would appear in a paragraph (i.e. "User account 'root' with ID 1234")
     * @return String simple description for this Object
     */
    function getDescription() {
        return "Private Key: " . $this->getFingerprint();
    }

    /**
     * Get a simple world-visible description of this object as it would be used when cast to a String (i.e. "root", 1234)
     * Note: This method typically contains "return $this->getTitle();"
     * @return String simple description for this Object
     */
    function __toString() {
        return $this->getTitle();
    }
}
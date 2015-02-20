<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 9/29/14
 * Time: 8:33 PM
 */
namespace Site\PGP;

class PublicKey extends AbstractEncryptionKey
{
    private $mParse = null;

    public function __construct($data) {
	    if(strpos($data, 'PGP PUBLIC KEY BLOCK') !== false)
            $data = \OpenPGP::unarmor($data, 'PGP PUBLIC KEY BLOCK');
	    if(!$data)
		    throw new \InvalidArgumentException("Invalid public key block");
        $this->mParse = \OpenPGP_Message::parse($data);
    }

    protected function parse() {
        return $this->mParse;
    }

    function exportKey() {
        $parse = $this->parse();
        return \OpenPGP::enarmor($parse->to_bytes(), "PGP PUBLIC KEY BLOCK");
    }

    /**
     * Get a simple public-visible description of this object as it would appear in a paragraph (i.e. "User account 'root' with ID 1234")
     * @return String simple description for this Object
     */
    function getDescription() {
        return "Public Key: " . $this->getFingerprint();
    }

}



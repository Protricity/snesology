<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 9/25/14
 * Time: 2:03 PM
 */
namespace Site\PGP;

use CPath\Data\Map\IKeyMapper;
use CPath\Render\HTML\Attribute;
use CPath\Request\IRequest;
use Site\PGP\Exceptions\PGPPrivateKeyNotFound;


class PublicKeyFile extends AbstractEncryptionKey
{

    private $mPath;
    private $mParse = null;
    private $mPrivateKey = null;

    public function __construct($filePath) {
        $this->mPath = $filePath;
    }

    public function getFilePath() {
        return $this->mPath;
    }

    /**
     * Get a simple public-visible title of this object as it would be displayed in a header (i.e. "Mr. Root")
     * @return String title for this Object
     */
    public function getTitle() {
        return "Public Key File: " . basename($this->mPath);
    }

    /**
     * Get a simple public-visible description of this object as it would appear in a paragraph (i.e. "User account 'root' with ID 1234")
     * @return String simple description for this Object
     */
    function getDescription() {
        return "Public key file";
    }

    /**
     * Get a simple world-visible description of this object as it would be used when cast to a String (i.e. "root", 1234)
     * Note: This method typically contains "return $this->getTitle();"
     * @return String simple description for this Object
     */
    function __toString() {
        return $this->getTitle();
    }

    protected function parse() {
        if ($this->mParse)
            return $this->mParse;

        $content = file_get_contents($this->mPath);
        $data = \OpenPGP::unarmor($content, 'PGP PUBLIC KEY BLOCK');
        $this->mParse = \OpenPGP_Message::parse($data);
        return $this->mParse;
    }

    public function hasPrivateKey(IRequest $Request=null) {
        try {
            $this->getPrivateKey($Request);
            return true;
        } catch (PGPPrivateKeyNotFound $ex) {
            return false;
        }
    }

    public function getPrivateKey(IRequest $Request=null) {
        if($this->mPrivateKey)
            return $this->mPrivateKey;

        $Util = new PGPUtil($Request);
        $privateKeyString = $Util->exportPrivateKey($this->getFingerprint());
        return $this->mPrivateKey = new PrivateKey($privateKeyString);
    }

    function exportKey() {
        $parse = $this->parse();
        return \OpenPGP::enarmor($parse->to_bytes(), "PGP PUBLIC KEY BLOCK");
    }

	/**
	 * Map data to a data map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
    function mapKeys(IKeyMapper $Map) {
        parent::mapKeys($Map);
        //$Map->map('file', basename($this->getFilePath()));
        $Map->map('private', $this->hasPrivateKey());
        //$Map->map('url', $this->getURL());
    }


//    /**
//     * Return the url for this object
//     * @return String
//     */
//    function getURL() {
//        $url = Key::COOKIE_PATH . '/' . $this->getFingerprint();
//        return new RouteLink($url, basename($this->mPath));
//    }

}


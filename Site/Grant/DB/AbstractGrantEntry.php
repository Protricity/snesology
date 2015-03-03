<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/3/2015
 * Time: 10:55 AM
 */
namespace Site\Grant\DB;
use Site\PGP\Commands\PGPEncryptCommand;
use CPath\Request\Validation\Exceptions\ValidationException;
use CPath\Render\HTML\Element\Form\HTMLForm;
use Site\PGP\Commands\PGPImportPublicKeyCommand;
use CPath\Request\IRequest;
use Site\Account\Exceptions\InvalidAccountPassword;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\Commands\PGPDecryptCommand;

/**
 * Class AccountEntry
 * @table account
 */
abstract class AbstractGrantEntry // implements IBuildable, IKeyMap, ISerializable, IRenderHTML
{
    const FIELD_PASSPHRASE = 'passphrase';
    const JSON_PASSPHRASE_COMMENTS = '{
	"#comments": [
		"/**",
		" * This is the contents of your decrypted grant challenge.",
		" * If you are reading this, that means you successfully decrypted",
		" * your grant challenge and authenticated your public key identity.",
		" * \'passphrase\':\'[your challenge passphrase]\'",
		" * To Authenticate: enter the following JSON value as the challenge answer:",
		" */"]
}';

    const KEYRING_NAME = 'accounts.gpg';

    abstract function loadChallenge(IRequest $Request);

    abstract function loadChallengeAnswer(IRequest $Request);

    abstract protected function updateChallenge(IRequest $Request, $newChallenge, $newAnswer);

    function assertChallengeAnswer(IRequest $Request, $password, HTMLForm $ThrowForm = null) {
        $encryptedPassword = $this->loadChallengeAnswer($Request);

        if (crypt($password, $encryptedPassword) !== $encryptedPassword) {
            if ($ThrowForm)
                throw new ValidationException($ThrowForm, "Invalid Password");
            throw new InvalidAccountPassword("Invalid password");
        }
    }


    function generateChallenge(IRequest $Request, Array $recipients) {
        $passphrase = uniqid("CH");
        $json = json_decode(static::JSON_PASSPHRASE_COMMENTS, true);
        $json[self::FIELD_PASSPHRASE] = $passphrase;
        $json = json_encode($json, 128);

        $encryptedPassword = crypt($passphrase);


        $PGPEncrypt = new PGPEncryptCommand($recipients, $json);
        $PGPEncrypt->addKeyRing(static::KEYRING_NAME);
        $PGPEncrypt->setArmored();

        $PGPEncrypt->execute($Request);
        $challenge = $PGPEncrypt->getEncryptedString();

        $this->updateChallenge($Request, $challenge, $encryptedPassword);

        return $challenge;
    }
}
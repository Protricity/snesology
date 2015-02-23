/**
 * Created with JetBrains PhpStorm.
 * User: Ari
 * Date: 8/1/13
 * Time: 8:40 PM
 * To change this template use File | Settings | File Templates.
 */
(function(){

    var JSON_PASSPHRASE = 'passphrase';

    var PARAM_CHALLENGE = 'challenge';
    var PARAM_CHALLENGE_ANSWER = 'challenge-answer';

    var PARAM_FINGERPRINT = 'fingerprint';
    var PARAM_USER_SELECT = 'user-select';

    var FORM_NAME = 'login';

    var ready = function() {

        var Form = jQuery('form[name=' + FORM_NAME + ']');
        if(Form.length === 0)
            return;

        var InputUserID = Form.find('[name=' + PARAM_FINGERPRINT + ']');
        if(!InputUserID.val()) {
            setTimeout(function () {
                if(InputUserID.val())
                    return;

                var keys = (new openpgp.Keyring.localstore()).loadPrivate();

                var InputUserIDList = InputUserID.next('[name=' + PARAM_USER_SELECT + ']');
                if(InputUserIDList.length === 0) {
                    InputUserID.after(InputUserIDList = jQuery('<select name="' + PARAM_USER_SELECT + '" />'));
                    InputUserIDList.hide();
                    InputUserIDList.change(function() {
                        document.location.href = Form.attr('action') + '?' + PARAM_FINGERPRINT + '=' + InputUserIDList.val();
                        // InputUserIDList.trigger('navigate', url);
                    })
                }
                InputUserIDList.html('');

                InputUserIDList.append('<option value="">Choose an identity to log in with</option>');
                for(var i=0; i<keys.length; i++) {
                    var key = keys[i];
                    if(!key.isPrivate())
                        continue;
                    var userID = key.getUserIds()[0];
                    var fingerprint = key.getSigningKeyPacket().getFingerprint();
                    var needsPassphrase = key.getEncryptionKeyPacket().isDecrypted ? '' : ' (passphrase required)';
                    InputUserIDList.append('<option value="' + fingerprint + '">' + userID + needsPassphrase + '</option>')
                }

                InputUserIDList.append('<optgroup label="' + keys.length + ' private keys found in browser"></optgroup>');
                InputUserIDList.fadeIn();
            }, 500);
        }


        var TextAreaChallenge = jQuery('textarea[name=' + PARAM_CHALLENGE + ']');
        if(!TextAreaChallenge.length)
            throw new Error("Text area challenge not found");
        var InputPassphrase = jQuery('input[name=' + PARAM_CHALLENGE_ANSWER + ']');
        if(!InputPassphrase.length)
            throw new Error("Passphrase input field not found");
        if(TextAreaChallenge.val().indexOf("-----BEGIN PGP MESSAGE-----") === -1)
            return console.error("Text area does not contain PGP message");

        decrypt(TextAreaChallenge,
            function(decryptedMessage) {
                var json = jQuery.parseJSON(decryptedMessage);

                if(typeof json[JSON_PASSPHRASE] === 'string') {
                    InputPassphrase.val(json[JSON_PASSPHRASE]);
                } else {
                    InputPassphrase.filter(':hidden').fadeIn();
                    InputPassphrase.parents(':hidden').fadeIn();
                    InputPassphrase.trigger('error', "Invalid json passphrase: " + decryptedMessage);
                }
            }, function (error) {
                InputPassphrase.filter(':hidden').fadeIn();
                InputPassphrase.parents(':hidden').fadeIn();
                InputPassphrase.trigger('error', error);
            }
        );
    };

    jQuery(document).ready(function() {
        jQuery('body')
            .on('ready', ready);
        ready();
    });

})();
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
    var PARAM_CHALLENGE_PASSPHRASE = 'challenge-answer';

    var ready = function() {

        var TextAreaChallenge = jQuery('textarea[name=' + PARAM_CHALLENGE + ']');
        if(!TextAreaChallenge.length)
            throw new Error("Text area challenge not found");
        var InputPassphrase = jQuery('input[name=' + PARAM_CHALLENGE_PASSPHRASE + ']');
        if(!InputPassphrase.length)
            throw new Error("Passphrase input field not found");
        if(TextAreaChallenge.val().indexOf("-----BEGIN PGP MESSAGE-----") === -1)
            return console.error("Text area does not contain PGP message");

        TextAreaChallenge.trigger('decrypt', function(decryptedMessage) {
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
        });
    };

    jQuery(document).ready(function() {
        jQuery('body')
            .on('ready', ready);
        setTimeout(ready, 100);

    });

})();
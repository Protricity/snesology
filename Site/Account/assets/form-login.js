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
    var PARAM_PASSPHRASE = 'passphrase';

    var FIELDSET_PASSPHRASE = 'fieldset-passphrase';

    var FORM_NAME = 'login';

    var decryptMessage = function(encryptedMessage, prompt, success, fail) {

        //var pgpMessage = '-----BEGIN PGP MESSAGE ... END PGP MESSAGE-----';
        var pgpMessage = openpgp.message.readArmored(encryptedMessage);
        var encIDs = pgpMessage.getEncryptionKeyIds();

        var keys = (new openpgp.Keyring.localstore()).loadPrivate();

        var privateKeys = [];


        for(var i=0; i<keys.length; i++) {
            var pk = keys[i].getKeyPacket(encIDs);
            if(!pk)
                continue;
            privateKeys[privateKeys.length] = keys[i];
        }

        if(privateKeys.length === 0) {
            var need = [];
            for(i=0; i<encIDs.length; i++)
                need.push(encIDs[i].toHex());
            var found = [];
            for(i=0; i<keys.length; i++)
                for(j=0; j<keys[i].getKeyIds().length; j++)
                    found.push(keys[i].getKeyIds()[j].toHex());
            found.sort();
            var err = "Private keys not found for encryption ids: " + need.join(', ')
                + "<br/> &nbsp; Manual log in may be required";
            if(found.length > 0)
                err += "<br/> &nbsp; (" + found.length + ") Key(s) found in local storage: " + found.join(', ');
            if(fail)
                fail(err);
            throw new Error(err);
        }

        var dec = function(key, pgpMessage) {
            openpgp.decryptMessage(key, pgpMessage)
                .then(function(decryptedMessage) {
                    success(decryptedMessage);

                }).catch(function(e) {
                    if(fail)
                        fail(e);
                });
        };

        var key = privateKeys[0];

        var privateKey = key.getKeyPacket(encIDs);
        if(!privateKey.isDecrypted) {
            prompt(function(passphrase) {
                var privateKey = key.getKeyPacket(encIDs);
                privateKey.decrypt(passphrase);
                if(privateKey.isDecrypted) {
                    dec(key, pgpMessage);
                    return true;
                }
                return false;

            });
        } else {
            dec(key, pgpMessage);
        }

    };

    var PGP_MSG_BEGIN = '-----BEGIN PGP MESSAGE-----';
    var PGP_MSG_END = '-----END PGP MESSAGE-----';
    var decrypt = function(target, onSuccess, onError) {
        var Target = jQuery(target);
        var encryptedMessage = Target.val();
        var start = encryptedMessage.indexOf(PGP_MSG_BEGIN);
        var startData = encryptedMessage.indexOf("\n\n", start) + 2;
        var endData = encryptedMessage.indexOf(PGP_MSG_END, start);
        var end = endData > 0 ? endData + PGP_MSG_END.length : -1;
        if(end < start || startData < start) {
            return onError ? onError("Message not found") : null;
        }

        var prompt = function(resumeWithPassphrase) {
            var Form = jQuery(Target[0].form);
            var InputPassphrase = Form.find('input[type=password][name=' + PARAM_PASSPHRASE + ']');
            var passphrase = InputPassphrase.val();

            var ButtonDecrypt = InputPassphrase.siblings('button[name=decrypt-passphrase]');
            if(ButtonDecrypt.length === 0 ) {
                ButtonDecrypt = jQuery("<button class='input' name='decrypt-passphrase'>Decrypt</button>");
                InputPassphrase.after(ButtonDecrypt);
                InputPassphrase.after('&nbsp;');
                ButtonDecrypt.on('click', function(e) {
                    e.preventDefault();
                    prompt(resumeWithPassphrase);
                });
            }
            InputPassphrase.off('keyup');
            if(resumeWithPassphrase(passphrase)) {
                InputPassphrase.val('');
                InputPassphrase.attr('disabled', 'disabled');

                InputPassphrase.removeClass('error');
                //Form.removeClass('error');
                InputPassphrase.removeAttr('required');
                ButtonDecrypt.fadeOut();
                return;
            }
            var timeout = null;
            InputPassphrase.on('keyup', function(e) {
                e.preventDefault();
                if(e.keyCode === 13)
                    prompt(resumeWithPassphrase);
                else {
                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        prompt(resumeWithPassphrase);
                    }, 100);
                }
            });
            InputPassphrase.addClass('error');
            InputPassphrase.attr('required', 'required');
            InputPassphrase.removeAttr('disabled');
            InputPassphrase.fadeIn();
            InputPassphrase.parents(':hidden').fadeIn();
            //Form.addClass('error');
        };


        var encMsgBlock = encryptedMessage.substring(start, end);
        decryptMessage(encMsgBlock, prompt,
            function(decryptedMessage) {
                if(onSuccess(decryptedMessage) === true)
                    return;

                Target.trigger('info', 'Message has been decrypted successfully');

                var Form = jQuery(Target[0].form);
                Form.find('.' + FIELDSET_PASSPHRASE).fadeOut();


                var count = 40;
                var end = 255;
                Target.on('focus click', function (e) { if(count < end) count = end; });
                var next = function() {
                    var mixedMsg = '';
                    for(var i=0; i<encryptedMessage.length; i++) {
                        var c = encryptedMessage[i];
                        if(i < startData) {
                            if(i < end - count*1.2 )
                                mixedMsg += c;
                            continue;
                        } else if(i > endData - 2) {
                            if(i - endData < end - count - 20)
                                mixedMsg += c;
                            continue;
                        }
                        var swap = c.charCodeAt(0) > (count * 255 / end)
                            + (c === '\n' ? 0 : -75 * Math.random())  ;

                        if(swap) {
                            mixedMsg += c;

                        } else {
                            if(decryptedMessage.length > i - startData)
                                mixedMsg += decryptedMessage[i - startData];
                            else
                                mixedMsg += ' ';
                        }
                    }

                    count += Math.random();
                    if(count < end) {
                        setTimeout(next, Math.abs(count / end * 50 - 17)); // (count / end) < .80 ? 5 : 100);
                        Target.val(mixedMsg);

                    } else {
                        Target.val(encryptedMessage.replace(encMsgBlock, decryptedMessage));
                        Target.animate({opacity: 1}, 'slow');
                    }
                };
                setTimeout(next, 5);
                Target.animate({opacity: 0.4}, 2000);

            }, function(err) {
                Target.trigger('info', err);
                if(onError)
                    onError(err);
            }
        );
    };

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
                    InputUserID.after(InputUserIDList = jQuery('<select class="input" name="' + PARAM_USER_SELECT + '" />'));
                    InputUserIDList.before(document.createTextNode(' '));

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
        if(TextAreaChallenge.length === 1) {
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
        } else {
            console.info("Text area challenge not found");

        }
    };

    jQuery(document).ready(function() {
        jQuery('body')
            .on('ready', ready);
        setTimeout(ready, 100);
    });

})();
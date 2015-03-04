/**
 * Created with JetBrains PhpStorm.
 * User: Ari
 * Date: 8/1/13
 * Time: 8:40 PM
 * To change this template use File | Settings | File Templates.
 */
(function(){
    var META_SESSION = 'session';
    var META_SESSION_ID = 'session-id';
    var PARAM_PASSPHRASE = 'passphrase';

    //var loadSessionKeyring = function() {
    //    return new openpgp.Keyring('session'); // ).loadPrivate();
    //};

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
    var eventMatches = {
        'decrypt': function(e, callback, onError) {
            var Target = jQuery(e.target);
            var encMsg = Target.val();
            var start = encMsg.indexOf(PGP_MSG_BEGIN);
            var startData = encMsg.indexOf("\n\n", start) + 2;
            var endData = encMsg.indexOf(PGP_MSG_END, start);
            var end = endData > 0 ? endData + PGP_MSG_END.length : -1;
            if(end < start || startData < start) {
                return onError ? onError("Message not found") : null;
            }

            var prompt = function(resumeWithPassphrase) {
                var Form = jQuery(e.target.form);
                var InputPassphrase = Form.find('input[type=password][name=' + PARAM_PASSPHRASE + ']');
                var passphrase = InputPassphrase.val();

                var ButtonDecrypt = InputPassphrase.siblings('button[name=decrypt-passphrase]');
                if(ButtonDecrypt.length === 0 ) {
                    ButtonDecrypt = jQuery("<button name='decrypt-passphrase'>Decrypt</button>");
                    InputPassphrase.after(ButtonDecrypt);
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


            var encMsgBlock = encMsg.substring(start, end);
            decryptMessage(encMsgBlock, prompt,
                //var Prompt = jQuery(
                //    '<form class="form-prompt">' +
                //    '<fieldset>' +
                //    '<legend>PGP Private Key Passphrase:</legend>' +
                //    'Please enter your PGP Private Key Passphrase:<br/>' +
                //    '<input type="password" class="invalid-passphrase" /><br/>' +
                //    '<button name="submit" >Submit</button>' +
                //    '<button name="cancel" >Cancel</button>' +
                //    '</fieldset>' +
                //    '</form>');
                //
                //
                //var PromptPassword = Prompt.find('input[type=password]');
                //
                //var tryPass = function() {
                //    var passphrase = PromptPassword.val();
                //    privateKey.decrypt(passphrase);
                //    if(privateKey.isDecrypted) {
                //        Prompt.removeClass('error');
                //        Prompt.remove();
                //        dec(key, pgpMessage);
                //    } else {
                //        Prompt.addClass('error');
                //    }
                //};
                //
                //PromptPassword.on('keyup', function(e) {
                //    e.preventDefault();
                //    if(e.keyCode === 13)
                //        return tryPass();
                //});
                //
                //Prompt.find('button[name=submit]').on('click keydown', function(e) {
                //    e.preventDefault();
                //    tryPass();
                //});
                //Prompt.find('button[name=cancel]').on('click keydown', function(e) {
                //    e.preventDefault();
                //    Prompt.remove();
                //    if(fail)
                //        fail("Passphrase required");
                //    throw new Error("Passphrase required");
                //});
                //
                //Prompt.hide().fadeIn(function() {
                //    PromptPassword.focus();
                //});
                //
                //jQuery('body').append(Prompt);
                function(decryptedMessage) {
                    if(callback(decryptedMessage) === true)
                        return;

                    Target.trigger('info', 'Message has been decrypted successfully');

                    var count = 40;
                    var end = 255;
                    Target.on('focus click', function (e) { if(count < end) count = end; });
                    var next = function() {
                        var mixedMsg = '';
                        for(var i=0; i<encMsg.length; i++) {
                            var c = encMsg[i];
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
                            Target.val(encMsg.replace(encMsgBlock, decryptedMessage));
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
        }
    };

    var eventHandler = function(e) {
        for(var events in eventMatches) {
            if(eventMatches.hasOwnProperty(events)) {
                var eventList = events.split(/[\s]+/);
                //if(eventList.indexOf(e.type) >= 0) try {
                    eventMatches[events].apply(eventMatches[events], arguments);
                //} catch (error) {
                //    jQuery('body').trigger('error', error);
                //    throw error;
                //}
            }
        }
    };

    var ready = function() {

    };

    jQuery(document).ready(function() {
        var EVENTS = Object.keys(eventMatches).join(' ');
        if(typeof window[EVENTS] !== 'undefined')
            return;

        jQuery('body').on(EVENTS, eventHandler)
            .on('ready', ready);
        setTimeout(ready, 100);

        window[EVENTS] = EVENTS;

        if(typeof openpgp._worker_init === 'undefined') {
            var src = jQuery('script[src$=openpgp\\.js], script[src$=openpgp\\.min\\.js]').attr('src');
            src = src.replace('/openpgp.', '/openpgp.worker.');
            openpgp.initWorker(src);
            openpgp._worker_init = true;
        }
    });

})();


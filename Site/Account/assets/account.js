/**
 * Created with JetBrains PhpStorm.
 * User: Ari
 * Date: 8/21/14
 * Time: 8:40 PM
 * To change this template use File | Settings | File Templates.
 */
(function(){
    var PARAM_GENERATE_INVITE = 'generate-invite';
    var PARAM_INVITE_EMAIL = 'invite-email';
    var PARAM_INVITE_MESSAGE = 'invite-message';
    var PARAM_ACCOUNT_FINGERPRINT = 'account-fingerprint';
    var PARAM_INVITE_CONTENT = 'invite-content';
    var CLS_ANCHOR_SEND_EMAIL = 'send-email';

    var PARAM_PRIVATE_MESSAGE = 'private-message';
    var PARAM_PRIVATE_MESSAGE_RECIPIENT = 'private-message-recipient';
    var PARAM_ENCRYPT = 'encrypt';

    var PARAM_PGP_SELECT = 'pgp-select';
    var PARAM_PGP_BACKUP = 'pgp-backup';
    var PARAM_PGP_DELETE = 'pgp-delete';
    var PARAM_PGP_CONTENT = 'pgp-content';
    var PARAM_PGP_ADD = 'pgp-add';

    var META_DOMAIN_PATH  = 'domain-path';

    var DEFAULT_MESSAGE = "You have been invited.";

    var validateEmail = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    var ready = function() {

        var domainPath = jQuery('head meta[name=' + META_DOMAIN_PATH + ']').attr('content');
        if(domainPath[0] !== '/')
            domainPath = '/' + domainPath;
        var domainFullPath = window.location.protocol + "//" + window.location.host + domainPath;
        var inviteFullPath = domainFullPath + 'invite/';
        var listAccountsFullPath = domainFullPath + 'search/accounts/';

        jQuery('button[name=' + PARAM_GENERATE_INVITE + ']').each(function(i, button) {
            if (typeof button.inviteInit !== 'undefined')
                return;
            button.inviteInit = true;

            var Button = jQuery(button);
            var Form = jQuery(button.form);
            var TextAreaInviteContent = Form.find('*[name=' + PARAM_INVITE_CONTENT + ']');
            var AnchorSendEmail = Form.find('a.' + CLS_ANCHOR_SEND_EMAIL);

            AnchorSendEmail.hide();

            console.info("Invite Button Found: ", Button);
            Button.on('click input', function(e) {
                e.preventDefault();
                var accountFingerprint = Form.find('*[name=' + PARAM_ACCOUNT_FINGERPRINT + ']').val().toUpperCase();
                var inviteEmail = Form.find('*[name=' + PARAM_INVITE_EMAIL + ']').val();
                var inviteMessage = Form.find('*[name=' + PARAM_INVITE_MESSAGE + ']').val() || DEFAULT_MESSAGE;
                var subject = "Invitation";

                if(!validateEmail.test(inviteEmail))
                    throw new Error("Invalid Email Address");

                var keys = (new openpgp.Keyring.localstore()).loadPrivate();

                for(var i=0; i<keys.length; i++) {
                    var key = keys[i].getSigningKeyPacket();
                    var fp = key.getFingerprint().toUpperCase();
                    if(fp === accountFingerprint) {
                        openpgp.signClearMessage(keys[i], inviteEmail)
                            .then(function(signedEmail) {
                                var inviteURL = inviteFullPath + '?invite=' + encodeURIComponent(signedEmail);
                                var inviteAnchor = '<a href="' + inviteURL + '">Invite Link</a>';
                                var body = inviteMessage
                                    + "\n\nInvite Link:\n\n"
                                    + inviteURL
                                    + "\n\nOr Go here\n\n"
                                    + inviteFullPath
                                    + "\n\nAnd supply the PGP SIGNED MESSAGE content:\n"
                                    + signedEmail
                                    + "\n\n\n\n\n- CleverTree\n" + new Date();

                                TextAreaInviteContent.val(body);

                                var inviteMailto = "mailto:" + inviteEmail
                                    + "?subject=" + encodeURIComponent(subject)
                                    + "&body=" + encodeURIComponent(jQuery.trim(body));

                                AnchorSendEmail.fadeIn();
                                AnchorSendEmail.attr('href', inviteMailto);

                            });
                        return;
                    }
                }

                throw new Error("Key pair not found in browser: " + accountFingerprint);
            });
            Button.removeAttr('disabled');
        });


        jQuery('button[name=' + PARAM_ENCRYPT + ']').each(function(i, button) {
            if (typeof button.encryptInit !== 'undefined')
                return;
            button.encryptInit = true;

            var Button = jQuery(button);
            var Form = jQuery(button.form);
            var TextAreaPrivateMessage = Form.find('*[name=' + PARAM_PRIVATE_MESSAGE + ']');
            var InputRecipients = Form.find('*[name=' + PARAM_PRIVATE_MESSAGE_RECIPIENT + ']');

            console.info("Private Message Encrypt Button Found: ", Button[0]);
            Button.on('click input', function(e) {
                e.preventDefault();

                var message = TextAreaPrivateMessage.val();
                var recipients = InputRecipients.val();

                if(recipients)
                    jQuery.getJSON(listAccountsFullPath + recipients,
                        function (json) {
                            var keys = [];
                            for (var i = 0; i < json.length; i++) {
                                var account = json[i];
                                console.log(account);

                                var publicKey = openpgp.key.readArmored(account['public-key']);
                                keys.push(publicKey.keys[0]);
                            }

                            openpgp.encryptMessage(keys, message).then(function(pgpMessage) {
                                TextAreaPrivateMessage.val(pgpMessage);
                            }).catch(function(error) {
                                console.error(error);
                            });

                        }
                    );
            });
            Button.removeAttr('disabled');
        });


        jQuery('select[name=' + PARAM_PGP_SELECT + ']').each(function(i, select) {
            if (typeof select.pgpInit !== 'undefined')
                return;
            select.pgpInit = true;

            var Select = jQuery(select);
            var Form = jQuery(select.form);
            var TextAreaPGPContent = Form.find('*[name=' + PARAM_PGP_CONTENT + ']');

            console.info("PGP Select Found: ", select);
            var Buttons = Select.siblings('button');
            
            var updatePGP = function() {
                var fingerprint = Select.val();

                Select.html();

                var selectedKey = null;
                var keys = (new openpgp.Keyring.localstore()).loadPrivate();
                for(var i=0; i<keys.length; i++) {
                    var key = keys[i].getSigningKeyPacket();
                    var fp = key.getFingerprint().toUpperCase();
                    Select.append('<option value="' + fp + '">' + keys[i].getUserIds()[0] + "</option>");
                    if(fp === fingerprint)
                        selectedKey = key;
                }

                Select.val(fingerprint);
            }

            Buttons.on('click input', function(e) {
                e.preventDefault();

                updatePGP();

                var Button = jQuery(e.target);
                switch(Button.attr('name')) {
                    case PARAM_PGP_ADD:
                        var privateKeyString = TextAreaPGPContent.val();
                        if(!privateKeyString)
                            break;
                        var privateKey = openpgp.key.readArmored(privateKeyString);
                        var keys = (new openpgp.Keyring.localstore()).loadPrivate();
                        keys.push(privateKey.keys[0]);
                        console.log("Adding private key: ", privateKey, keys);
                        (new openpgp.Keyring.localstore()).storePrivate(keys);
                        TextAreaPGPContent.val('');
                        
                        updatePGP();
                        break;

                    case PARAM_PGP_BACKUP:
                        var fingerprint = Select.val();
                        var keys = (new openpgp.Keyring.localstore()).loadPrivate();
                        for(var i=0; i<keys.length; i++) {
                            var key = keys[i].getSigningKeyPacket();
                            var fp = key.getFingerprint().toUpperCase();

                            if(fp === fingerprint) {
                                console.log("Downloading private key: ", keys[i]);
                                var pom = document.createElement('a');
                                pom.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(keys[i].armor()));
                                pom.setAttribute('download', 'private_key');
                                pom.click();
                                break;
                            }
                        }
                        updatePGP();
                        break;

                    case PARAM_PGP_DELETE:
                        var fingerprint = Select.val();
                        var keys = (new openpgp.Keyring.localstore()).loadPrivate();
                        for(var i=0; i<keys.length; i++) {
                            var key = keys[i].getSigningKeyPacket();
                            var fp = key.getFingerprint().toUpperCase();

                            if(fp === fingerprint) {
                                keys = keys.splice(i, 1);
                                console.log("Deleting private key: ", key, keys);
                                (new openpgp.Keyring.localstore()).storePrivate(keys);
                                break;
                            }
                        }
                        updatePGP();
                        break;

                    default:
                        throw new Error("Invalid Button");
                }
            });
            Buttons.removeAttr('disabled');
            updatePGP();
        });
    };
    jQuery(document).ready(function() {
        jQuery('body').on('ready', ready);
        setTimeout(ready, 100);

        if(typeof openpgp._worker_init === 'undefined') {
            var src = jQuery('script[src$=openpgp\\.js], script[src$=openpgp\\.min\\.js]').attr('src');
            src = src.replace('/openpgp.', '/openpgp.worker.');
            openpgp.initWorker(src);
            openpgp._worker_init = true;
        }
    });
})();


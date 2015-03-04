/**
 * Created with JetBrains PhpStorm.
 * User: Ari
 * Date: 8/1/13
 * Time: 8:40 PM
 * To change this template use File | Settings | File Templates.
 */
(function(){
    var EVENTS = 'submit change click keydown keyup blur generate generate-complete';

    var FORM_ACTION = 'register';
    var FORM_NAME = 'form-register';
    var CLS_FORM = 'form-register';

    var PARAM_PUBLIC_KEY = 'public_key';
    var PARAM_RESET = 'reset';
    var PARAM_GENERATE = 'gen-keys';
    var PARAM_USER = 'gen-user';
    var PARAM_EMAIL = 'gen-email';
    var PARAM_PASSPHRASE = 'passphrase';
    var PARAM_SUBMIT = 'submit';
    var PARAM_LOAD_FILE = 'load-file';
    var PARAM_LOAD_STORAGE = 'load-storage';
    var PARAM_CHALLENGE = 'challenge';

    var CLS_FIELDSET_GENERATE = 'fieldset-generate';
    var CLS_FIELDSET_TOOLS = 'fieldset-tools';

    var regexEmail = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

    var lastKeyID = null;
    var eventMatches = {

        'click keyup keydown submit blur change': function (e) {
            var Target = jQuery(e.target);

            var isAction =
                e.type === 'click'
            || (e.type === 'submit')
            || (e.type === 'keyup' && (e.keyCode === 13 || e.keyCode === 32));

            if(typeof Target[0].form === 'undefined')
                return;

            var Form = jQuery(Target[0].form);

            if(isAction && Target.is('[name=' + PARAM_GENERATE + ']')) {
                e.preventDefault();
                Form.trigger('generate');

            } else if(isAction && Target.is('[name=' + PARAM_LOAD_FILE + ']')) {
                if (!window.FileReader) {
                    alert('Your browser is not supported');
                    throw new Error("window.FileReader unavailable");
                }
                if (!Target[0].files || !Target[0].files.length)
                    return;

                e.preventDefault();
                var textFile = Target[0].files[0];

                var reader = new FileReader();
                jQuery(reader).on('load', function(e) {
                    var file = e.target.result;
                    if (file && file.length) {
                        Form.find('[name=' + PARAM_PUBLIC_KEY + ']')
                            .val(file)
                            .trigger('blur');
                    }
                    Target.val('');
                });
                reader.readAsText(textFile);

            } else if(isAction && Target.is('[name=' + PARAM_RESET + ']')) {
                e.preventDefault();
                Form
                    .find("fieldset, [class^='field-']")
                    .removeAttr('disabled');
                Form.parent().find('div.error')
                    .slideUp(function() {
                        jQuery(this).remove();
                    });
                Form.find('.'+ CLS_FIELDSET_TOOLS)
                    .trigger('open');

            } else if(Target.is('[name=' + PARAM_USER + ']')) {
                var value = Target.val();
                if(!/^[\w_-]{5,27}$/.test(value) && !regexEmail.test(value)) {
                    Form.find('[name=' + PARAM_GENERATE + ']')
                        .attr('disabled', 'disabled');

                    if(e.type === 'blur' || e.type === 'change')
                        Target
                            .addClass('error')
                            .trigger('error', 'Username must be alphanumeric and may contain _ or -');

                } else {
                    if(e.type === 'blur' || e.type === 'change')
                        Target.trigger('error', null);
                    Form.find('[name=' + PARAM_GENERATE + ']')
                        .removeAttr('disabled');
                    Target.removeClass('error');
                }

            } else if(Target.is('[name=' + PARAM_EMAIL + ']')) {
                var emailValue = Target.val();

                if(emailValue && !regexEmail.test(emailValue)) {
                    if(e.type === 'blur' || e.type === 'change')
                        Target.addClass('error');
                    Form.find('[name=' + PARAM_GENERATE + ']')
                        .attr('disabled', 'disabled');

                } else {
                    Form.find('[name=' + PARAM_GENERATE + ']')
                        .removeAttr('disabled');
                    Target.removeClass('error');
                }

            } else if(Target.is('[name=' + PARAM_PUBLIC_KEY + ']')) {

                if(Target.val().indexOf("-----BEGIN PGP PUBLIC KEY BLOCK-----") >= 0) {
                    var publicKey = window.openpgp.key.readArmored(Target.val());
                    if(publicKey.err && publicKey.err.length > 0)
                        throw publicKey.err[0];
                    Target.removeClass('error');

                    var firstUser = publicKey.keys[0].users[0];
                    var firstUserID = firstUser.userId.userid;

                    var match = firstUserID.match(/^([^<]+)(?: <([^>]+)>)$/);
                    if(match !== null) {
                        firstUserID = match[1];
                        Form.find('[name=' + PARAM_EMAIL + ']')
                            .val(match[2]);
                    }

                    Form.find('[name=' + PARAM_USER + ']')
                        .val(firstUserID)
                        .blur();

                    Form.find('.'+ CLS_FIELDSET_TOOLS)
                        .trigger('close');

                    Form.find('[name=' + PARAM_SUBMIT + ']')
                        .removeAttr('disabled');

                } else {
                    Form.find('.'+ CLS_FIELDSET_TOOLS)
                        .trigger('open');

                    Form.find('[name=' + PARAM_SUBMIT+ ']')
                        .attr('disabled', 'disabled');
                }
            }
        },

        'generate': function(e, callback) {
            var Form = jQuery('form[name=' + FORM_NAME + ']');
            if(!Form.length)
                throw new Error("Form not found: " + FORM_NAME);

            var userID = Form.find('[name=' + PARAM_USER + ']').val();
            var email = Form.find('[name=' + PARAM_EMAIL + ']').val();
            var passphrase = Form.find('[name=' + PARAM_PASSPHRASE + ']').val();
            if(email)
                userID = userID + ' <' + email + '>';

            openpgp.generateKeyPair({
                keyType:1,
                numBits:1024,
                userId:userID,
                passphrase:passphrase
            }).then(function(NewKey) {
                    Form.find('[name=' + PARAM_GENERATE + ']')
                        .removeAttr('disabled');

                    var local = new openpgp.Keyring.localstore();
                    var keys = local.loadPrivate();
                    keys.push(NewKey.key);
                    if(lastKeyID !== null) {
                        for(var i=0; i<keys.length; i++) {
                            if(lastKeyID.equals(keys[i].getKeyIds()[0])) {
                                console.log(keys.length + " keys found. Deleting unused private key: " + keys[i]);
                                keys = keys.splice(i, 1);
                            }
                        }
                    }

                    lastKeyID = NewKey.key.getKeyIds()[0];
                    local.storePrivate(keys);

                    Form.find('[name=' + PARAM_PUBLIC_KEY + ']')
                        .val(NewKey.publicKeyArmored)
                        .trigger('change');

                    Form.trigger('generate-success', [NewKey]);

                    //Form.find('.' + CLS_FIELDSET_GENERATE)
                    //    .trigger('close');

                    if(callback)
                        callback(NewKey);

                    Form.trigger('log',
                        "<b>New PGP Keypair created successfully</b>:<br/>"
                        + " &nbsp; UserID: <b>" + NewKey.key.users[0].userId.userid.replace('<', '&lt;') + "</b><br/>"
                        + " &nbsp; Fingerprint: <b>" + NewKey.key.primaryKey.getFingerprint()+ "</b><br/>");
                    Form.trigger('info', "<b>Info</b>: You may now <a href='#submit' onclick=\"jQuery('input[name=" + PARAM_SUBMIT + "]').trigger('focuson'); return false;\">submit</a> your new <b>PGP public key</b> and complete your account registration");

                    Form.find('input[name=' + PARAM_SUBMIT + ']').trigger('focusin');

                }).catch(function(err) {
                    Form.find('button[name=' + PARAM_GENERATE + ']')
                        .removeAttr('disabled');

                    Form.trigger('error', [err]);
                });

            Form.find('button[name=' + PARAM_GENERATE + ']')
                .attr('disabled', 'disabled');

            Form.trigger('log', "Generating RSA key pair for " + userID + "...");
            e.preventDefault();
            return true;
        }
    };

    var eventHandler = function(e) {
        for(var events in eventMatches) {
            if(eventMatches.hasOwnProperty(events)) {
                var eventList = events.split(/[\s]+/);
                if(eventList.indexOf(e.type) >= 0) try {
                    eventMatches[events].apply(eventMatches[events], arguments);
                } catch (error) {
                    jQuery(e.target).trigger('error', error);
                    throw error;
                }
            }
        }
    };

    var key_i=0;
    var ready = function() {

        jQuery('button[name=' + PARAM_LOAD_STORAGE + ']').each(function(i, elm) {
            var LoadFromStorage = jQuery(elm);
            LoadFromStorage.hide();
            LoadFromStorage.removeAttr('disabled');
            LoadFromStorage.delay(500);
            LoadFromStorage.fadeIn();
            console.info("Enabled loading from storage", LoadFromStorage);
            LoadFromStorage.on('click', function(e) {
                e.preventDefault();
                var local = new openpgp.Keyring.localstore();
                var keys = local.loadPrivate();
                if(keys.length == 0) {
                    console.error("No keys stored in browser");
                    return;
                }

                var key = keys[key_i++ % keys.length];
                console.log("Key found: ", key);
                key = key.toPublic();
                var Form = jQuery(LoadFromStorage[0].form);
                Form.find('textarea[name="' + PARAM_PUBLIC_KEY + '"]')
                    .val(key.armor());
                var userID = key.getUserIds()[0];
                userID = userID.split('<');

                Form.find('input[name="' + PARAM_USER + '"]')
                    .val(jQuery.trim(userID[0]));
                if(userID.length > 1)
                    Form.find('input[name="' + PARAM_EMAIL + '"]')
                        .val(jQuery.trim(userID[1].replace('>', '')));
                Form.find('input[name="' + PARAM_PASSPHRASE + '"]')
                    .attr('disabled', 'disabled');
            });
        });
    };

    jQuery(document).ready(function() {
        var EVENTS = Object.keys(eventMatches).join(' ');
        if(EVENTS && typeof window[EVENTS] === 'undefined'){
            jQuery('body').on(EVENTS, eventHandler)
                .on('ready', ready);
            setTimeout(ready, 100);
            window[EVENTS] = EVENTS;
        }

        if(typeof openpgp._worker_init === 'undefined') {
            var src = jQuery('script[src$=openpgp\\.js], script[src$=openpgp\\.min\\.js]').attr('src');
            src = src.replace('/openpgp.', '/openpgp.worker.');
            openpgp.initWorker(src);
            openpgp._worker_init = true;
        }

    });

})();
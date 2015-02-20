/**
 * Created with JetBrains PhpStorm.
 * User: Ari
 * Date: 8/21/14
 * Time: 8:40 PM
 * To change this template use File | Settings | File Templates.
 */
(function(){
    var PARAM_ACCOUNT_TYPE = 'account-type';
    var CLS_FIELDSET_CHOOSE_ACCOUNT = 'fieldset-choose-account';

    var ready = function() {

        jQuery('form:has(fieldset.' + CLS_FIELDSET_CHOOSE_ACCOUNT + ')').each(function(i, form) {
            if (typeof form.accountInit !== 'undefined')
                return;
            form.accountInit = true;

            var Form = jQuery(form);
            console.info("Account Form Found: ", Form);
            Form.on('input', function(e) {
                var accountType = Form.find('*[name=' + PARAM_ACCOUNT_TYPE + ']').val();
                var WalletFieldSets = Form.find('.' + CLS_FIELDSET_CHOOSE_ACCOUNT);
                if(accountType) {
                    var WalletFieldSet = WalletFieldSets.filter('[data-' + PARAM_ACCOUNT_TYPE + '=' + accountType + ']');
                    WalletFieldSets = WalletFieldSets.not(WalletFieldSet);
                    WalletFieldSet.filter(':disabled').removeAttr('disabled');
                }
                WalletFieldSets.filter(':enabled').attr('disabled', 'disabled');
            }).trigger('input');

        });
    };
    jQuery(document).ready(function() {
        jQuery('body').on('ready', ready);
        ready();
    });
})();


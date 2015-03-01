/**
 * Created with JetBrains PhpStorm.
 * User: Ari
 * Date: 8/1/13
 * Time: 8:40 PM
 * To change this template use File | Settings | File Templates.
 */
(function(){

    const PARAM_REVIEW_TAG_NAME = 'review-tag-name';
    const PARAM_REVIEW_TAG_VALUE = 'review-tag-value';
    const TAG_TYPE_DEFAULT = 's';

    var ready = function() {

        var InputSelectTag = jQuery('*[name=' + PARAM_REVIEW_TAG_NAME + ']');

        if(typeof InputSelectTag[0].initReviewTagSelect !== 'undefined')
            return;
        
        InputSelectTag[0].initReviewTagSelect = true;
        var AddCustomOption = jQuery('<option value="custom:">Add Custom Review Tag</option>');
        InputSelectTag.append(AddCustomOption);

        InputSelectTag.on('change input', function(e) {
            var tagName = InputSelectTag.val();
            var tagType = TAG_TYPE_DEFAULT;
            if(tagName.indexOf(':') > 0)
                tagType = tagName.split(':')[0];

            if(tagName === 'custom:') {
                var newTagName = prompt("Provide the key name for the review tag");
                if(!newTagName)
                    return;
                AddCustomOption.before('<option>' + newTagName + '</option>');
                InputSelectTag.val(newTagName);
                return;
            }

            var InputValues = jQuery(InputSelectTag[0].form).find('.tag-type');
            InputValues
                .attr('disabled', 'disabled')
                .hide();
            InputValues.filter('.tag-type-' + tagType)
                .removeAttr('disabled')
                .show();
        }).trigger('change');

        AddCustomOption.on('click input', function(e) {
        });
    };

    jQuery(document).ready(function() {
        jQuery('body')
            .on('ready', ready);
        ready();
    });

})();
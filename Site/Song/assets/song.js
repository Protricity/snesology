/**
 * Created with JetBrains PhpStorm.
 * User: Ari
 * Date: 8/1/13
 * Time: 8:40 PM
 * To change this template use File | Settings | File Templates.
 */
(function(){

    var PARAM_TAG_NAME = 'tag-name';
    var PARAM_TAG_VALUE = 'tag-value';
    var META_DOMAIN_PATH  = 'domain-path';

    var doRequest = function(url, data, success, method) {
        method = method ? method : 'GET';
        var ajax = {
            method: method,
            data: data,
            dataType: 'json',
            url: url,
            success: success,
            error: function(jqXHR, textStatus, errorThrown) {
                console.error(errorThrown, arguments);
            }
        };

        jQuery.ajax(ajax);
    };

    var incID = 0;
    var ready = function() {

        var domainPath = jQuery('head meta[name=' + META_DOMAIN_PATH + ']').attr('content');
        var domainFullPath = window.location.protocol + "//" + window.location.host + "/" + domainPath;
        var searchSongTagsFullPath = domainFullPath + 'search/songtags/';

        jQuery('fieldset')
            .has('*[name=' + PARAM_TAG_NAME + ']')
            .has('*[name=' + PARAM_TAG_VALUE + ']')
            .each(function(i, fieldset) {
                if(typeof fieldset.__initSongAutoTag !== 'undefined')
                    return;    

                console.log("Found Song Auto Tag Fieldset: ", fieldset);
                fieldset.__initSongAutoTag = true;
                var FieldSet = jQuery(fieldset);
                var InputName = FieldSet.find('*[name=' + PARAM_TAG_NAME + ']');
                var InputValue = FieldSet.find('*[name=' + PARAM_TAG_VALUE + ']').first();

                var id = 'datalist_song_tag_' + incID++;
                var DataList = jQuery('<datalist id="' + id + '" />');
                DataList.append('<option value="wut" />');
                InputValue.after("<br/>");
                InputValue.after(DataList);
                InputValue.attr('list', id);

                var pathCache = {};

                var onInput = function() {
                    var tagName = InputName.val();
                    var tagValue = InputValue.val();
                    var path = searchSongTagsFullPath 
                    + encodeURIComponent(tagName)
                    + '/' + encodeURIComponent(tagValue.substr(0,2));


                    if(typeof pathCache[path] == 'object') {
                        var data = pathCache[path];
                        DataList.html('');
                        if(data)
                            for(var i=0; i<data.length; i++) 
                                DataList.append('<option value="' + data[i]['tag-value'] + '" />');
                        
                    } else {
                        doRequest(path, data, function(data) {
                            pathCache[path] = data;
                            DataList.html('');
                            if(data)
                                for(var i=0; i<data.length; i++) 
                                    DataList.append('<option value="' + data[i]['tag-value'] + '" />');
                        });
                    }
                };
                InputName.on('input change', onInput);
                InputValue.on('input', onInput);
                onInput();
            });

    };

    jQuery(document).ready(function() {
        jQuery('body')
            .on('ready', ready);
        ready();
    });

})();
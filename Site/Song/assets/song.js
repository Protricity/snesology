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

    var PARAM_SONG_ARTIST = 'song-artist';

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
        domainPath = domainPath.replace(/^\//, '');
        var domainFullPath = window.location.protocol + "//" + window.location.host + "/" + domainPath;
        var searchSongTagsPath = 'search/songtags/';

        var doAutoList = function(input, tagName) {
            if(typeof input.__initSongAutoTag !== 'undefined')
                return;    

            console.log("Found Song Auto Tag Input: ", input);
            input.__initSongAutoTag = true;
            
            var InputValue = jQuery(input); // FieldSet.find('*[name=' + PARAM_TAG_VALUE + ']');

            var id = 'datalist_song_tag_' + incID++;
            var DataList = jQuery('<datalist id="' + id + '" />');
            InputValue.after("<br/>");
            InputValue.after(DataList);
            InputValue.attr('list', id);

            var pathCache = {};

            var onInput = function() {

                var tagValue = InputValue.val();
                var path = searchSongTagsPath 
                + encodeURIComponent(typeof tagName == 'function' ? tagName() : tagName)
                + '/' + encodeURIComponent(tagValue.substr(0,2));

                if(typeof pathCache[path] === true) {
                    console.info("Cache miss: ", path);
                } else if(typeof pathCache[path] == 'object') {
                    var data = pathCache[path];
                    DataList.html('');
                    if(data)
                        for(var i=0; i<data.length; i++) 
                            DataList.append('<option value="' + data[i]['tag-value'] + '" />');

                } else {
                    pathCache[path] = true;
                    doRequest(domainFullPath + path, data, function(data) {
                        pathCache[path] = data;
                        DataList.html('');
                        if(data)
                            for(var i=0; i<data.length; i++) 
                                DataList.append('<option value="' + data[i]['tag-value'] + '" />');
                    });
                }
            };
            InputValue.on('input', onInput);
            onInput();
        };

        jQuery('fieldset')
            .has('*[name=' + PARAM_TAG_NAME + ']')
            .has('*[name=' + PARAM_TAG_VALUE + ']')
            .each(function(i, fieldset) {
                var InputName = jQuery(fieldset).find('*[name=' + PARAM_TAG_NAME + ']');
                var InputValue = jQuery(fieldset).find('*[name=' + PARAM_TAG_VALUE + ']');
                doAutoList(InputValue[0], function() { return InputName.val(); });
                InputName.on('change input', function() { InputValue.trigger('input'); });
            });

        jQuery('*[name=' + PARAM_SONG_ARTIST + ']')
            .each(function(i, input) {
                doAutoList(input, 'artist');
            });
    };

    jQuery(document).ready(function() {
        jQuery('body')
            .on('ready', ready);
        ready();
    });

})();
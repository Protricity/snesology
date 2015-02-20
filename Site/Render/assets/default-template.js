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
    var META_PATH = 'path';

    var CLS_CONSOLE = 'console';
    var CLS_STAY_OPEN = 'stay-open';
    var CLS_TOGGLE = 'toggle';
    var CLS_PRESERVE = 'preserve';
    var CLS_PRESERVE_GET = 'preserve-get';
    var CLS_PRESERVE_POST = 'preserve-post';

    var ERROR_TIMEOUT = 15000;

    var Body = null;

    var getMetaContent = function(key) {
        var meta = {};
        jQuery('head meta').each(function (i, elm) {
            var name = jQuery(elm).attr('name');
            meta[name] = jQuery(elm).attr('content');
        });
        return meta;
    };

    var onResize = function() {
        var Footer = jQuery('section.footer');
        //var Console = Footer.children('.html-console');
        var windowHeight = jQuery(window).height();
        var windowScroll = jQuery(window).scrollTop();
        var bodyHeight = jQuery('body').height();
        var consoleHeight = Footer.height();
        if(Footer.hasClass('fixed')) {
            if(windowHeight + windowScroll > bodyHeight + consoleHeight) {
                Footer.removeClass('fixed');
            }
        } else {
            if(windowHeight + windowScroll < bodyHeight + consoleHeight) {
                Footer.addClass('fixed');
            }

        }
        //console.log(windowHeight, windowScroll, bodyHeight, consoleHeight);
    };

    var allowCache = false;
    jQuery.ajaxPrefilter(function( options, originalOptions ) {
        if(!allowCache)
            return;
        if ( options.dataType == 'script' || originalOptions.dataType == 'script' ) {
            options.cache = true;
        }
    });

    var hashString = function(string) {
        var hash = 0, i, chr, len;
        if (string.length == 0)
            return hash;
        for (i = 0, len = string.length; i < len; i++) {
            chr   = string.charCodeAt(i);
            hash  = ((hash << 5) - hash) + chr;
            hash |= 0; // Convert to 32bit integer
        }
        return hash;
    };

    var matchURI = function(uri1, uri2) {
        var parser1 = document.createElement('a');
        parser1.href = uri1;
        var parser2 = document.createElement('a');
        parser2.href = uri2;
        return parser1.pathname && (parser1.pathname === parser2.pathname);
    };

    //var matchChildren = function(oldParent, newParent) {
    //    var OldChildren = jQuery(oldParent).children();
    //    var NewChildren = jQuery(newParent).children();
    //    var MatchedChildren = jQuery();
    //    for(var i=0; i<NewChildren.length; i++) {
    //        for(var j=0; j<OldChildren.length; j++) {
    //            if(matchElements(OldChildren[j], NewChildren[i]))
    //                MatchedChildren = MatchedChildren.add(NewChildren[i]);
    //        }
    //    }
    //    return NewChildren.length >= MatchedChildren.length > 0;
    //};

    var matchElements = function(oldElm, newElm) {
        if(newElm.nodeName !== oldElm.nodeName)
            return false;

        var NewElm = jQuery(newElm);
        var OldElm = jQuery(oldElm);
        switch (newElm.nodeName.toLowerCase()) {
            case 'script':
                if(!matchURI(NewElm.attr('src'), OldElm.attr('src')))
                    return false;
                break;
            case 'link':
                if(!matchURI(NewElm.attr('href'), OldElm.attr('href')))
                    return false;
                break;
            case 'label':
                if(!NewElm.attr('for') || NewElm.attr('for') !== OldElm.attr('for'))
                    return false;
                break;
            case 'title':
                break;
            default:

                var found = false;
                for (var i=0; i<newElm.attributes.length; i++) {
                    var newAttr = newElm.attributes[i];
                    if(newAttr.name == 'style')
                        continue;

                    for (var j=0; j<oldElm.attributes.length; j++) {
                        var oldAttr = oldElm.attributes[j];
                        if(oldAttr.name == newAttr.name) {
                            switch(oldAttr.name) {
                                case 'class':
                                    if(!(newAttr.value && OldElm.hasClass(newAttr.value))
                                        && !(oldAttr.value && NewElm.hasClass(oldAttr.value)))
                                        return false;
                                    break;
                                default:
                                    if(oldAttr.value !== newAttr.value)
                                        return false;
                            }
                            found = true;
                            break;
                        }
                    }
                }

                if(found)
                    break;

                if(oldElm.childNodes.length === 0) {
                    if(newElm.childNodes.length > 0)
                        return false;
                    break;
                }
                if(oldElm.firstChild.nodeName.toLowerCase() === '#text') {
                    if(!newElm.firstChild)
                        return false;
                    if(newElm.firstChild.nodeName.toLowerCase() !== '#text')
                        return false;
                    if(oldElm.firstChild.nodeValue !== newElm.firstChild.nodeValue)
                        return false;
                    break;
                }

                return false;
        }
        //console.log("Matched: ", oldElm, newElm);
        return true;
    };

    var updateDOM = function(OldElements, NewElements) {
        var Container = OldElements.parent();
       // var NotFound = [];
        var Insert = [];
        var Remove = OldElements.not();
        for(var j=0; j<OldElements.length; j++)
            jQuery(OldElements[j]).data('matched', false);

        var LastElm = null;
        for(var i=0; i<NewElements.length; i++) {

            var NewElm = jQuery(NewElements[i]);
            if(NewElm[0].nodeName.toLowerCase() === '#text' && !NewElm[0].nodeValue.trim())
                continue;
            var found = false;
            for(j=0; j<OldElements.length; j++) {
                var OldElm = jQuery(OldElements[j]);
                if(OldElm.data('matched') === true)
                    continue;
                if(!matchElements(OldElm[0], NewElm[0]))
                    continue;


                if(OldElm.children().length > 0 || NewElm.children().length > 0) {
                    updateDOM(OldElm.children(), NewElm.children());
                } else {
                    if(OldElm.html() !== NewElm.html()) {
                        console.log("Updating: " + OldElm[0].outerHTML.replace("\n", " ") + ' with ', NewElm[0]);
                        OldElm.html(NewElm.html());
                        OldElm.trigger('update');
                    }
                }

                OldElm.data('matched', true);
                LastElm = OldElm;

                found = true;
                Remove = Remove.not(OldElm[0]);
                //OldElm.trigger('change');
                break;
            }
            if(!found) {
                //Insert[Insert.length] = NewElm[0];
                if(LastElm === null) {
                    console.log("Inserting: ", Container[0], ".append(", NewElm[0], ")");
                    Container.append(NewElm[0]);

                } else {
                    console.log("Inserting: ", LastElm[0], ".after(", NewElm[0], ")");
                    LastElm.after(NewElm[0]);
                }
            }
        }

        for(i=0; i<OldElements.length; i++) {
            var OldElement = jQuery(OldElements[i]);
            if(OldElement.data('matched') === false) {
                switch(OldElement[0].nodeName.toLowerCase()) {
                    case 'link':
                        console.log("Skipping removal of stylesheet ", OldElement[0]);
                        continue;
//
//                    case 'script':
//                    case 'title':
//                        break;

                    default:
                        break;
                }

                if(OldElement.is('.' + CLS_PRESERVE)) {
                    console.log("Preserving ", OldElement[0]);
                    Insert[Insert.length] = OldElement[0];
                } else {
                    console.log("Removing: ", OldElement[0]);
                    OldElement.trigger('remove');

                }
            }
        }

        for(i=0; i<Insert.length; i++) {
            var elm = Insert[i];
            console.log("Inserting: ", Container[0], ".append(", elm, ")");
            Container.append(elm);
            //jQuery(arr[0])[arr[1]](arr[2]);
            if(!OldElement.is('.' + CLS_PRESERVE))
                jQuery(elm).trigger('insert');
        }
    };

    var doCommand = function(command) {

    };

    var findConsoles = function(targetElm) {
        var Target = jQuery(targetElm || 'body');
        if(!Target.is('.' + CLS_CONSOLE))
            Target = Body.find('.' + CLS_CONSOLE);
        return Target;
    };

    var consolePrepend = " <span class='console-marker'>$</span> ";

    setTimeout(function() {
        var meta = getMetaContent();
        if(typeof meta[META_SESSION_ID] === 'string')
            consolePrepend =
                "<span class='console-user-id'>" + meta[META_SESSION_ID] + "</span>"
                + " <span class='console-path'>" + meta[META_PATH] + "</span>"
                + " <br/>"
                + consolePrepend;
        //var Console = findConsoles();
        //Console.html(consolePrepend + curLine);
    }, 1000);

    var curLine = '';
    var pending = 0;
    var lastLogTime = null;
    var eventMatches = {
        'keydown': function(e) {
            var Console = findConsoles(e);

            if(e.target) {
                switch(e.target.nodeName.toLowerCase()) {
                    case 'input':
                    case 'textarea':
                        return;
                    default:
                        break;
                }
            }

            switch(e.keyCode) {
                case 13: // enter
                    doCommand(curLine);
                    curLine = '';
                    Console.html(consolePrepend + curLine);
                    return;

                case 8: // back-space
                    if(curLine.length === 0)
                        return;

                    e.preventDefault();
                    curLine = curLine.substr(0, curLine.length - 1);
                    Console.html(consolePrepend + curLine);
                    return;

                default:
                    var code = e.keyCode;
                    if(!e.shiftKey && (code >= 65 && code <= 90))
                        code += 97 - 65;

                    var char = String.fromCharCode(code);
                    curLine += char;
                    Console.html(consolePrepend + curLine);

            }
        },

        'click': function(e) {
            var Target = jQuery(e.target);
            var args = Array.prototype.slice.call(arguments, 1);

            if(Target.is('a')) {
                //var url = Target.attr('href');
                //if(!url)
                //    return;
                //
                //if(url.indexOf('#') >= 0) {
                //
                //} else {
                //    Target.trigger('navigate', [url]);
                //    e.preventDefault();
                //}

            } else if(Target.is('.toggle')) {
                Target
                    .parent()
                    .trigger('toggle', args);

            } else if(Target.is('dt, dl')) {
                var DataList = null;
                if(Target.is('dl')) {
                    DataList = Target;
                } else {
                    DataList = Target
                        .next('dt + dd')
                        .children('dl');
                    //if(DataList.length === 0)
                    //    DataList = Target.parents('dl:first');
                    if(DataList.length === 0)
                        return;
                }
                DataList.trigger('toggle', args);
            }
        },

        'toggle': function(e) {
            var Target = jQuery(e.target);
            var args = Array.prototype.slice.call(arguments, 1);

            if(Target.is('form, fieldset, dl, div')) {
                e.type = Target.hasClass('closed')
                    ? 'open'
                    : (Target
                    .children()
//                        .not('.' + CLS_STAY_OPEN)
                    .filter(':hidden').length === 0
                    ? 'close'
                    : 'open');
                Target
                    .trigger(e.type, args);

            } else if(Target.is('form button, form input, fieldset button, fieldset input')) {
                Target
                    .parents('fieldset, form')
                    .first()
                    .trigger('toggle');
            }
        },

        'open close': function(e) {
            var Target = jQuery(e.target);
            var args = Array.prototype.slice.call(arguments, 1);

            if(Target.is('form, fieldset, dl, div')) {
                if(e.type === 'open') {
                    Target
                        .removeClass('closed')
                        .children()
                        .trigger('show', args);
                } else {
                    Target
                        .addClass('closed')
                        .children()
                        .not('.' + CLS_TOGGLE)
                        .not('.' + CLS_STAY_OPEN)
                        .trigger('hide');
                }

            } else if(Target.is('form button, form input, fieldset button, fieldset input')) {
                Target
                    .parents('fieldset, form')
                    .first()
                    .trigger(e.type);
            }
        },

        'hide show': function(e, duration, callback) {
            var Target = jQuery(e.target);

            if(Target.is('table')) {
                Target = Target.filter(e.type === 'hide' ? ':visible' : ':hidden');
                Target[e.type === 'hide' ? 'fadeOut' : 'fadeIn'](duration, callback);

            } else {
                Target = Target.filter(e.type === 'hide' ? ':visible' : ':hidden');
                Target[e.type === 'hide' ? 'slideUp' : 'slideDown'](duration, callback);
            }
        },

        'log info error': function (e, message, _message) {
            var Target = jQuery(e.target);
            if (Target.is('html, body'))
                Target = jQuery('body').children().first();
            var LogDivs = Target.prevAll('div.log, div.info, div.error');
            if (typeof message === 'undefined') {
                LogDivs.trigger('remove');

            } else {
                if (message instanceof Error)
                    message = message.stack
                        .replace(/[\n\r]+/g, "<br/>")
                        .replace(/[\t]|\s\s\s\s/g, "&nbsp; ");

                var argHash = hashString(message);

                if (LogDivs.length > 0) {
                    var FoundDiv = LogDivs
                        .filter('[data-hash=' + argHash + ']');

//                    if(FoundDiv.length > 0)
//                        LogDivs = LogDivs.not(FoundDiv);

                    if (lastLogTime === null || lastLogTime + ERROR_TIMEOUT < Date.now())
                        LogDivs.trigger('remove');

//                    if(FoundDiv.length > 0) {
//                        var c = parseInt(FoundDiv.attr('data-count')) || 0;
//                        FoundDiv.attr('data-count', c + 1);
//                        FoundDiv.trigger('update');
//                        return;
//                    }
                }

                Target = Target.parent().children().first();
                Target.before(
                    LogDivs = jQuery('<div class="' + e.type + '" data-hash="' + argHash + '"></div>')
                        .hide()
                        .fadeIn()
                );

                for(var i=1; i<arguments.length; i++) {
                    var arg = arguments[i];
                    if(typeof arg === 'string' && arg.indexOf('<') === -1)
                        arg = jQuery(i <= 1 ? '<legend class="toggle" />' : '<div class="log" />')
                            .append(arg);
                    LogDivs.append(arg);
                }
            }

            lastLogTime = Date.now();
        },

        'insert': function(e) {
            jQuery(e.target)
                .hide()
                .slideDown();
        },
        'update': function(e) {
            jQuery(e.target)
                .hide()
                .fadeIn();
        },
        'remove': function(e, arg) {
            var Target = jQuery(e.target);
            Target.trigger('highlight');
            Target.fadeOut(function() {
                Target.remove(arg);
            });
        },
        'highlight': function(e, callback) {
            var Target = jQuery(e.target);
            var style = Target.attr('style');
            Target.addClass('highlight');
            Target.animate({
                'padding': 20,
                'border-width': 5,
                'border-radius': 10,
                'margin': 10
            }, 800, 'swing', function() {
                Target.animate({
                    'padding': 5,
                    'border-width': 0,
                    'border-radius': 0,
                    'margin': 5
                }, 600, 'swing', function () {
                    if(callback)
                        callback(e);
                    Target
                        .removeAttr('style')
                        .removeClass('highlight')
                        .hide()
                        .fadeIn('fast');
                });
            })
        },

        'submit focus focusin focuson blur': function(e, arg) {
            var Form, Target = jQuery(e.target);
            if(Target.is('form'))
                Form = Target;
            else if(typeof Target[0].form !== "undefined")
                Form = jQuery(Target[0].form);
            else
                Form = Target.parents('form:first');

            switch(e.type) {
                case 'submit':
                    //var ajax = jQuery.extend({
                    //    data: {},
                    //    url: Form.attr('action') || document.location.href.split('?')[0],
                    //    type: Form.attr('method') || 'GET'
                    //}, arg || {});
                    //jQuery.each(Form.serializeArray(), function(i, obj) {
                    //    ajax.data[obj.name] = obj.value;
                    //});
                    //Form.trigger('navigate', ajax);
                    //e.preventDefault();
                    break;

                case 'focus':
                case 'focusin':
                    if(!Target.is('.focus')) {
                        Target.addClass('focus');
                        Form.removeClass('focus');
                    }
                    break;

                case 'blur':
                    if(Target.is('.focus')) {
                        Target.removeClass('focus');
                        Form.removeClass('focus');
                    }
                    break;

                case 'focuson':
                    Form.addClass('focus');
                    Target.addClass('focus');
                    $('html, body').animate({
                        scrollTop: Target.offset().top + 100
                    }, function() {
                        Target.trigger('highlight', function() {
                            Form.removeClass('focus');
                            Target.removeClass('focus');
                        });
                    });
                    break;

                default:
                    break;
            }
            return false;
        },

        'navigate': function(e, url) {
            if(pending > 1)
                throw new Error("Too many pending activeRequests");
            pending++;

            var Target = jQuery(e.target);
            var ajax = {url: url};
            if(typeof url === 'object')
                ajax = url;
            ajax = jQuery.extend({
                complete: function(jqXHR) {
                    pending--;

                    var url = jqXHR.getResponseHeader('X-Location');
                    if(url)
                        ajax.url = url;
                    Target.trigger("navigation-complete", [jqXHR.responseText, ajax, jqXHR]);
                    //Target.trigger("log", [jqXHR.statusText]);

                    var r = jqXHR.getResponseHeader('Refresh');
                    if(r) {
                        r = r.split('; URL=');
                        var sec = r[0];
                        setInterval(function() {
                            Target.trigger('info', 'Redirecting in ' + sec + ' seconds...');
                            sec--;
                        }, 1000);
                    }
                },
                success: function(data, textStatus, jqXHR) {
                    Target.trigger("navigation-success", [jqXHR.responseText, ajax, jqXHR]);

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Target.trigger("navigation-error", [errorThrown, jqXHR.responseText, ajax, jqXHR]);
                }
            }, ajax);

            jQuery.ajax(ajax);

            Target
                .parent()
                .find('div.log, div.info, div.error')
                .trigger('remove');
        },

        'navigation-complete': function(e, html, ajax, jqXHR) {
            allowCache = true;
            var HTML = jQuery(html);

            var NewHeaders = HTML.filter('script, link, title, meta');
            var NewBody = HTML.not(NewHeaders);
            var OldBody = Body.children();

            var OldContent = Body.children('section.content');
            var NewContent = NewBody.filter('section.content');

            if (NewContent.length === 0 && OldContent.length > 0) { // !matchChildren(OldBody, NewBody)) {
                allowCache = false;

                var content = jQuery('<div class="navigation-frame"></div>')
                    .append(html)
                    .hide();
                jQuery(e.target).trigger(jqXHR.status === 200 ? "info" : "error", [jqXHR.statusText, content]);

                return;
            }

            var OldHeaders = jQuery('head script, head link, head title, head meta');
            updateDOM(OldHeaders, NewHeaders);

            if (ajax.method === 'POST') {
                OldBody.find('.' + CLS_PRESERVE_POST)
                    .addClass(CLS_PRESERVE);
            } else if (!ajax.method || ajax.method === 'GET') {
                OldBody.find('.' + CLS_PRESERVE_GET)
                    .addClass(CLS_PRESERVE);
            }

            updateDOM(OldBody, NewBody);

            OldBody
                .find('.' + CLS_PRESERVE_POST + ', .' + CLS_PRESERVE_GET)
                .filter('.' + CLS_PRESERVE)
                .removeClass(CLS_PRESERVE);

            HTML.remove();

            if (allowCache)
                allowCache = false;

            //var meta = getMetaContent();

            Body.trigger('ready');

            if (ajax.url && ajax.skipPush !== true)
                history.pushState(html, document.title, ajax.url);

            jQuery('html,body').animate({scrollTop: 0}, 'slow');
        }
        //
        //'navigation-error': function(e, error, html, ajax) {
        //    var Target = jQuery(e.target);
        //    if(Target.is('body, html'))
        //        Target = jQuery('body').children().first();
        //
        //    Target
        //        .prevAll('div.navigation.error')
        //        .trigger('remove');
        //
        //    allowCache = true;
        //    Target.before(
        //        jQuery('<div class="navigation error"></div>')
        //            .hide()
        //            .append('<legend>' + ajax.url + '</legend>')
        //            .append(html)
        //            .trigger('toggle')
        //    );
        //
        //    if(allowCache)
        //        allowCache = false;
        //}
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

    var ready = function() {};

    jQuery(document).ready(function() {
        jQuery(window).resize(onResize);
        onResize();

        window.onpopstate = function(event) {
            var html = event.state;
            var ajax = {
                url: document.location.href,
                skipPush: true
            };
            Body.trigger('navigation-complete', [html, ajax]);
        };

        Body = jQuery('body');

        var EVENTS = Object.keys(eventMatches).join(' ');
        if(EVENTS && typeof window[EVENTS] === 'undefined'){
            Body.on(EVENTS, eventHandler)
                .on('ready', ready);
            ready();
            window[EVENTS] = EVENTS;
        }
    });

})();


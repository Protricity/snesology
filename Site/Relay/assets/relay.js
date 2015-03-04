/**
 * Created with JetBrains PhpStorm.
 * User: Ari
 * Date: 8/1/13
 * Time: 8:40 PM
 * To change this template use File | Settings | File Templates.
 */
(function(){
    
    var FORM_CLASS = 'form-relay-log';
    var PARAM_LOG = 'log';
    var LOG_CONTAINER = 'log-container';

    var CLIENT_PORT = 7845; // 7845;
    var WEB_SOCKET_URL = 'ws://' + document.location.host + ':' + CLIENT_PORT + '/socket';
    var RECONNECT_TIMEOUT = 5000;

    var pending = 0;
    var ready = function() {

        jQuery('input[name=' + PARAM_LOG + ']').each(function(i, input) {
            if(typeof input.form.initRelayLog !== 'undefined')
                return;

            input.form.initRelayLog = true;

            var Input = jQuery(input);
            var Form = jQuery(input.form);
            var LogContainer = Form.find('.' + LOG_CONTAINER);
            var ChatSocket;
            var formAction = Form.attr('action');

            if(pending > 1)
                throw new Error("Too many pending activeRequests");
            pending++;
            
            LogContainer.scrollTop(LogContainer[0].scrollHeight);

            Form.on('submit', function(e, ajax) {
                e.preventDefault();

                var val = Input.val();

                if(ChatSocket && ChatSocket.readyState === ChatSocket.OPEN) {
                    var data = {};
                    jQuery.each(Form.serializeArray(), function(i, pair) {
                       data[pair.name] = pair.value;
                    });
                    data['action'] = formAction;
                    console.log("Sending: ", data);
                    ChatSocket.send(JSON.stringify(data));
                    
                    Input.val('');
//                     LogContainer.append('<div class="relay-log"><span class="relay-account">' + data.account.name + '</span> ' + data.log + '</span>');
//                     LogContainer.scrollTop(LogContainer[0].scrollHeight);

                    return;
                }


                ajax = jQuery.extend({
                    method: 'POST',
                    data: Form.serialize(),
                    dataType: 'json',
                    url: Form.attr('action'),
                    complete: function(jqXHR) {
                        pending--;

                        var r = jqXHR.getResponseHeader('Refresh');
                        if(r) {
                            r = r.split('; URL=');
                            var sec = r[0];
                            setInterval(function() {
                                Form.trigger('info', 'Redirecting in ' + sec + ' seconds...');
                                sec--;
                            }, 1000);
                        }
                    },
                    success: function(data) {
                        Input.val('');
                        LogContainer.append('<div class="relay-log"><span class="relay-account">' + data.account.name + '</span> ' + data.log + '</span>');
                        LogContainer.scrollTop(LogContainer[0].scrollHeight);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log(arguments);
                    }
                }, ajax);

                jQuery.ajax(ajax);
            });


            var initWebSocket = null;
            initWebSocket = function() {
                try {
                    ChatSocket = new WebSocket(WEB_SOCKET_URL);
                } catch (error) {
                    LogContainer.append('<div class="error">' + error + '</span>');
                }
                
                ChatSocket.onopen = function(e) {
                    console.info("WebSocket Open: " + WEB_SOCKET_URL, e);
                };

                ChatSocket.onclose = function(e) {
                    console.log("WebSocket Closed: " + WEB_SOCKET_URL, e);
                    setTimeout(initWebSocket, RECONNECT_TIMEOUT);
                };

                ChatSocket.onmessage = function(e) {
                    console.info("Receiving: ", e.data);
                    var data = jQuery.parseJSON(e.data);
                    if(data.path = formAction) {
                        LogContainer.append('<div class="relay-log"><span class="relay-account">' + (data.account.name || data.account.fingerprint) + '</span> ' + data.log + '</span>');
                        LogContainer.scrollTop(LogContainer[0].scrollHeight);
                    }
                };

                ChatSocket.onerror = function(e) {
                    console.error(e);
                    // LogContainer.append('<div class="error">' + e.message + '</span>');
                };
            };
            initWebSocket();


        });
    };

    jQuery(document).ready(function() {
        jQuery('body')
            .on('ready', ready);
        ready();
    });

})();
/**
 * Created by ari on 3/4/2015.
 */

var viewers = {};
onconnect = function (e) {
    //var name = getNextName();
    //event.ports[0]._data = { port: event.ports[0], name: name, x: 0, y: 0};
    //viewers[name] = event.ports[0]._data;
    e.ports[0].postMessage('connected');

    e.ports[0].onmessage = function(e) {
        console.log("Received: ", e);
    }
};


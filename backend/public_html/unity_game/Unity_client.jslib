mergeInto(
    LibraryManager.library,
    {
        start: function() {
            window.ctx = {
                processor: null
            };
            window.ctx.processor = {
                ws: null,
                state: 'init',
                payload: null,
                command: null,
                output: null,
                auth: null,
                pendingCommand: null,
                eventCodes: {},

                init: function() {
                    this.eventCodes[0] = 'ERROR';
                    this.eventCodes[1] = 'INFO';
                    this.eventCodes[2] = 'DEFAULT';
                    this.eventCodes[3] = 'MODE';
                    this.eventCodes[4] = 'STATE';
                    this.eventCodes[20] = 'DEPART';
                    this.eventCodes[21] = 'ARRIVE';
                    this.eventCodes[22] = 'REPAIR';
                    this.eventCodes[23] = 'AUTOREPAIR';
                    this.eventCodes[25] = 'NAV';
                    this.eventCodes[50] = 'SELL';
                    this.eventCodes[51] = 'BUY';
                    this.eventCodes[51] = 'TRADE';
                    this.eventCodes[53] = 'GAIN_GOLD';
                    this.eventCodes[54] = 'GAIN_RES';
                    this.eventCodes[55] = 'LOSS_GOLD';
                    this.eventCodes[56] = 'LOSS_RES';
                    this.eventCodes[57] = 'OVERLOAD';
                    this.eventCodes[100] = 'ENCOUNTER';
                    this.eventCodes[101] = 'WIN';
                    this.eventCodes[102] = 'LOSE';
                    this.eventCodes[103] = 'FLEE';
                    this.eventCodes[104] = 'ATTACK';
                    this.eventCodes[105] = 'MISS';
                    this.eventCodes[106] = 'EVADE';
                    this.eventCodes[108] = 'DAMAGE';
                    this.eventCodes[109] = 'USE';
                    this.eventCodes[110] = 'VOLLEY_SHOT';
                    this.eventCodes[111] = 'FLEE_FAIL';
                    this.eventCodes[200] = 'WORMHOLE';
                    this.eventCodes[201] = 'METEOR_RAIN';
                    this.eventCodes[300] = 'PVP_ASSAULT';
                    this.eventCodes[301] = 'PVP_INCOMING';

                    this.ws = new WebSocket('ws://89.111.136.45:8888');
                    var ws = this.ws;
                    ws.onopen = function() {
                        console.log('*** ws open');
                        ws.send('YARR');
                    };
                    ws.onerror = function() {
                        console.log('*** ws error');
                    };
                    ws.onclose = function() {
                        console.log('*** ws closed');
                    };
                    ws.onmessage = this.onMessage.bind(this);
                },
                parseEvent: function(data) {
                    var parts = data.split('&');
                    var params = {};
                    for (var i in parts) {
                        var p = parts[i].indexOf('=');
                        if (p !== -1) {
                            var key = parts[i].substring(0, p);
                            var value = parts[i].substring(p + 1);
                            if (key === 't') {
                                params.type = this.eventCodes[parseInt(value, 10)];
                            } else if (key === 'm') {
                                params.msg = value;
                            } else {
                                params[key] = value;
                            }
                        }
                    }
                    return params;
                },
                onMessage: function(evt) {
                    this.process(evt.data);
                },

                process: function(msg) {
                    if (this.update(msg)) {
                        if (this.output) {
                            this.ws.send(this.output);
                        }
                    } else {
                        var jsondata = JSON.parse(msg);
                        for (var i in jsondata.responses) {
                            var r = this.parseEvent(jsondata.responses[i]);
                            jsondata.responses[i] = r;
                        }
                        console.log(jsondata);
                        unityInstance.SendMessage('JsonReceiver', 'ReceiveJsonMessage', JSON.stringify(jsondata));
                    }
                },
                update: function(msg) {
                    this.command = null;
                    this.payload = null;
                    var div = msg.indexOf(' ');
                    if (div === -1) {
                        this.command = msg;
                    } else {
                        this.command = msg.substring(0, div);
                        this.payload = msg.substring(div + 1);
                    }
                    if (!this.command && !this.payload) {
                        return false;
                    }
                    if (this.state === 'init') {
                        if (this.command === 'OK') {
                            this.state = 'start';
                            this.output = 'START ' + this.auth;
                            return true;
                        } else if (this.command === 'ERROR') {
                            alert(this.payload);
                            return false;
                        }
                        var xhr = new XMLHttpRequest();
                        xhr.that = this;
                        xhr.open('POST', 'http://89.111.136.45/game/index.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onreadystatechange = function() {
                            if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                                console.log('response=' + this.response);
                                this.that.process(this.response);
                            }
                            // http request error
                            return false;
                        };
                        this.auth = this.payload;
                        console.log('sending auth: ' + this.payload);
                        xhr.send('auth=' + this.payload);
                        this.output = null;
                        return true;
                    } else if (this.state === 'start') {
                        this.output = null;
                        if (this.command === 'OK') {
                            this.state = 'disabled';
                            this.output = 'STATE';
                            return true;
                        }
                    }
                    return false;
                }
            };
            window.ctx.processor.init();
        },
        command: function(msg) {
            window.ctx.processor.ws.send(Pointer_stringify(msg));
        }
    }
);
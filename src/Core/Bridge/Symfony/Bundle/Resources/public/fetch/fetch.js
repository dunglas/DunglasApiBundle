'use strict';

var http = require('http');
var https = require('https');
var urllib = require('url');
var utillib = require('util');
var zlib = require('zlib');
var dns = require('dns');
var Stream = require('stream').Readable;
var CookieJar = require('./cookiejar').CookieJar;
var encodinglib = require('encoding');
var net = require('net');

var USE_ALLOC = typeof Buffer.alloc === 'function';

exports.FetchStream = FetchStream;
exports.CookieJar = CookieJar;
exports.fetchUrl = fetchUrl;

function FetchStream(url, options) {
    Stream.call(this);

    options = options || {};

    this.url = url;
    if (!this.url) {
        return this.emit('error', new Error('url not defined'));
    }

    this.userAgent = options.userAgent || 'FetchStream';

    this._redirect_count = 0;

    this.options = options || {};
    this.normalizeOptions();

    // prevent errors before 'error' handler is set by defferring actions
    if (typeof setImmediate !== 'undefined') {
        setImmediate(this.runStream.bind(this, url));
    } else {
        process.nextTick(this.runStream.bind(this, url));
    }
    this.responseBuffer = USE_ALLOC ? Buffer.alloc(0, '', 'binary') : new Buffer(0, 'binary');
    this.ended = false;
    this.readyToRead = 0;
}
utillib.inherits(FetchStream, Stream);

FetchStream.prototype._read = function (size) {
    if (this.ended && this.responseBuffer.length === 0) {
        this.push(null);
        return;
    }
    this.readyToRead += size;
    this.drainBuffer();
};

FetchStream.prototype.drainBuffer = function () {
    if (this.readyToRead === 0) {
        return;
    }
    if (this.responseBuffer.length === 0) {
        return;
    }
    var push;
    var rest;
    var restSize;

    if (this.responseBuffer.length > this.readyToRead) {
        push = USE_ALLOC ? Buffer.alloc(this.readyToRead, '', 'binary') : new Buffer(this.readyToRead, 'binary');
        this.responseBuffer.copy(push, 0, 0, this.readyToRead);
        restSize = this.responseBuffer.length - this.readyToRead;
        rest = USE_ALLOC ? Buffer.alloc(restSize, '', 'binary') : new Buffer(restSize, 'binary');
        this.responseBuffer.copy(rest, 0, this.readyToRead);
    } else {
        push = this.responseBuffer;
        rest = USE_ALLOC ? Buffer.alloc(0, '', 'binary') : new Buffer(0, 'binary');
    }
    this.responseBuffer = rest;
    this.readyToRead = 0;
    if (this.options.encoding) {
        this.push(push, this.options.encoding);
    } else {
        this.push(push);
    }
};

FetchStream.prototype.destroy = function (ex) {
    this.emit('destroy', ex);
};

FetchStream.prototype.normalizeOptions = function () {

    // cookiejar
    this.cookieJar = this.options.cookieJar || new CookieJar();

    // default redirects - 10
    // if disableRedirect is set, then 0
    if (!this.options.disableRedirect && typeof this.options.maxRedirects !== 'number' &&
        !(this.options.maxRedirects instanceof Number)) {
        this.options.maxRedirects = 10;
    } else if (this.options.disableRedirects) {
        this.options.maxRedirects = 0;
    }

    // normalize header keys
    // HTTP and HTTPS takes in key names in case insensitive but to find
    // an exact value from an object key name needs to be case sensitive
    // so we're just lowercasing all input keys
    this.options.headers = this.options.headers || {};

    var keys = Object.keys(this.options.headers);
    var newheaders = {};
    var i;

    for (i = keys.length - 1; i >= 0; i--) {
        newheaders[keys[i].toLowerCase().trim()] = this.options.headers[keys[i]];
    }

    this.options.headers = newheaders;

    if (!this.options.headers['user-agent']) {
        this.options.headers['user-agent'] = this.userAgent;
    }

    if (!this.options.headers.pragma) {
        this.options.headers.pragma = 'no-cache';
    }

    if (!this.options.headers['cache-control']) {
        this.options.headers['cache-control'] = 'no-cache';
    }

    if (!this.options.disableGzip) {
        this.options.headers['accept-encoding'] = 'gzip, deflate';
    } else {
        delete this.options.headers['accept-encoding'];
    }

    // max length for the response,
    // if not set, default is Infinity
    if (!this.options.maxResponseLength) {
        this.options.maxResponseLength = Infinity;
    }

    // method:
    // defaults to GET, or when payload present to POST
    if (!this.options.method) {
        this.options.method = this.options.payload || this.options.payloadSize ? 'POST' : 'GET';
    }

    // set cookies
    // takes full cookie definition strings as params
    if (this.options.cookies) {
        for (i = 0; i < this.options.cookies.length; i++) {
            this.cookieJar.setCookie(this.options.cookies[i], this.url);
        }
    }

    // rejectUnauthorized
    if (typeof this.options.rejectUnauthorized === 'undefined') {
        this.options.rejectUnauthorized = true;
    }
};

FetchStream.prototype.parseUrl = function (url) {
    var urlparts = urllib.parse(url, false, true),
        transport,
        urloptions = {
            host: urlparts.hostname || urlparts.host,
            port: urlparts.port,
            path: urlparts.pathname + (urlparts.search || '') || '/',
            method: this.options.method,
            rejectUnauthorized: this.options.rejectUnauthorized
        };

    switch (urlparts.protocol) {
        case 'https:':
            transport = https;
            break;
        case 'http:':
        default:
            transport = http;
            break;
    }

    if (transport === https) {
        if('agentHttps' in this.options){
            urloptions.agent = this.options.agentHttps;
        }
        if('agent' in this.options){
            urloptions.agent = this.options.agent;
        }
    } else {
        if('agentHttp' in this.options){
            urloptions.agent = this.options.agentHttp;
        }
        if('agent' in this.options){
            urloptions.agent = this.options.agent;
        }
    }

    if (!urloptions.port) {
        switch (urlparts.protocol) {
            case 'https:':
                urloptions.port = 443;
                break;
            case 'http:':
            default:
                urloptions.port = 80;
                break;
        }
    }

    urloptions.headers = this.options.headers || {};

    if (urlparts.auth) {
        var buf = USE_ALLOC ? Buffer.alloc(Buffer.byteLength(urlparts.auth), urlparts.auth) : new Buffer(urlparts.auth);
        urloptions.headers.Authorization = 'Basic ' + buf.toString('base64');
    }

    return {
        urloptions: urloptions,
        transport: transport
    };
};

FetchStream.prototype.setEncoding = function (encoding) {
    this.options.encoding = encoding;
};

FetchStream.prototype.runStream = function (url) {
    var url_data = this.parseUrl(url),
        cookies = this.cookieJar.getCookies(url);

    if (cookies) {
        url_data.urloptions.headers.cookie = cookies;
    } else {
        delete url_data.urloptions.headers.cookie;
    }

    if (this.options.payload) {
        url_data.urloptions.headers['content-length'] = Buffer.byteLength(this.options.payload || '', 'utf-8');
    }

    if (this.options.payloadSize) {
        url_data.urloptions.headers['content-length'] = this.options.payloadSize;
    }

    if (this.options.asyncDnsLoookup) {
        var dnsCallback = (function (err, addresses) {
            if (err) {
                this.emit('error', err);
                return;
            }

            url_data.urloptions.headers.host = url_data.urloptions.hostname || url_data.urloptions.host;
            url_data.urloptions.hostname = addresses[0];
            url_data.urloptions.host = url_data.urloptions.headers.host + (url_data.urloptions.port ? ':' + url_data.urloptions.port : '');

            this._runStream(url_data, url);
        }).bind(this);

        if (net.isIP(url_data.urloptions.host)) {
            dnsCallback(null, [url_data.urloptions.host]);
        } else {
            dns.resolve4(url_data.urloptions.host, dnsCallback);
        }
    } else {
        this._runStream(url_data, url);
    }
};

FetchStream.prototype._runStream = function (url_data, url) {

    var req = url_data.transport.request(url_data.urloptions, (function (res) {

        // catch new cookies before potential redirect
        if (Array.isArray(res.headers['set-cookie'])) {
            for (var i = 0; i < res.headers['set-cookie'].length; i++) {
                this.cookieJar.setCookie(res.headers['set-cookie'][i], url);
            }
        }

        if ([301, 302, 303, 307, 308].indexOf(res.statusCode) >= 0) {
            if (!this.options.disableRedirects && this.options.maxRedirects > this._redirect_count && res.headers.location) {
                this._redirect_count++;
                req.destroy();
                this.runStream(urllib.resolve(url, res.headers.location));
                return;
            }
        }

        this.meta = {
            status: res.statusCode,
            responseHeaders: res.headers,
            finalUrl: url,
            redirectCount: this._redirect_count,
            cookieJar: this.cookieJar
        };

        var curlen = 0,
            maxlen,

            receive = (function (chunk) {
                if (curlen + chunk.length > this.options.maxResponseLength) {
                    maxlen = this.options.maxResponseLength - curlen;
                } else {
                    maxlen = chunk.length;
                }

                if (maxlen <= 0) {
                    return;
                }

                curlen += Math.min(maxlen, chunk.length);
                if (maxlen >= chunk.length) {
                    if (this.responseBuffer.length === 0) {
                        this.responseBuffer = chunk;
                    } else {
                        this.responseBuffer = Buffer.concat([this.responseBuffer, chunk]);
                    }
                } else {
                    this.responseBuffer = Buffer.concat([this.responseBuffer, chunk], this.responseBuffer.length + maxlen);
                }
                this.drainBuffer();
            }).bind(this),

            error = (function (e) {
                this.ended = true;
                this.emit('error', e);
                this.drainBuffer();
            }).bind(this),

            end = (function () {
                this.ended = true;
                if (this.responseBuffer.length === 0) {
                    this.push(null);
                }
            }).bind(this),

            unpack = (function (type, res) {
                var z = zlib['create' + type]();
                z.on('data', receive);
                z.on('error', error);
                z.on('end', end);
                res.pipe(z);
            }).bind(this);

        this.emit('meta', this.meta);

        if (res.headers['content-encoding']) {
            switch (res.headers['content-encoding'].toLowerCase().trim()) {
                case 'gzip':
                    return unpack('Gunzip', res);
                case 'deflate':
                    return unpack('InflateRaw', res);
            }
        }

        res.on('data', receive);
        res.on('end', end);

    }).bind(this));

    req.on('error', (function (e) {
        this.emit('error', e);
    }).bind(this));

    if (this.options.timeout) {
        req.setTimeout(this.options.timeout, req.abort.bind(req));
    }
    this.on('destroy', req.abort.bind(req));

    if (this.options.payload) {
        req.end(this.options.payload);
    } else if (this.options.payloadStream) {
        this.options.payloadStream.pipe(req);
        this.options.payloadStream.resume();
    } else {
        req.end();
    }
};

function fetchUrl(url, options, callback) {
    if (!callback && typeof options === 'function') {
        callback = options;
        options = undefined;
    }
    options = options || {};

    var fetchstream = new FetchStream(url, options),
        response_data, chunks = [],
        length = 0,
        curpos = 0,
        buffer,
        content_type,
        callbackFired = false;

    fetchstream.on('meta', function (meta) {
        response_data = meta;
        content_type = _parseContentType(meta.responseHeaders['content-type']);
    });

    fetchstream.on('data', function (chunk) {
        if (chunk) {
            chunks.push(chunk);
            length += chunk.length;
        }
    });

    fetchstream.on('error', function (error) {
        if (error && error.code === 'HPE_INVALID_CONSTANT') {
            // skip invalid formatting errors
            return;
        }
        if (callbackFired) {
            return;
        }
        callbackFired = true;
        callback(error);
    });

    fetchstream.on('end', function () {
        if (callbackFired) {
            return;
        }
        callbackFired = true;

        buffer = USE_ALLOC ? Buffer.alloc(length) : new Buffer(length);
        for (var i = 0, len = chunks.length; i < len; i++) {
            chunks[i].copy(buffer, curpos);
            curpos += chunks[i].length;
        }

        if (content_type.mimeType === 'text/html') {
            content_type.charset = _findHTMLCharset(buffer) || content_type.charset;
        }

        content_type.charset = (options.overrideCharset || content_type.charset || 'utf-8').trim().toLowerCase();


        if (!options.disableDecoding && !content_type.charset.match(/^utf-?8$/i)) {
            buffer = encodinglib.convert(buffer, 'UTF-8', content_type.charset);
        }

        if (options.outputEncoding) {
            return callback(null, response_data, buffer.toString(options.outputEncoding));
        } else {
            return callback(null, response_data, buffer);
        }

    });
}

function _parseContentType(str) {
    if (!str) {
        return {};
    }
    var parts = str.split(';'),
        mimeType = parts.shift(),
        charset, chparts;

    for (var i = 0, len = parts.length; i < len; i++) {
        chparts = parts[i].split('=');
        if (chparts.length > 1) {
            if (chparts[0].trim().toLowerCase() === 'charset') {
                charset = chparts[1];
            }
        }
    }

    return {
        mimeType: (mimeType || '').trim().toLowerCase(),
        charset: (charset || 'UTF-8').trim().toLowerCase() // defaults to UTF-8
    };
}

function _findHTMLCharset(htmlbuffer) {

    var body = htmlbuffer.toString('ascii'),
        input, meta, charset;

    if ((meta = body.match(/<meta\s+http-equiv=["']content-type["'][^>]*?>/i))) {
        input = meta[0];
    }

    if (input) {
        charset = input.match(/charset\s?=\s?([a-zA-Z\-0-9]*);?/);
        if (charset) {
            charset = (charset[1] || '').trim().toLowerCase();
        }
    }

    if (!charset && (meta = body.match(/<meta\s+charset=["'](.*?)["']/i))) {
        charset = (meta[1] || '').trim().toLowerCase();
    }

    return charset;
}

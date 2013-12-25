/*
 *  Client actions handling
 *  MIT License
 */
;
(function ($, window, document, undefined) {
    var pluginName = 'ca';
    var defaults = {
        caDataKey: 'ca',                // Name of element's "data" entry that stores applied client action
        classes: {                      // Various CSS classes to use for additional control of client actions behavior
            caApplied: 'ca-applied',    // CSS class to apply to elements that have applied client actions
            autoTarget: 'ca-target',    // CSS class to use to mark target element for client actions that doesn't define their target explicitly
            baseCa: 'ca-base'           // CSS class to use to mark element that contains "base" client action to use for current state client action
        },
        loading: {                      // Options for resources loading
            indicator: {
                enabled: false,         // TRUE to indicate "loading" process
                indicator: null,        // Either jQuery selector to element that should be displayed as "loading" indicator or object with "start" and "stop" methods
                mask: $.isFunction($.fn.mask),  // TRUE to use jquery.loadmask plugin if it is available, FALSE to avoid using it, string for custom loading message
                activity: $.isFunction($.fn.activity),  // TRUE to use jquery.activity plugin if it is available, FALSE to avoid using it Or object of configuration options for jquery.activity plugin
                loadingClass: null      // Additional class to add to target element for a time its being loaded
            },
            ajax: {                     // Additional settings for $.ajax()
                type: 'POST',
                async: true
            },
            stateParam: '__state',      // Name of request parameter to store app.state modifications
            urlTransformer: {           // URL transformation functions for various tasks
                load: null              // URL transformer for "load" client actions, it should accept ClientAction object as argument and return either URL string or {url:"url to load",data:"data to send to server"}
            },
            onload: null,               // Callback function to call when loading will be completed
            onerror: null               // Callback function to call in a case of error during loading process
        }
    };

    function CaUtils() {

    }

    CaUtils.prototype = {
        /**
         * Convert given object from "plain" dotted index notation into normal object
         *
         * @param {Object} obj          Object to convert
         * @return {Object}
         */
        fromPlain: function (obj) {
            var result = {}, value, part, parts, target;
            for (var i in obj) {
                value = obj[i];
                target = result;
                parts = i.split('.');
                do {
                    part = parts.shift();
                    if (!$.isPlainObject(result[part] || false)) {
                        target[part] = {};
                    }
                    if (parts.length) {
                        target = target[part];
                    } else {
                        target[part] = value;
                    }
                } while (parts.length);
            }
            return result;
        },

        /**
         * Convert given object into plain object with dotted index notation
         *
         * @param {Object} obj          Object to convert
         * @param {String} [prefix]     Keys prefix
         * @return {Object}
         */
        toPlain: function (obj, prefix) {
            var plain = {};
            if (typeof(prefix) !== 'string') {
                prefix = '';
            }
            if ((prefix.length && prefix.substr(-1) !== '.')) {
                prefix += '.';
            }
            for (var key in obj) {
                var value = obj[key];
                if ($.isPlainObject(value)) {
                    value = this.toPlain(value, prefix + key);
                    for (var j in value) {
                        plain[j] = value[j];
                    }
                    continue;
                }
                plain[prefix + key] = value;
            }
            return plain;
        },

        /**
         * Get list of keys from given object
         *
         * @param {Object} obj          Object to get keys of
         * @param {Boolean} [sorted]    TRUE to sort keys before returning
         * @returns {Array}
         */
        getKeys: function (obj, sorted) {
            var keys = [];
            if ($.isPlainObject(obj)) {
                for (var i in obj) {
                    keys.push(i);
                }
            }
            if (sorted || false) {
                keys.sort();
            }
            return keys;
        },

        /**
         * Apply given modifications to given object
         *
         * @param {Object} obj              Object to apply modifications to
         * @param {Object} modifications    Modifications to apply
         * @param {Array} [modified]        List of modified values
         * @param {String} [prefix]         Prefix to list of modified values
         * @returns {Array}
         */
        modify: function (obj, modifications, modified, prefix) {
            if (!(modified instanceof Array)) {
                modified = [];
            }
            if (typeof(prefix) !== 'string') {
                prefix = '';
            }
            if ((prefix.length && prefix.substr(-1) !== '.')) {
                prefix += '.';
            }
            for (var p in modifications) {
                var parts = p.split('.');
                var v = modifications[p];
                for (var i = 0; i < parts.length; i++) {
                    var name = parts[i];
                    if (typeof(obj[name]) !== 'undefined') {
                        if (i == (parts.length - 1)) {
                            if (($.isPlainObject(obj[name])) && ($.isPlainObject(v))) {
                                modified = CaUtils.prototype.modify(obj[name], v, modified, prefix + p);
                            } else {
                                obj[name] = v;
                                modified.push(prefix + p);
                            }
                        } else {
                            if (($.isPlainObject(obj[name])) || (obj[name] instanceof Array)) {
                                obj = obj[name];
                            } else {
                                break;
                            }
                        }
                    } else {
                        break;
                    }
                }
            }
            return modified;
        }
    };

    /**
     * Fixed structure implementation
     *
     * @param {Object} definition   Structure definition
     * @param {Object} [contents]   Initial contents
     * @constructor
     */
    function CaStruct(definition, contents) {
        this._init(definition, contents);
    }

    CaStruct.prototype = {
        _init: function (definition, contents) {
            this._struct = {};
            if (!$.isPlainObject(definition)) {
                definition = {};
            }
            this._struct = CaUtils.prototype.fromPlain(definition);
            if (contents !== undefined) {
                this.set(contents);
            }
        },

        /**
         * Get structure value
         *
         * @param {string} [path]   Path into structure object to get value of
         * @return {*}
         */
        get: function (path) {
            if (path === undefined) {
                return this._struct;
            }
            var parts = path.split('.');
            var struct = this._struct;
            for (var i = 0; i < parts.length; i++) {
                var name = parts[i];
                if (typeof(struct[name]) !== 'undefined') {
                    if (i == (parts.length - 1)) {
                        return struct[name];
                    } else {
                        if (($.isPlainObject(struct[name])) || (struct[name] instanceof Array)) {
                            struct = struct[name];
                        } else {
                            break;
                        }
                    }
                } else {
                    break;
                }
            }
        },

        /**
         * Set structure value by given path
         *
         * @param {string} [path]   Path into structure object to set value of (can be skipped)
         * @param {*} [value]       New value of application state entry
         */
        set: function (path, value) {
            if (typeof(path) !== 'string') {
                value = path;
            } else {
                var t = {};
                t[path] = value;
                value = t;
            }
            CaUtils.prototype.modify(this._struct, value);
        }
    };

    /**
     * Application state structure implementation
     *
     * @param {String} [id]         State Id (can be skipped)
     * @param {Object} definition   State structure definition
     * @param {Object} [state]      Current state of structure
     * @constructor
     */
    function CaState(id, definition, state) {
        this._id = null;
        this._definition = null;
        if (typeof(id) !== 'string') {
            state = definition;
            definition = id;
            id = null;
        }
        this._id = id;
        this._definition = definition;
        this._init(definition);
        if ($.isPlainObject(state)) {
            this.set(state, true);
        }
    }

    CaState.prototype = $.extend(CaStruct.prototype, {
        /**
         * Get application state Id
         *
         * @returns {String}
         */
        id: function () {
            return this._id;
        },

        /**
         * Modify application state
         *
         * @param {string} [path]       Path into structure object to set value of (can be skipped)
         * @param {*} [value]           New value of application state entry
         * @param {Boolean} [silent]    TRUE to perform silent modification (e.g. to sync app.state with external state source)
         */
        modify: function (path, value, silent) {
            if (typeof(path) !== 'string') {
                silent = value;
                value = path;
                path = undefined;
            }
            if (path !== undefined) {
                var t = {};
                t[path] = value;
                value = t;
            }
            var modified = CaUtils.prototype.modify(this._struct, value);
            this._notify(modified, silent);
        },

        /**
         * Reset application state to its default state
         *
         * @param {Boolean} [silent]    TRUE to perform silent modification
         */
        reset: function (silent) {
            var modified = CaUtils.prototype.modify(this._struct, this._definition);
            this._notify(modified, silent);
        },

        /**
         * Set given state as current application state
         *
         * @param {Object} state        State to set
         * @param {Boolean} [silent]    TRUE to perform silent modification
         */
        set: function (state, silent) {
            this.reset(silent);
            this.modify(state, silent);
        },

        /**
         * Toggle given value into application state
         *
         * @param {string} path         Path into application state field to toggle value in
         * @param {*} [value]           Value to toggle
         * @param {Boolean} [silent]    TRUE to perform silent modification
         */
        toggle: function (path, value, silent) {
            var entry = this.get(path);
            if (entry instanceof Array) {
                if ($.inArray(value, entry) !== -1) {
                    entry = $.grep(entry, function (v) {
                        return(v !== value);
                    });
                } else {
                    entry.push(value);
                }
            } else if ((entry === true) || (entry === false)) {
                // value have no meaning here
                silent = value;
                entry = !entry;
            }
            this.modify(path, entry, silent);
        },

        /**
         * Notify application about application state modifications
         *
         * @param {Array} modified      List of modified fields in application state
         * @param {Boolean} [silent]    TRUE to suppress notification
         * @private
         */
        _notify: function (modified, silent) {
            if ((modified.length) && (!(silent || false))) {
                $(document).trigger('ca.state.modified', modified);
            }
        }
    });

    /**
     * Client action object
     *
     * @constructor
     * @param {string|ClientAction|Object} [ca] Client action information
     */
    function ClientAction(ca) {
        this.action = null;
        this.target = null;
        this.event = null;
        this.url = null;
        this.args = {};
        this.operation = null;
        this.state = {};
        if (ca !== undefined) {
            this.modify(this.parse(ca));
        }
    }

    ClientAction.prototype = {
        /**
         * Parse given client action information
         *
         * @param {string|ClientAction|jQuery|Node|Object} data
         * @return {Object}
         */
        parse: function (data) {
            var n, t;
            var result = {};
            if ((data instanceof Node) || (data instanceof NodeList)) {
                data = $(data);
            }
            if (data instanceof jQuery) {
                var map = {};
                for (n in this) {
                    if (this.hasOwnProperty(n)) {
                        map['ca' + n.substr(0, 1).toUpperCase() + n.substr(1)] = n;
                    }
                }
                data.each(function () {
                    var nResult = {};
                    var data = $(this).data();
                    if (!$.isEmptyObject(data)) {
                        for (n in map) {
                            if (data.hasOwnProperty(n)) {
                                nResult[map[n]] = data[n];
                            }
                        }
                        if (!$.isEmptyObject(nResult)) {
                            result = nResult;
                            return false;
                        }
                    }
                    return true;
                });
            } else if ((data instanceof ClientAction) || ($.isPlainObject(data))) {
                for (n in data) {
                    if (this.hasOwnProperty(n) && n.charAt(0) != '_') {
                        result[n] = ($.isPlainObject(data[n])) ? $.extend(true, {}, data[n]) : data[n];
                    }
                }
            } else if (typeof(data) === 'string') {
                if (data.indexOf(':') !== -1) {
                    t = data.split(':', 2);
                    result['action'] = t.shift();
                    data = t.shift();
                    t = /^(?:\[([^\]]*)\])(.*)/.exec(data);
                    if ((t || false) && (t.length)) {
                        if (t[1].length) {
                            result['target'] = t[1];
                        }
                        data = t[2];
                    }
                    if (data.indexOf('#') !== -1) {
                        t = data.split('#', 2);
                        data = t.shift();
                        var st = t.shift();
                        var op = null;
                        switch (st.substr(0, 1)) {
                            case '=':
                                op = 'set';
                                break;
                            case '!':
                                op = 'reset';
                                break;
                            case '~':
                                op = 'toggle';
                                break;
                        }
                        if (op !== null) {
                            st = st.substr(1);
                        } else {
                            op = 'modify';
                        }
                        result['operation'] = op;
                        result['state'] = this._parseArgs(st);
                    }
                    if (data.indexOf('?') !== -1) {
                        t = data.split('?', 2);
                        data = t.shift();
                        result['args'] = this._parseArgs(t.shift());
                    }
                    if (data.length) {
                        switch (result['action'] || false) {
                            case 'load':
                                result['url'] = data;
                                break;
                            case 'event':
                                result['event'] = data;
                                break;
                            case 'state':
                                result['operation'] = data;
                                break;
                        }
                    }
                } else {
                    result['action'] = data;
                }
            }
            $.each(['action', 'target', 'event', 'url'], function (key, name) {
                if ((result.hasOwnProperty(name)) && (result[name] !== null) && (typeof(result[name]) != 'string')) {
                    result[name] = null;
                }
            });
            $.each(['args', 'state'], function (key, name) {
                if (result.hasOwnProperty(name)) {
                    if (typeof(result[name]) === 'string') {
                        result[name] = ClientAction.prototype._parseArgs(result[name]);
                    }
                    if (!$.isPlainObject(result[name])) {
                        result[name] = {};
                    }
                }
            });
            if (((result['action'] || false) === 'state') && ($.isEmptyObject(result['state'] || {})) && (!$.isEmptyObject(result['args'] || {}))) {
                result['state'] = result['args'];
                result['args'] = {};
            }
            return result;
        },

        /**
         * Parse given list of arguments
         *
         * @param {string|Object} args
         * @returns {*}
         */
        _parseArgs: function (args) {
            if ($.isPlainObject(args)) {
                return(args);
            }
            if (args.match(/^\{.*?\}$/)) {
                // Parse as JSON string
                args = (JSON || false) ? JSON.parse(args) : $.parseJSON(args);
            } else if (args.indexOf('=') !== -1) {
                // Parse as query string. Code based on http://jsbin.com/adali3/
                var e,
                    re = /([^&=]+)=?([^&]*)/g,
                    query = {};
                while (e = re.exec(args)) {
                    var pn = e[1];
                    var pv = e[2];
                    var ind = [], arg, t, i;
                    if (pn.indexOf('[') !== -1) {
                        t = pn.split('[');
                        pn = t.shift();
                        t = t.join('[').replace(/\]$/, '');
                        ind = t.split('][');
                    } else if (pn.indexOf('.') !== -1) {
                        ind = pn.split('.');
                        pn = ind.shift();
                    }
                    pn = this._decodeArg(pn);
                    if (t = pv.match(/^\[(.*?)\]$/)) {
                        t = t[1].split(',');
                        pv = [];
                        for (i in t) {
                            pv.push(this._decodeArg(t[i]));
                        }
                    } else {
                        pv = this._decodeArg(pv);
                    }
                    if (ind.length) {
                        arg = (typeof(query[pn]) !== 'undefined') ? query[pn] : ((ind.join('') === '') ? [] : {});
                        this._pushIndex(arg, ind, pv);
                        query[pn] = arg;
                    } else {
                        query[pn] = pv;
                    }
                }
                args = query;
            }
            return(args);
        },

        _decodeArg: function (str) {
            str = decodeURIComponent(str).replace(/\+/g, " ");
            switch (str) {
                case 'null':
                    str = null;
                    break;
                case 'true':
                    str = true;
                    break;
                case 'false':
                    str = false;
                    break;
                default:
                    if (str.match(/^[-]?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][+-]?[0-9]+)?$/)) {
                        str = parseFloat(str);
                    } else if (str.match(/^\-?[0-9]+$/)) {
                        str = parseInt(str);
                    }
                    break;
            }
            return str;
        },

        _pushIndex: function (param, indexes, value) {
            var index = this._decodeArg(indexes.shift());
            if (indexes.length == 0) {
                if (param instanceof Array) {
                    param.push(value);
                } else {
                    param[index] = value;
                }
            } else {
                if (typeof(param[index]) === 'undefined') {
                    param[index] = (indexes[0] !== '') ? {} : [];
                }
                this._pushIndex(param[index], indexes, value);
            }
        },

        /**
         * Modify client action with given information
         *
         * @param {Object|String} data
         * @param {*} [value]
         * @return void
         */
        modify: function (data, value) {
            if (typeof(data) === 'string') {
                var t = {};
                t[data] = value;
                data = t;
            }
            if ($.isPlainObject(data)) {
                for (var n in data) {
                    if (this.hasOwnProperty(n)) {
                        this[n] = data[n];
                    }
                }
            }
        },

        /**
         * Check if this client action is valid
         *
         * @return {Boolean}
         */
        isValid: function () {
            var valid = true;
            switch (this.action) {
                case 'load':
                    valid &= (typeof(this.url) === 'string');
                    break;
                case 'event':
                    valid &= (typeof(this.event) === 'string');
                    break;
                case 'state':
                    valid &= ($.isPlainObject(this.state) && !$.isEmptyObject(this.state));
                    break;
                default:
                    // Action is required
                    valid = false;
                    break;
            }
            return valid;
        },

        /**
         * Apply this client action to given jQuery elements
         *
         * @param {jQuery} elements
         * @return void
         */
        apply: function (elements) {
            $(elements).data($.ca('options', 'caDataKey'), this).addClass($.ca('options', 'classes.caApplied'));
        },

        /**
         * Run this client action
         *
         * @param {Boolean} [normalized]    TRUE if client action is already normalized (can be skipped)
         * @param {Function|jQuery.Deferred|jQuery.Callbacks} [callback]    Callbacks to call upon loading (can be skipped)
         * @param {Object} [options]        Options to use (overrides "loading" section of plugin's options)
         */
        run: function (normalized, callback, options) {
            var ca;
            if ((normalized !== true) && (normalized !== false)) {
                options = callback;
                callback = normalized;
                normalized = false;
            }
            if (!CaLoader.prototype.isCallback(callback)) {
                options = callback;
                callback = undefined;
            }
            if (normalized || false) {
                ca = this;
            } else {
                if (!this.isValid()) {
                    return;
                }
                ca = new ClientAction(this);
                var target;
                if ((ca.target) && !(ca.target instanceof jQuery)) {
                    target = $(ca.target);
                    if (!target.length) {
                        target = null;
                    }
                }
                if (!target) {
                    target = $(document);
                }
                ca.target = target;
            }
            switch (ca.action) {
                case 'load':
                    var url = ca.url;
                    var data = ca.args;
                    var transformer = $.ca('options', 'loading.urlTransformer.load');
                    if (!$.isFunction(transformer)) {
                        transformer = function (ca) {
                            var state = {};
                            state[$.ca('options', 'loading.stateParam')] = ca.state;
                            return {url: ca.url, data: $.extend(true, ca.args, state)};
                        }
                    }
                    var t = transformer(ca);
                    if ($.isPlainObject(t)) {
                        url = t.url || url;
                        data = t.data || data;
                    } else {
                        url = t;
                        data = undefined;
                    }
                    var loader = new CaLoader({'url': url, 'data': data}, callback, options, ca.target);
                    loader.run();
                    break;
                case 'event':
                    $(ca.target).trigger(ca.event, ca.args);
                    break;
                case 'state':
                    var state = $.ca('state');
                    switch (ca.operation) {
                        case 'reset':
                            state.reset();
                            break;
                        case 'set':
                            state.set(ca.state);
                            break;
                        case 'toggle':
                            for (var i in ca.state) {
                                state.toggle(i, ca.state[i]);
                            }
                            break;
                        case 'modify':
                        default:
                            ca.modify(ca.state);
                            break;
                    }
                    break;
            }
        }

    };

    /**
     * Load resource by given URL and arguments into given target
     *
     * @param {String|Object} url       Either URL to load information from or {url:"url to load",data:"data to send to server"}
     * @param {Function|jQuery.Deferred|jQuery.Callbacks} [callback]    Callbacks to call upon loading (can be skipped)
     * @param {Object} [options]        Options to use (overrides "loading" section of plugin's options) (can be skipped)
     * @param {Node|jQuery} [target]    Target element for applying loading indicator
     */
    function CaLoader(url, callback, options, target) {
        this.url = null;
        this.target = null;
        this.options = null;
        this.init(url, callback, options, target);
    }

    CaLoader.prototype = {
        /**
         * Object initialization
         *
         * @param {String|Object} url       Either URL to load information from or {url:"url to load",data:"data to send to server"}
         * @param {Function|jQuery.Deferred|jQuery.Callbacks} [callback]    Callbacks to call upon loading (can be skipped)
         * @param {Object} [options]        Options to use (overrides "loading" section of plugin's options) (can be skipped)
         * @param {Node|jQuery} [target]    Target element for applying loading indicator
         */
        init: function (url, callback, options, target) {
            if ((!this.isCallback(callback)) && (callback !== undefined)) {
                target = options;
                options = callback;
                callback = undefined;
            }
            if ((options instanceof Node) || (options instanceof jQuery)) {
                target = options;
                options = {};
            }
            options = $.extend(true, $.ca('options', 'loading'), options || {});
            if (this.isCallback(callback)) {
                options.onload = callback;
            }
            if ($.isPlainObject(url)) {
                if (url.data || false) {
                    options.ajax.data = url.data;
                }
                url = url.url || '';
            }
            target = (((target instanceof Node) && (target !== document)) || ((target instanceof jQuery) && (target[0] !== document))) ? $(target) : undefined;

            this.url = url;
            // Don't assign options directly to avoid overwriting in a case of multiple calls
            this.options = $.extend(true, {}, options);
            this.target = target;
        },

        /**
         * Run resource loading process
         *
         * @returns {*}
         */
        run: function () {
            this.loadingIndicator(true);
            return($.ajax(this.url, $.extend(true, this.options.ajax, {
                context: this,
                success: this.onload,
                error: this.onerror
            })));
        },

        onload: function (data) {
            if (($.isPlainObject(data)) && (data.responseText || false)) {
                data = data.responseText;
            }
            if (this.target) {
                // Since $.html() strips out JavaScript code - use $.replaceWith() instead
                var t = $('<div>');
                this.target.empty().append(t);
                t.replaceWith(data);
                $(this.target).trigger('ca.init');
            }
            this.loadingIndicator(false);
            var cb = this.options.onload;
            var cbThis = (this.target) ? this.target : $(document);
            cbThis.trigger('ca.loaded', data);
            switch (this.isCallback(cb)) {
                case 'function':
                    cb.call(cbThis, data);
                    break;
                case 'deferred':
                    /** @type {jQuery.Deferred} cb */
                    cb.resolveWith(cbThis, [data]);
                    break;
                case 'callbacks':
                    /** @type {jQuery.Callbacks} cb */
                    cb.fireWith(cbThis, [data]);
                    break;
            }
        },

        onerror: function (data) {
            this.loadingIndicator(false);
            var cb = this.options.onerror;
            var cbThis = (this.target) ? this.target : $(document);
            cbThis.trigger('ca.load.error', data);
            switch (this.isCallback(cb)) {
                case 'function':
                    cb.call(cbThis, data);
                    break;
                case 'deferred':
                    /** @type {jQuery.Deferred} cb */
                    cb.rejectWith(cbThis, [data]);
                    break;
                case 'callbacks':
                    /** @type {jQuery.Callbacks} cb */
                    cb.fireWith(cbThis, [data]);
                    break;
                default:
                    if (cb !== false) {
                        var msg = 'Error while loading url "' + url + '"';
                        if ($.isPlainObject(data)) {
                            if (data.responseText || false) {       // AJAX error
                                msg = 'Error while loading url "' + url + '": HTTP ' + data.status + ' ' + data.statusText;
                                $.error(msg);
                            }
                        } else if (typeof(data) === 'string') {
                            $.error(data);
                        } else {
                            $.error(msg);
                        }
                    }
                    break;
            }
            // Reject onload $.Deferred object if we get it
            if (this.isCallback(this.options.onload) === 'deferred') {
                this.options.onload.rejectWith(cbThis, [data]);
            }
        },

        /**
         * Set loading indicator into given status based on current / given options
         *
         * @param {Boolean} status      Loading indicator status
         */
        loadingIndicator: function (status) {
            var options = this.options.indicator;
            if (status || false) {
                // Enable indicator
                if (options.enabled) {
                    if (($.isPlainObject(options.indicator)) && ($.isFunction(options.indicator.start))) {
                        $.proxy(options.indicator.start, this.target);
                    } else if (this.target) {
                        if (options.loadingClass) {
                            this.target.addClass(options.loadingClass);
                        }
                        if ((options.mask) && ($.fn.mask || false)) {
                            this.target.mask((typeof(options.mask) === 'string') ? options.mask : '');
                        }
                        if ((options.activity) && ($.fn.activity || false)) {
                            this.target.activity(($.isPlainObject(options.activity)) ? options.activity : {});
                        }
                    } else if ((typeof(options.indicator) === 'string') || (options.indicator instanceof jQuery)) {
                        $(options.indicator).show();
                    }
                }
            } else {
                // Disable indicator
                if (($.isPlainObject(options.indicator)) && ($.isFunction(options.indicator.stop))) {
                    $.proxy(options.indicator.stop, this.target);
                } else if (this.target) {
                    if ((options.mask) && ($.fn.unmask || false)) {
                        this.target.unmask();
                    }
                    if ((options.activity) && ($.fn.activity || false)) {
                        this.target.activity(false);
                    }
                    if (options.loadingClass) {
                        this.target.removeClass(options.loadingClass);
                    }
                } else if ((typeof(options.indicator) === 'string') || (options.indicator instanceof jQuery)) {
                    $(options.indicator).hide();
                }
            }
        },


        /**
         * Check if given value can be used as callback for resources loading
         *
         * @param {*} callback
         * @returns {string|boolean}
         */
        isCallback: function (callback) {
            if ($.isFunction(callback)) {       // Plain function
                return('function');
            }
            if ($.isPlainObject(callback)) {
                if ($.isFunction(callback.promise)) {       // $.Deferred object
                    return('deferred');
                } else if ($.isFunction(callback.fire)) {   // $.Callbacks object
                    return('callbacks');
                }
            }
            return(false);
        }
    };

    /**
     * Plugin itself
     *
     * @constructor
     */
    function Plugin() {
        this._options = null;
        this._state = null;
        this.initialized = false;
    }

    /**
     * Plugin methods independent from context
     */
    Plugin.prototype.services = {
        /**
         * Plugin initialization
         *
         * @param {Object} [state]      Application state
         * @param {Object} [options]    Plugin options
         * @return {void}
         */
        init: function (state, options) {
            if (this.initialized) {
                return;
            }
            if (!$.isPlainObject(state)) {
                state = {};
            }
            var haveDefault = false;
            if (($.isPlainObject(state.default || false)) && ($.isPlainObject(state.current || false))) {
                var ck = CaUtils.prototype.getKeys(CaUtils.prototype.toPlain(state.current), true);
                var dk = CaUtils.prototype.getKeys(CaUtils.prototype.toPlain(state.default), true);
                var missed = false;
                for (var k in ck) {
                    if ($.inArray(k, dk) == -1) {
                        missed = true;
                        break;
                    }
                }
                if (!missed) {
                    haveDefault = true;
                }
            }
            if (haveDefault) {
                this._state = new CaState(state.id || null, state.default, state.current);
            } else {
                this._state = new CaState(state);
            }
            this._options = new CaStruct(defaults || {}, options);
            // Setup client actions handlers
            $(document)
                .on('ca.init', $.proxy(this.handlers.init, this))
                .on('click', '.' + $.ca('options', 'classes.caApplied'), $.proxy(this.handlers.action, this))
                .trigger('ca.init');
            this.initialized = true;
        },

        /**
         * Plugin options management
         *
         * @param {Object|String} [name]
         * @param {*} [value]
         * @return {*}
         */
        options: function (name, value) {
            switch (typeof(name)) {
                case 'string':  // Get/set some single value of application state
                    if (typeof(value) !== 'undefined') {
                        this._options.set(name, value);
                    } else {
                        return this._options.get(name);
                    }
                    break;
                case 'object':  // Set multiple properties
                    this._options.set(name);
                    break;
                default:
                    // Get whole state object
                    return this._options;
                    break;
            }
        },

        /**
         * Create client action object
         *
         * @param {string|ClientAction|jQuery|Node|Object} ca   Client action information
         * @return {ClientAction}
         */
        create: function (ca) {
            return new ClientAction(ca);
        },

        /**
         * Load information from given URL
         *
         * @param {String|Object} url       Either URL to load information from or {url:"url to load",data:"data to send to server"}
         * @param {Function|jQuery.Deferred|jQuery.Callbacks} [callback]    Callbacks to call upon loading (can be skipped)
         * @param {Object} [options]        Options to use (overrides "loading" section of plugin's options)
         */
        load: function (url, callback, options) {
            var loader = new CaLoader(url, callback, options);
            loader.run();
        },

        /**
         * Application state options management
         *
         * @param {Object|String} name
         * @param {*} [value]
         * @return {*}
         */
        state: function (name, value) {
            switch (typeof(name)) {
                case 'string':  // Get/set some single value of application state
                    if (typeof(value) !== 'undefined') {
                        this._state.modify(name, value);
                    } else {
                        return this._state.get(name);
                    }
                    break;
                case 'object':  // Set multiple properties
                    this._state.modify(name);
                    break;
                default:
                    // Get whole state object
                    return this._state;
                    break;
            }
        }
    };

    /**
     * Context-dependent plugin methods
     */
    Plugin.prototype.methods = {
        /**
         * Apply given client action to context elements
         *
         * @param {string|ClientAction|jQuery|Node|Object|null} ca  Client action information
         * @param {Boolean} [clone]                                 TRUE to clone given ClientAction object to avoid its indirect modifications
         *                                                          FALSE to apply it directly
         * @return {jQuery}
         */
        apply: function (ca, clone) {
            if (clone === undefined) {
                clone = true;
            }
            if (!(ca instanceof ClientAction) || (clone)) {
                ca = new ClientAction(ca);
            }
            return(this.each(function () {
                ca.apply(this);
            }));
        },

        /**
         * Get client action applied to context element
         *
         * @return {ClientAction|null}
         */
        get: function () {
            var ca = null;
            this.each(function () {
                var eca = $(this).data($.ca('options', 'caDataKey'));
                if (eca instanceof ClientAction) {
                    ca = eca;
                }
                return(ca === null);
            });
            return ca;
        },

        /**
         * Remove client action information from context elements
         *
         * @return {jQuery}
         */
        remove: function () {
            return(this.each(function () {
                var $this = $(this);
                $this.removeData($.ca('options', 'caDataKey'));
                $this.removeClass($.ca('options', 'classes.caApplied'));
            }));
        },

        /**
         * Check if client action is applied on context element
         *
         * @return {Boolean}
         */
        applied: function () {
            var applied = false;
            this.each(function () {
                var $this = $(this);
                var ca = $this.data($.ca('options', 'caDataKey'));
                if (ca instanceof ClientAction) {
                    applied = true;
                    return false;
                } else {
                    return true;
                }
            });
            return applied;
        },

        /**
         * Load information from given URL
         *
         * @param {String|Object} url       Either URL to load information from or {url:"url to load",data:"data to send to server"}
         * @param {Function|jQuery.Deferred|jQuery.Callbacks} [callback]    Callbacks to call upon loading (can be skipped)
         * @param {Object} [options]        Options to use (overrides "loading" section of plugin's options)
         */
        load: function (url, callback, options) {
            return this.each(function () {
                var loader = new CaLoader(url, callback, options, this);
                loader.run();
            });
        }

    };

    /**
     * Various event handlers
     */
    Plugin.prototype.handlers = {
        /**
         * Client actions initialization
         *
         * @param {jQuery.Event} ev
         */
        init: function (ev) {
            // Convert all elements with client actions applied through data- attributes
            // into real client action objects
            $('*[data-ca-action]', ev.target || null).each(function () {
                var ca = new ClientAction(this);
                ca.apply(this);
            });
        },

        /**
         * Client actions dispatcher
         *
         * @param {jQuery.Event} ev
         */
        action: function (ev) {
            var target = $(ev.currentTarget || ev.target);
            if (!target.ca('applied')) {
                return;
            }
            var ca = target.ca('get');
            if (!(ca instanceof ClientAction)) {
                return;
            }
            ev.stopPropagation();
            // Normalize client action
            ca = new ClientAction(ca);
            if (ca.action === 'state') {
                var baseCa = target.parents('.' + $.ca('options', 'classes.baseCa')).first();
                if (baseCa.length || false) {
                    baseCa = baseCa.ca('get');
                    if (baseCa instanceof ClientAction) {
                        baseCa = new ClientAction(baseCa);
                        baseCa.operation = ca.operation;
                        for (var i in ca.state) {
                            baseCa.state[i] = ca.state[i];
                        }
                        ca = baseCa;
                    }
                }
            }
            if ((ca.action === 'load') && (!ca.url) && (target.is('[href]'))) {
                ca.url = target.href;
            }
            var autoTarget;
            if ((!ca.target) && (target)) {
                autoTarget = target.parents('.' + $.ca('options', 'classes.autoTarget')).first();
            } else if (typeof(ca.target) === 'string') {
                autoTarget = $(ca.target);
            }
            if (autoTarget.length || false) {
                ca.target = autoTarget;
            } else if (!(ca.target instanceof jQuery)) {
                ca.target = $(document);
            }
            if (!ca.isValid()) {
                return;
            }
            ca.run(true);
        }

    };
    // Expose client action and application state objects in global scope
    // to allow use of "instanceof" and direct object creation
    window.ClientAction = ClientAction;
    window.CaState = CaState;

    var plugin = new Plugin();

    // Plugin's service methods execution
    $[pluginName] = function (method) {
        if ((plugin.services[method] || false) && (method.charAt(0) != '_')) {
            // Run explicitly called plugin's service method
            return(plugin.services[method].apply(plugin, Array.prototype.slice.call(arguments, 1)));
        } else {
            // Create client action object using given information
            return(plugin.services.create.apply(plugin, arguments));
        }
    };

    // Plugin's context-dependent methods execution
    $.fn[pluginName] = function (method) {
        if ((plugin.methods[method] || false) && (method.charAt(0) != '_')) {
            return(plugin.methods[method].apply(this, Array.prototype.slice.call(arguments, 1)));
        } else if ((typeof(method) === 'object') || (!method)) {
            return(plugin.services.init.apply(plugin, arguments));
        } else {
            return($.error('Method ' + method + ' does not exist on jQuery.' + pluginName));
        }
    };

})(jQuery, window, document, undefined);

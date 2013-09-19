/*
 *  Client actions handling
 *  MIT License
 */
;
(function ($, window, document, undefined) {
    var pluginName = 'ca';
    var defaults = {
        caDataKey: 'ca',                // Name of element's "data" entry that stores applied client action
        caAppliedClass: 'ca-applied',   // CSS class to apply to elements that have applied client actions
        autoTargetClass: 'ca-target',   // CSS class to use to mark target element for client actions that doesn't define their target explicitly
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
            urlTransformer: null,       // URL transformation function, it should accept ClientAction object as argument and return either URL string or {url:"url to load",data:"data to send to server"}
            onload: null,               // Callback function to call when loading will be completed
            onerror: null               // Callback function to call in a case of error during loading process
        }
    };

    /**
     * Application state object
     *
     * @param {Object} state    Application state
     * @constructor
     */
    function CaAppState(state) {
        if (!$.isPlainObject(state)) {
            state = {};
        }
        this.state = state;
    }

    CaAppState.prototype = {
        /**
         * Get application state value
         *
         * @param {string} [path]   Path into application state object to get value of
         * @return {*}
         */
        get: function (path) {
            if (path === undefined) {
                return this.state;
            }
            var parts = path.split('.');
            var state = this.state;
            for (var i = 0; i < parts.length; i++) {
                var name = parts[i];
                if ($.type(state[name]) !== 'undefined') {
                    if (i == (parts.length - 1)) {
                        return state[name];
                    } else {
                        if (($.isPlainObject(state[name])) || (state[name] instanceof Array)) {
                            state = state[name];
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
         * Set value of application state entry by given path
         *
         * @param {string|Object} path      Path into application state object to set value of
         * @param {*} [value]               New value of application state entry
         * @param {Boolean} [silent]        TRUE to perform silent modification (e.g. to sync app.state with external state source)
         */
        set: function (path, value, silent) {
            var modifications = path;
            if ($.type(path) == 'string') {
                modifications = {};
                modifications[path] = value;
            }
            var modified = [];
            for (var p in modifications) {
                var parts = p.split('.');
                value = modifications[p];
                var state = this.state;
                for (var i = 0; i < parts.length; i++) {
                    var name = parts[i];
                    if ($.type(state[name]) !== 'undefined') {
                        if (i == (parts.length - 1)) {
                            state[name] = value;
                            modified.push(p);
                        } else {
                            if (($.isPlainObject(state[name])) || (state[name] instanceof Array)) {
                                state = state[name];
                            } else {
                                break;
                            }
                        }
                    } else {
                        break;
                    }
                }
                if ((modified.length) && (silent || false)) {
                    $(document).trigger('ca.state.modified', modified);
                }
            }
        },

        /**
         * Toggle given value into given application state entry
         *
         * @param {string} path     Path into application state object to toggle value in
         * @param {*} [value]       Value to toggle
         */
        toggle: function (path, value) {
            var entry = this.get(path);
            if (entry instanceof Array) {
                if ($.inArray(value, entry) !== -1) {
                    entry = $.grep(entry, function (v) {
                        return(v !== value);
                    });
                } else {
                    entry.push(value);
                }
                this.set(path, entry);
            } else if ((entry === true) || (entry === false)) {
                this.set(path, !entry);
            }
        }
    };

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
                        result[n] = data[n];
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
                        result['state'] = this._parseArgs(t.shift());
                    }
                    if (data.indexOf('?') !== -1) {
                        t = data.split('?', 2);
                        data = t.shift();
                        result['args'] = this._parseArgs(t.shift());
                    }
                    if (data.length) {
                        if ((result['action'] || false) == 'event') {
                            result['event'] = data;
                        } else {
                            result['url'] = data;
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
                    decode = function (str, value) {
                        str = decodeURIComponent(str).replace(/\+/g, " ");
                        if (value) {
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
                                    if (str.match(/^\-?[0-9]*\.[0-9]+$/)) {
                                        str = parseFloat(str);
                                    } else if (str.match(/^\-?[0-9]+$/)) {
                                        str = parseInt(str);
                                    }
                                    break;
                            }
                        }
                        return str;
                    },
                    query = {};
                while (e = re.exec(args)) {
                    if (e[1].indexOf("[") == -1) {
                        query[decode(e[1])] = decode(e[2], true);
                    } else {
                        var t = e[1].indexOf("["),
                            ind = decode(e[1].slice(t + 1, e[1].indexOf("]", t))),
                            pn = decode(e[1].slice(0, t)),
                            pv = decode(e[2], true);

                        if (typeof(query[pn]) === 'undefined') {
                            query[pn] = (ind !== '') ? {} : [];
                        }
                        if (query[pn] instanceof Array) {
                            query[pn].push(pv);
                        } else {
                            query[pn][ind] = pv;
                        }
                    }
                }
                args = query;
            }
            return(args);
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
                    valid &= ($.type(this.url) === 'string');
                    break;
                case 'event':
                    valid &= ($.type(this.event) === 'string');
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
            $(elements).data($.ca('options', 'caDataKey'), this).addClass($.ca('options', 'caAppliedClass'));
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
                    var transformer = $.ca('options', 'loading.urlTransformer');
                    if ($.isFunction(transformer)) {
                        var t = transformer(ca);
                        if ($.isPlainObject(t)) {
                            url = t.url || url;
                            data = t.data || data;
                        } else {
                            url = t;
                            data = undefined;
                        }
                    }
                    var loader = new CaLoader({'url': url, 'data': data}, callback, options, ca.target);
                    loader.run();
                    break;
                case 'event':
                    $(ca.target).trigger(ca.event, ca.args);
                    break;
                case 'state':
                    $.ca('state', ca.state);
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
            }
            this.loadingIndicator(false);
            var cb = this.options.onload;
            var cbThis = (this.target) ? this.target : $(document);
            cbThis.trigger('ca.load.completed', data);
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
                        } else if ($.type(data) === 'string') {
                            $.error(data);
                        } else {
                            $.error(msg);
                        }
                    }
                    break;
            }
            // Reject onload $.Deferred object if we get it
            if (this.isCallback(this.options.onload) == 'deferred') {
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
                            this.target.mask(($.type(options.mask) == 'string') ? options.mask : '');
                        }
                        if ((options.activity) && ($.fn.activity || false)) {
                            this.target.activity(($.isPlainObject(options.activity)) ? options.activity : {});
                        }
                    } else if (($.type(options.indicator) == 'string') || (options.indicator instanceof jQuery)) {
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
                } else if (($.type(options.indicator) == 'string') || (options.indicator instanceof jQuery)) {
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
        this._options = defaults;
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
            this._state = new CaAppState(state || {});
            this._options = new CaAppState(this._options || {});
            this._options.set(options || {});
            // Setup client actions handlers
            $(document)
                .on('ca.init', $.proxy(this.handlers.init, this))
                .on('click', '.' + $.ca('options', 'caAppliedClass'), $.proxy(this.handlers.action, this))
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
            switch ($.type(name)) {
                case 'string':  // Get/set some single value of application state
                    if ($.type(value) !== 'undefined') {
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
            switch ($.type(name)) {
                case 'string':  // Get/set some single value of application state
                    if ($.type(value) !== 'undefined') {
                        this._state.set(name, value);
                    } else {
                        return this._state.get(name);
                    }
                    break;
                case 'object':  // Set multiple properties
                    this._state.set(name);
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
                $this.removeClass($.ca('options', 'caAppliedClass'));
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
            var target = $(ev.target);
            if (!target.length) {
                return;
            }
            var ca = target.ca('get');
            if (!(ca instanceof ClientAction)) {
                return;
            }
            // Normalize client action
            ca = new ClientAction(ca);
            if ((ca.action === 'load') && (!ca.url) && (target.is('[href]'))) {
                ca.url = target.href;
            }
            var autoTarget;
            if ((!ca.target) && (target)) {
                autoTarget = target.parents('.' + $.ca('options', 'autoTargetClass')).first();
            } else if ($.type(ca.target) === 'string') {
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
    window.CaAppState = CaAppState;

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

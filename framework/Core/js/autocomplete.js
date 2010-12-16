/**
 * autocomplete.js - A javascript library which implements autocomplete.
 * Requires prototype.js v1.6.0.2+, scriptaculous v1.8.0+ (effects.js),
 * and keynavlist.js.
 *
 * Adapted from script.aculo.us controls.js v1.8.0
 *   (c) 2005-2007 Thomas Fuchs, Ivan Krstic, and Jon Tirsen
 *   Contributors: Richard Livsey, Rahul Bhargava, Rob Wills
 *   http://script.aculo.us/
 *
 * The original script was freely distributable under the terms of an
 * MIT-style license.
 *
 * Usage:
 * ------
 * TODO: options = autoSelect, frequency, minChars, onSelect, onShow, onType,
 *                 paramName, tokens
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 */

var Autocompleter = {};

Autocompleter.Base = Class.create({
    baseInitialize: function(elt, opts)
    {
        this.elt = elt = $(elt);
        this.changed = false;
        this.observer = null;
        this.oldval = $F(elt);
        this.opts = Object.extend({
            frequency: 0.4,
            indicator: null,
            minChars: 1,
            onSelect: Prototype.K,
            onShow: Prototype.K,
            onType: Prototype.K,
            filterCallback: Prototype.K,
            paramName: elt.readAttribute('name'),
            tokens: [],
            keydownObserver: this.elt
        }, (this._setOptions ? this._setOptions(opts) : (opts || {})));

        // Force carriage returns as token delimiters anyway
        if (!this.opts.tokens.include('\n')) {
            this.opts.tokens.push('\n');
        }

        elt.writeAttribute('autocomplete', 'off');
        elt.observe("keydown", this._onKeyDown.bindAsEventListener(this));
    },

    _onKeyDown: function(e)
    {
        var a = document.activeElement;

        if (Object.isUndefined(a) || a == this.elt) {
            switch (e.keyCode) {
            case 0:
                if (!Prototype.Browser.WebKit) {
                    break;
                }
                // Fall-through

            // Ignore events caught by KevNavList
            case Event.KEY_DOWN:
            case Event.KEY_ESC:
            case Event.KEY_RETURN:
            case Event.KEY_TAB:
            case Event.KEY_UP:
                return;
            }

            this.changed = true;

            if (this.observer) {
                clearTimeout(this.observer);
            }

            this.observer = this.onObserverEvent.bind(this).delay(this.opts.frequency);
        }
    },

    updateChoices: function(choices)
    {
        var a = document.activeElement, c = [], re;

        if (this.changed ||
            (Object.isUndefined(a) || a != this.elt)) {
            return;
        }

        if (this.opts.indicator) {
            $(this.opts.indicator).hide();
        }

        choices = this.opts.filterCallback(choices);
        if (!choices.size()) {
            if (this.knl) {
                this.knl.hide();
            }
            this.getNewVal(this.lastentry);
        } else if (choices.size() == 1 && this.opts.autoSelect) {
            this.onSelect(choices.first());
            if (this.knl) {
                this.knl.hide();
            }
        } else {
            re = new RegExp("(" + this.getToken() + ")", "i");

            choices.each(function(n) {
                c.push({
                    l: n.escapeHTML().gsub(re, '<strong>#{1}</strong>'),
                    v: n
                });
            });

            if (!this.knl) {
                this.knl = new KeyNavList(this.elt, {
                    onChoose: this.onSelect.bind(this),
                    onShow: this.opts.onShow.bind(this),
                    domParent: this.opts.domParent,
                    keydownObserver: this.opts.keydownObserver
                });
            }

            this.knl.show(c);
        }
    },

    onObserverEvent: function()
    {
        this.changed = false;

        var entry = this.getToken();

        if (entry.length >= this.opts.minChars) {
            entry = this.opts.onType(entry);
        }

        if (entry.length) {
            if (this.opts.indicator) {
                $(this.opts.indicator).show();
            }
            this.lastentry = entry;
            this.getUpdatedChoices(entry);
        } else if (this.knl) {
            this.knl.hide();
        }
    },

    getToken: function()
    {
        var bounds = this.getTokenBounds();
        return $F(this.elt).substring(bounds[0], bounds[1]).strip();
    },

    getTokenBounds: function()
    {
        var diff, i, index, l, offset, tp,
            t = this.opts.tokens,
            value = $F(this.elt),
            nextTokenPos = value.length,
            prevTokenPos = -1,
            boundary = Math.min(nextTokenPos, this.oldval.length);

        if (value.strip().empty()) {
            return [ -1, 0 ];
        }

        diff = boundary;
        for (i = 0; i < boundary; ++i) {
            if (value[i] != this.oldval[i]) {
                diff = i;
                break;
            }
        }

        offset = (diff == this.oldval.length ? 1 : 0);

        for (index = 0, l = t.length; index < l; ++index) {
            tp = value.lastIndexOf(t[index], diff + offset - 1);
            if (tp > prevTokenPos) {
                prevTokenPos = tp;
            }
            tp = value.indexOf(t[index], diff + offset);
            if (tp != -1 && tp < nextTokenPos) {
                nextTokenPos = tp;
            }
        }
        return [ prevTokenPos + 1, nextTokenPos ];
    },

    onSelect: function(entry)
    {
        if (entry) {
            this.elt.setValue(this.opts.onSelect(this.getNewVal(entry))).focus();
            if (this.knl) {
                this.knl.markSelected();
            }
        }
    },

    getNewVal: function(entry)
    {
        var bounds = this.getTokenBounds(), newval, v, ws;

        if (bounds[0] == -1) {
            newval = entry;
        } else {
            v = $F(this.elt);
            newval = v.substr(0, bounds[0]);
            ws = v.substr(bounds[0]).match(/^\s+/);
            if (ws) {
                newval += ws[0];
            }
            newval += entry + v.substr(bounds[1]);
        }

        this.oldval = newval;

        return newval;
    }

});

Ajax.Autocompleter = Class.create(Autocompleter.Base, {

    initialize: function(element, url, opts)
    {
        this.baseInitialize(element, opts);
        this.opts = Object.extend(this.opts, {
            asynchronous: true,
            onComplete: this._onComplete.bind(this),
            defaultParams: $H(this.opts.parameters)
        });
        this.url = url;
        this.cache = $H();
    },

    getUpdatedChoices: function(t)
    {
        var p,
            o = this.opts,
            c = this.cache.get(t);

        if (c) {
            this.updateChoices(c);
        } else {
            p = Object.clone(o.defaultParams);
            p.set(o.paramName, t);
            o.parameters = p.toQueryString();
            new Ajax.Request(this.url, o);
        }
    },

    _onComplete: function(request)
    {
        this.updateChoices(this.cache.set(this.getToken(), request.responseJSON));
    }
});

Autocompleter.Local = Class.create(Autocompleter.Base, {

    initialize: function(element, arr, opts)
    {
        this.baseInitialize(element, opts);
        this.opts.arr = arr;
    },

    getUpdatedChoices: function(entry)
    {
        var choices,
            csort = [],
            entry_len = entry.length,
            i = 0,
            o = this.opts;

        if (o.ignoreCase) {
            entry = entry.toLowerCase();
        }

        choices = o.arr.findAll(function(t) {
            if (i == o.choices) {
                throw $break;
            }

            if (o.ignoreCase) {
                t = t.toLowerCase();
            }
            t = t.unescapeHTML();

            var pos = t.indexOf(entry);
            if (pos != -1 &&
                ((pos == 0 && t.length != entry_len) ||
                 (entry_len >= o.partialChars &&
                  o.partialSearch &&
                  (o.fullSearch || /\s/.test(t.substr(pos - 1, 1)))))) {
                ++i;
                return true;
            }
            return false;
        }, this);

        if (o.score) {
            choices.each(function(term) {
                csort.push({ s: LiquidMetal.score(term, entry), t: term });
            }.bind(this));
            // Sort the terms
            csort.sort(function(a, b) { return b.s - a.s; });
            choices = csort.pluck('t');
        }

        this.updateChoices(choices);
    },

    _setOptions: function(opts)
    {
        return Object.extend({
            choices: 10,
            fullSearch: false,
            ignoreCase: true,
            partialChars: 2,
            partialSearch: true,
            score: false
        }, opts || {});
    }

});

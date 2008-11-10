/**
 * autocomplete.js - A javascript library which implements autocomplete.
 * Requires prototype.js v1.6.0.2+ and scriptaculous v1.8.0+ (effects.js)
 *
 * Adapted from script.aculo.us controls.js v1.8.0
 *   (c) 2005-2007 Thomas Fuchs, Ivan Krstic, and Jon Tirsen
 *   Contributors: Richard Livsey, Rahul Bhargava, Rob Wills
 *   http://script.aculo.us/
 *
 * The original script was freely distributable under the terms of an
 * MIT-style license.
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var Autocompleter = {};
Autocompleter.Base = Class.create({
    baseInitialize: function(element, update, options)
    {
        this.element = $(element);
        this.update = $(update).hide();
        this.active = this.changed = this.hasFocus = false;
        this.entryCount = this.index = 0;
        this.observer = null;
        this.oldval = $F(this.element);

        this.options = Object.extend({
            paramName: this.element.name,
            tokens: [],
            frequency: 0.4,
            minChars: 1,
            onHide: this._onHide.bind(this),
            onShow: this._onShow.bind(this)
        }, (this._setOptions ? this._setOptions(options) : (options || {})));

        // Force carriage returns as token delimiters anyway
        if (!this.options.tokens.include('\n')) {
            this.options.tokens.push('\n');
        }

        this.element.writeAttribute('autocomplete', 'off').observe("blur", this._onBlur.bindAsEventListener(this)).observe(Prototype.Browser.Gecko ? "keypress" : "keydown", this._onKeyPress.bindAsEventListener(this));
    },

    _onShow: function(elt, update)
    {
        var c, p = update.getStyle('position');
        if (!p || p == 'absolute') {
            // Temporary fix for Bug #7074 - Fixed as of prototypejs 1.6.0.3
            c = (Prototype.Browser.IE) ? elt.cumulativeScrollOffset() : [ 0 ];
            update.setStyle({ position: 'absolute' }).clonePosition(elt, {
                setHeight: false,
                offsetTop: elt.offsetHeight,
                offsetLeft: c[0]
            });
        }
        new Effect.Appear(update, { duration: 0.15 });
    },

    _onHide: function(elt, update)
    {
        new Effect.Fade(update, { duration: 0.15 });
    },

    show: function()
    {
        if (!this.update.visible()) {
            this.options.onShow(this.element, this.update);
        }

        if (Prototype.Browser.IE &&
            !this.iefix &&
            this.update.getStyle('position') == 'absolute') {
            this.iefix = new Element('IFRAME', { src: 'javascript:false;', frameborder: 0, scrolling: 'no' }).setStyle({ position: 'absolute', filter: 'progid:DXImageTransform.Microsoft.Alpha(opactiy=0)', zIndex: 1 }).hide();
            this.update.setStyle({ zIndex: 2 }).insert({ after: this.iefix });
        }

        if (this.iefix) {
            this._fixIEOverlapping.bind(this).delay(0.05);
        }
    },

    _fixIEOverlapping: function()
    {
        this.iefix.clonePosition(this.update).show();
    },

    hide: function()
    {
        this.stopIndicator();
        if (this.update.visible()) {
            this.options.onHide(this.element, this.update);
            if (this.iefix) {
                this.iefix.hide();
            }
        }
    },

    startIndicator: function()
    {
        if (this.options.indicator) {
            $(this.options.indicator).show();
        }
    },

    stopIndicator: function()
    {
        if (this.options.indicator) {
            $(this.options.indicator).hide();
        }
    },

    _onKeyPress: function(e)
    {
        if (this.active) {
            switch (e.keyCode) {
            case Event.KEY_TAB:
            case Event.KEY_RETURN:
                this.selectEntry();
                e.stop();
                return;

            case Event.KEY_ESC:
                this.hide();
                this.active = false;
                e.stop();
                return;

            case Event.KEY_LEFT:
            case Event.KEY_RIGHT:
                return;

            case Event.KEY_UP:
            case Event.KEY_DOWN:
                if (e.keyCode == Event.KEY_UP) {
                    this.markPrevious();
                } else {
                    this.markNext();
                }
                this.render();
                e.stop();
                return;
            }
        } else {
            switch (e.keyCode) {
            case 0:
                if (!Prototype.Browser.WebKit) {
                    break;
                }
                // Fall through to below case
                //
            case Event.KEY_TAB:
            case Event.KEY_RETURN:
                return;
            }
        }

        this.changed = this.hasFocus = true;

        if (this.observer) {
            clearTimeout(this.observer);
        }
        this.observer = this.onObserverEvent.bind(this).delay(this.options.frequency);
    },

    _onHover: function(e)
    {
        var elt = e.findElement('LI'),
            index = elt.readAttribute('acIndex');
        if (this.index != index) {
            this.index = index;
            this.render();
        }
        e.stop();
    },

    _onClick: function(e)
    {
        this.index = e.findElement('LI').readAttribute('acIndex');
        this.selectEntry();
    },

    _onBlur: function(e)
    {
        // Needed to make click events work
        this.hide.bind(this).delay(0.25);
        this.active = this.hasFocus = false;
    },

    render: function()
    {
        var i = 0;

        if (this.entryCount) {
            this.update.down().childElements().each(function(e) {
                [ e ].invoke(this.index == i++ ? 'addClassName' : 'removeClassName', 'selected');
            }, this);
            if (this.hasFocus) {
                this.show();
                this.active = true;
            }
        } else {
            this.active = false;
            this.hide();
        }
    },

    markPrevious: function()
    {
        if (this.index) {
            --this.index;
        } else {
            this.index = this.entryCount - 1;
        }
        this.getEntry(this.index).scrollIntoView(true);
    },

    markNext: function()
    {
        if (this.index < this.entryCount - 1) {
            ++this.index;
        } else {
            this.index = 0;
        }
        this.getEntry(this.index).scrollIntoView(false);
    },

    getEntry: function(index)
    {
        return this.update.down().childElements()[index];
    },

    selectEntry: function()
    {
        this.active = false;
        this.updateElement(this.getEntry(this.index));
        this.hide();
    },

    updateElement: function(elt)
    {
        var bounds, newValue, nodes, whitespace, v,
            o = this.options,
            value = '';

        if (o.updateElement) {
            o.updateElement(elt);
            return;
        }

        if (o.select) {
            nodes = $(elt).select('.' + o.select) || [];
            if (nodes.size()) {
                value = nodes[0].collectTextNodes(o.select);
            }
        } else {
            value = elt.collectTextNodesIgnoreClass('informal');
        }

        bounds = this.getTokenBounds();
        if (bounds[0] != -1) {
            v = $F(this.element);
            newValue = v.substr(0, bounds[0]);
            whitespace = v.substr(bounds[0]).match(/^\s+/);
            if (whitespace) {
                newValue += whitespace[0];
            }
            this.element.setValue(newValue + value + v.substr(bounds[1]));
        } else {
            this.element.setValue(value);
        }
        this.element.focus();

        if (o.afterUpdateElement) {
            o.afterUpdateElement(this.element, elt);
        }

        this.oldval = $F(this.element);
    },

    updateChoices: function(choices)
    {
        var li, re, ul,
            i = 0;

        if (!this.changed && this.hasFocus) {
            li = new Element('LI');
            ul = new Element('UL');
            re = new RegExp("(" + this.getToken() + ")", "i");

            choices.each(function(n) {
                ul.insert(li.cloneNode(false).writeAttribute('acIndex', i++).update(n.gsub(re, '<strong>#{1}</strong>')));
            });

            this.update.update(ul);
            this.entryCount = choices.size();
            ul.childElements().each(this.addObservers.bind(this));

            this.stopIndicator();
            this.index = 0;

            if (this.entryCount == 1 && this.options.autoSelect) {
                this.selectEntry();
            } else {
                this.render();
            }
        }
    },

    addObservers: function(elt)
    {
        $(elt).observe("mouseover", this._onHover.bindAsEventListener(this)).observe("click", this._onClick.bindAsEventListener(this));
    },

    onObserverEvent: function()
    {
        this.changed = false;
        if (this.getToken().length >= this.options.minChars) {
            this.getUpdatedChoices();
        } else {
            this.active = false;
            this.hide();
        }
        this.oldval = $F(this.element);
    },

    getToken: function()
    {
        var bounds = this.getTokenBounds();
        return $F(this.element).substring(bounds[0], bounds[1]).strip();
    },

    getTokenBounds: function()
    {
        var diff, i, index, l, offset, tp,
            t = this.options.tokens,
            value = $F(this.element),
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
    }
});

Ajax.Autocompleter = Class.create(Autocompleter.Base, {
    initialize: function(element, update, url, options)
    {
        this.baseInitialize(element, update, options);
        this.options = Object.extend(this.options, {
            asynchronous: true,
            onComplete: this._onComplete.bind(this),
            defaultParams: $H(this.options.parameters)
        });
        this.url = url;
        this.cache = $H();
    },

    getUpdatedChoices: function()
    {
        var p,
            o = this.options,
            t = this.getToken(),
            c = this.cache.get(t);

        if (c) {
            this.updateChoices(c);
        } else {
            p = Object.clone(o.defaultParams);
            this.startIndicator();
            p.set(o.paramName, t);
            o.parameters = p.toQueryString();
            new Ajax.Request(this.url, o);
        }
    },

    _onComplete: function(request)
    {
        this.updateChoices(this.cache.set(this.getToken(), request.responseText.evalJSON(true)));
    }
});

Autocompleter.Local = Class.create(Autocompleter.Base, {
    initialize: function(element, update, arr, options)
    {
        this.baseInitialize(element, update, options);
        this.options.arr = arr;
    },

    getUpdatedChoices: function()
    {
        this.updateChoices(this._selector());
    },

    _setOptions: function(options)
    {
        return Object.extend({
            choices: 10,
            partialSearch: true,
            partialChars: 2,
            ignoreCase: true,
            fullSearch: false
        }, options || {});
    },

    _selector: function()
    {
        var entry = this.getToken(),
            entry_len = entry.length,
            i = 0,
            o = this.options;

        if (o.ignoreCase) {
            entry = entry.toLowerCase();
        }

        return o.arr.findAll(function(t) {
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
    }
});

/**
 * An autocompleter implementation that provides a more advanced UI
 * (completed elements are stored in separate DIV elements).
 *
 * Events handled by this class:
 *   - AutoComplete:focus
 *   - AutoComplete:reset
 *   - AutoComplete:update
 *
 * Events triggered by this class (on input element):
 *   - AutoComplete:resize
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @copyright  2008-2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */
var IMP_Autocompleter = Class.create({

    // ac,
    // acTimeout,
    // box,
    // cache,
    // data,
    // dimg,
    // elt,
    // elt_ac,
    // input,
    // itemid,
    // knl,
    // lastinput,
    // p,

    initialize: function(elt, params)
    {
        var active;

        this.cache = $H();
        this.itemid = 0;
        this.lastinput = '';
        this.p = Object.extend({
            autocompleterParams: {},
            // Outer div/fake input box and CSS class
            // box (created below)
            boxClass: 'hordeACBox',
            boxClassFocus: '',
            entryDelay: 0.4,
            // CSS class for real input field
            growingInputClass: 'hordeACTrigger',
            // input, (created below)
            // <ul> CSS class
            listClass: 'hordeACList',
            listClassItem: 'hordeACListItem',
            maxItemSize: 50,
            minChars: 3,
            onAdd: Prototype.emptyFunction,
            onBeforeServerRequest: Prototype.emptyFunction,
            onEntryClick: Prototype.emptyFunction,
            onServerSuggestion: Prototype.emptyFunction,
            processValueCallback: Prototype.emptyFunction,
            removeClass: 'hordeACItemRemove',
            requireSelection: false,
            shortDisplayCallback: Prototype.K
        }, params || {});

        // The original input element is transformed into a hidden input
        // field that holds the raw return value.
        this.elt = $(elt);
        this.elt.writeAttribute('autocomplete', 'off');

        // Create an autocomplete input element that holds the JSON encoded
        // return value.
        this.elt_ac = new Element('INPUT', {
            name: this.elt.identify() + '_ac',
            type: 'hidden'
        });
        this.elt.insert({ after: this.elt_ac });

        this.box = new Element('DIV', { className: this.p.boxClass });

        // The input element and the <li> wrapper
        this.input = new Element('INPUT', {
            autocomplete: 'off',
            className: this.p.growingInputClass
        });

        // Build the outer box
        this.box.insert(
            // The list - where the chosen items are placed as <li> nodes
            new Element('UL', { className: this.p.listClass }).insert(
                new Element('LI').insert(this.input)
            )
        );

        // Replace the single input element with the new structure and
        // move the old element into the structure while making sure it's
        // hidden.
        active = this.checkActiveElt(this.elt);
        this.box.insert(this.elt.replace(this.box).hide());
        if (active) {
            this.focus();
        }

        // Look for clicks on the box to simulate clicking in an input box
        this.box.observe('click', this.clickHandler.bindAsEventListener(this));

        // Double-clicks cause an edit on existing entries.
        this.box.observe('dblclick', this.dblclickHandler.bindAsEventListener(this));

        this.input.observe('blur', this.blur.bind(this));
        this.input.observe('keydown', this.keydownHandler.bindAsEventListener(this));

        new PeriodicalExecuter(this.inputWatcher.bind(this), 0.25);

        document.observe('AutoComplete:focus', this.triggerEvent.bindAsEventListener(this, this.focus.bind(this)));
        document.observe('AutoComplete:reset', this.triggerEvent.bindAsEventListener(this, this.reset.bind(this)));
        document.observe('AutoComplete:update', this.triggerEvent.bindAsEventListener(this, this.processInput.bind(this)));

        this.reset();
    },

    triggerEvent: function(e, func)
    {
        switch (e.element()) {
        case this.elt:
            e.stop();
            /* falls through */

        case document:
            func();
            break;
        }
    },

    checkActiveElt: function(elt)
    {
        return (document.activeElement &&
                (document.activeElement == elt));
    },

    focus: function()
    {
        this.input.focus();
        this.box.addClassName(this.p.boxClassFocus);
    },

    blur: function()
    {
        this.box.removeClassName(this.p.boxClassFocus);
    },

    reset: function()
    {
        this.data = [];
        this.currentEntries().invoke('remove');
        this.processValue($F(this.elt));
        this.processInput();
    },

    processInput: function()
    {
        var tmp = $F(this.input);

        if (!tmp.empty()) {
            this.addNewItems([ new IMP_Autocompleter_Elt(tmp) ]);
            this.updateInput('');
        }
    },

    processValue: function(val)
    {
        var tmp;

        if (!this.p.requireSelection) {
            tmp = this.p.processValueCallback(val.replace(/^\s+/, ''));
            this.addNewItems(tmp[0]);
            this.updateInput(tmp[1]);
        }
    },

    getElts: function()
    {
        return this.data.pluck('elt');
    },

    getEntryByElt: function(elt)
    {
        var itemid = elt.retrieve('itemid');

        return this.data.detect(function(v) {
            return (v.id == itemid);
        });
    },

    getEntryById: function(id)
    {
        return this.data.detect(function(v) {
            return (v.id == id);
        });
    },

    addNewItems: function(value)
    {
        value = this.filterChoices(value);

        if (!value.size()) {
            return false;
        }

        value.each(function(v) {
            v.elt = new Element('LI', {
                className: this.p.listClassItem,
                title: v.label
            });
            v.id = ++this.itemid;

            this.input.up('LI').insert({ before:
                v.elt
                    .insert((v.short_d || this.p.shortDisplayCallback(v.label)).truncate(this.p.maxItemSize).escapeHTML())
                    .insert(this.deleteImg().clone(true).show())
                    .store('itemid', v.id)
            });

            this.data.push(v);
            this.p.onAdd(v);
        }, this);

        // Add to hidden input field.
        this.updateHiddenInput();

        if (this.knl) {
            this.knl.hide();
        }

        return true;
    },

    filterChoices: function(c)
    {
        var cv = this.data.pluck('value');

        return c.findAll(function(v) {
            return !cv.include(v.value);
        });
    },

    currentEntries: function()
    {
        return this.input.up('UL').select('LI.' + this.p.listClassItem);
    },

    updateInput: function(input)
    {
        if (Object.isElement(input)) {
            this.input.setValue(
                this.getEntryById(input.retrieve('itemid')).value
            );
            this.removeEntry(input);
        } else {
            this.input.setValue(input);
        }

        this.resize();
    },

    removeEntry: function(input)
    {
        input = input.remove();
        this.data = this.data.findAll(function(v) {
            return (v.id != input.retrieve('itemid'));
        });
        this.updateHiddenInput();
    },

    // Format: value [, value [, ...]]
    // AC Format: [ [ value, ID ], [ ... ], ... ]
    updateHiddenInput: function()
    {
        var val = this.data.pluck('value').without('');

        this.elt.setValue(val.join());
        this.elt_ac.setValue(Object.toJSON(val.zip(this.data.pluck('id'))));
    },

    resize: function()
    {
        this.input.setStyle({
            width: Math.max(80, $F(this.input).length * 9) + 'px'
        });
        this.input.fire('AutoComplete:resize');
    },

    deleteImg: function()
    {
        if (!this.dimg) {
            this.dimg = new Element('IMG', {
                className: this.p.removeClass,
                src: this.p.deleteIcon
            }).hide();
            this.box.insert(this.dimg);
        }

        return this.dimg;
    },

    /* Event handlers. */

    clickHandler: function(e)
    {
        var elt = e.element(),
            li = elt.up('LI');

        if (!this.p.onEntryClick({ ac: this, elt: elt, entry: li })) {
            if (elt.hasClassName(this.p.removeClass)) {
                this.removeEntry(li);
            }
        }

        this.focus();
    },

    dblclickHandler: function(e)
    {
        var elt = e.findElement('LI');

        if (elt && elt.hasClassName(this.p.listClassItem)) {
            this.updateInput(elt);
        } else {
            this.focus();
        }
    },

    keydownHandler: function(e)
    {
        var tmp;

        switch (e.which || e.keyCode || e.charCode) {
        case Event.KEY_DELETE:
        case Event.KEY_BACKSPACE:
            if (!$F(this.input).length &&
                (tmp = this.currentEntries().last())) {
                this.updateInput(tmp);
                e.stop();
            }
            break;
        }
    },

    inputWatcher: function()
    {
        var input = $F(this.input);

        if (input != this.lastinput) {
            this.input.setValue(this.processValue(input));
            this.lastinput = $F(this.input);
            if (this.acTimeout) {
                window.clearTimeout(this.acTimeout);
            }
            this.acTimeout = this.doAutocomplete.bind(this, this.lastinput).delay(this.p.entryDelay);
            this.resize();
        }
    },

    doAutocomplete: function(t)
    {
        if (!this.checkActiveElt(this.input)) {
            return;
        }

        var c = this.cache.get(t), tmp;

        if (c) {
            this.updateAutocomplete(t, c);
        } else if (t.length >= this.p.minChars) {
            tmp = this.p.onBeforeServerRequest(t, this.cache);
            if (tmp) {
                this.cache.set(t, tmp);
                this.updateAutocomplete(t, tmp);
                return;
            }

            DimpCore.doAction(
                'autocompleteSearch',
                Object.extend(this.p.autocompleterParams, { search: t }),
                {
                    callback: function(r, ajax) {
                        this.cache.set(t, r.results);
                        if (ajax.request.parameters.search == $F(this.input)) {
                            this.updateAutocomplete(t, r.results);
                        }
                    }.bind(this)
                }
            );

            // Pre-load the delete image now.
            this.deleteImg();
        }
    },

    updateAutocomplete: function(search, r)
    {
        var re,
            c = [],
            obs = [];

        if (!this.checkActiveElt(this.input)) {
            return;
        }

        r.each(function(e) {
            var elt = new IMP_Autocompleter_Elt(e.v, e.l, e.s);
            this.p.onServerSuggestion(e, elt);
            obs.push(elt);
        }, this);

        obs = this.filterChoices(obs);
        if (!obs.size()) {
            if (this.knl) {
                this.knl.hide();
            }
            return;
        }

        if (!this.knl) {
            this.knl = new KeyNavList(this.input, {
                onChoose: function(item) {
                    if (this.addNewItems([ item ])) {
                        this.updateInput('');
                    }
                }.bind(this)
            });
        }

        re = new RegExp(search, "i");

        obs.each(function(o) {
            var l = o.label,
                l2 = '';

            (l.match(re) || []).each(function(m2) {
                var idx = l.indexOf(m2);
                l2 += l.substr(0, idx).escapeHTML() + "<strong>" + m2.escapeHTML() + "</strong>";
                l = l.substr(idx + m2.length);
            });

            if (l.length) {
                l2 += l.escapeHTML();
            }

            c.push({ l: l2, v: o });
        });

        this.knl.show(c);
    }

}),

IMP_Autocompleter_Elt = Class.create({

    // elt,
    // id,
    // label,
    // short_d,
    // value,

    initialize: function(value, label, short_d)
    {
        this.value = value;
        this.label = label || value;
        if (short_d) {
            this.short_d = short_d;
        }
    }

});

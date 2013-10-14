/**
 * An autocompleter implementation that provides a more advanced UI
 * (completed elements are stored in separate DIV elements).
 *
 * Events handled by this class:
 *   - AutoCompleter:focus
 *   - AutoCompleter:reset
 *   - AutoCompleter:submit
 *
 *
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */
var IMP_PrettyAutocompleter = Class.create({

    // box,
    // elt,
    // input,
    // lastinput,

    initialize: function(elt, params)
    {
        var ac, active, p_clone;

        this.p = Object.extend({
            // Outer div/fake input box and CSS class
            // box (created below)
            boxClass: 'hordeACBox',
            boxClassFocus: '',
            // <ul> CSS class
            listClass: 'hordeACList',
            listClassItem: 'hordeACListItem',
            // input (created below)
            // CSS class for real input field
            growingInputClass: 'hordeACTrigger impACTrigger',
            removeClass: 'hordeACItemRemove',
            // Allow for a function that filters the display value
            // This function should *always* return escaped HTML
            displayFilter: function(t) { return t.escapeHTML(); },
            filterCallback: this.filterChoices.bind(this),
            onAdd: Prototype.K,
            onRemove: Prototype.K,
            processValueCallback: this.processValueCallback.bind(this),
            requireSelection: false
        }, params || {});

        // The original input element is transformed into the hidden input
        // field that hold the text values.
        this.elt = $(elt);

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
        active = (document.activeElement && (document.activeElement == this.elt));
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

        p_clone = Object.toJSON(this.p).evalJSON();
        p_clone.onSelect = this.updateElement.bind(this);
        p_clone.paramName = this.elt.readAttribute('name');
        p_clone.tokens = [];

        ac = new Ajax.Autocompleter(this.input, this.p.uri, p_clone);
        ac.getToken = function() {
            return $F(this.input);
        }.bind(this);

        this.reset();

        document.observe('AutoComplete:focus', function(e) {
            if (e.memo == this.elt) {
                this.focus();
                e.stop();
            }
        }.bindAsEventListener(this));
        document.observe('AutoComplete:reset', this.reset.bind(this));
        document.observe('AutoComplete:submit', this.processInput.bind(this));
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
        if ($F(this.input)) {
            this.currentEntries().invoke('remove');
            this.updateInput('');
        }
        this.addNewItem(this.processValue($F(this.elt)));
    },

    processInput: function()
    {
        this.addNewItem($F(this.input));
        this.updateInput('');
    },

    processValue: function(val)
    {
        if (this.p.requireSelection) {
            return val;
        }

        return this.p.processValueCallback(this, val.replace(/^\s+/, ''));
    },

    processValueCallback: function(ob, val)
    {
        var chr, pos = 0;

        chr = val.charAt(pos);
        while (chr !== "") {
            if (ob.p.tokens.indexOf(chr) === -1) {
                ++pos;
            } else {
                if (!pos) {
                    val = val.substr(1);
                } else {
                    ob.addNewItem(val.substr(0, pos));
                    val = val.substr(pos + 2);
                    pos = 0;
                }
            }

            chr = val.charAt(pos);
        }

        return val.replace(/^\s+/, '');
    },

    // Used as the updateElement callback.
    updateElement: function(item)
    {
        if (this.addNewItem(item)) {
            this.updateInput('');
        }
    },

    /**
     * Adds a new element to the UI, ignoring duplicates.
     *
     * @return boolean True on success, false on failure/duplicate.
     */
    addNewItem: function(value)
    {
        var displayValue;

        // Don't add if it's already present.
        if (value.empty() || !this.filterChoices([ value ]).size()) {
            return false;
        }

        displayValue = this.p.displayFilter(value);

        this.input.up('LI').insert({
            before: new Element('LI', {
                        className: this.p.listClassItem,
                        title: value
                    })
                    .insert(displayValue)
                    .insert(
                        new Element('IMG', {
                            className: this.p.removeClass,
                            src: this.p.deleteIcon
                        })
                    )
                    .store('raw', value)
        });

        // Add to hidden input field.
        this.updateHiddenInput();

        this.p.onAdd(value);

        return true;
    },

    filterChoices: function(c)
    {
        var cv = this.currentValues();

        return c.select(function(v) {
            return !cv.include(v);
        });
    },

    currentEntries: function()
    {
        return this.input.up('UL').select('LI.' + this.p.listClassItem);
    },

    currentValues: function()
    {
        return this.currentEntries().collect(function(elt) {
            return elt.retrieve('raw');
        });
    },

    updateInput: function(input)
    {
        if (Object.isElement(input)) {
            input = input.remove().retrieve('raw');
            this.updateHiddenInput();
        }

        this.input.setValue(input);
        this.resize();
        this.focus();
    },

    updateHiddenInput: function()
    {
        this.elt.setValue(this.currentValues().join(', '));
    },

    resize: function()
    {
        this.input.setStyle({
            width: Math.max(80, $F(this.input).length * 9) + 'px'
        });
    },

    /* Event handlers. */

    clickHandler: function(e)
    {
        var elt = e.element();

        if (elt.hasClassName(this.p.removeClass)) {
            elt.up('LI').remove();
            this.updateHiddenInput();
        }

        this.focus();
    },

    dblclickHandler: function(e)
    {
        var elt = e.findElement('LI');

        if (elt.hasClassName(this.p.listClassItem)) {
            this.addNewItem($F(this.input));
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
            this.resize();
        }
    }

});

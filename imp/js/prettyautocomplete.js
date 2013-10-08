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

    // Vars used and defaulting to null/false:
    //   initialized, p

    initialize: function(elt, params)
    {
        this.p = Object.extend({
            // Outer div/fake input box and CSS class
            // box (created below)
            boxClass: 'hordeACBox',
            // <ul> CSS class
            listClass: 'hordeACList',
            listClassItem: 'hordeACListItem',
            // input (created below)
            // CSS class for real input field
            growingInputClass: 'hordeACTrigger',
            removeClass: 'hordeACItemRemove',
            // Allow for a function that filters the display value
            // This function should *always* return escaped HTML
            displayFilter: function(t) { return t.escapeHTML(); },
            filterCallback: this.filterChoices.bind(this),
            onAdd: Prototype.K,
            onRemove: Prototype.K,
            requireSelection: false,

            // The original input element is transformed into the hidden input
            // field that hold the text values.
            elt: $(elt)
        }, params || {});

        this.init();
    },

    /**
     * Initializes the autocompleter, builds the dom structure, registers
     * events...
     */
    init: function()
    {
        var active;

        if (this.initialized) {
            return;
        }

        this.p.box = new Element('DIV', { className: this.p.boxClass });

        // The input element and the <li> wrapper
        this.p.input = new Element('INPUT', {
            autocomplete: 'off',
            className: this.p.growingInputClass
        });

        // Build the outer box
        this.p.box.insert(
            // The list - where the chosen items are placed as <li> nodes
            new Element('UL', { className: this.p.listClass }).insert(
                new Element('LI').insert(this.p.input)
            )
        );

        // Replace the single input element with the new structure and
        // move the old element into the structure while making sure it's
        // hidden.
        active = (document.activeElement && (document.activeElement == this.p.elt));
        this.p.box.insert(this.p.elt.replace(this.p.box).hide());
        if (active) {
            this.focus();
        }

        // Look for clicks on the box to simulate clicking in an input box
        this.p.box.observe('click', this.clickHandler.bindAsEventListener(this));

        // Double-clicks cause an edit on existing entries.
        this.p.box.observe('dblclick', this.dblclickHandler.bindAsEventListener(this));

        this.p.input.observe('keydown', this.keydownHandler.bindAsEventListener(this));

        this.p.onSelect = this.updateElement.bind(this);
        this.p.paramName = this.p.elt.readAttribute('name');

        new Ajax.Autocompleter(this.p.input, this.p.uri, this.p);

        this.processValue($F(this.p.elt));

        document.observe('AutoComplete:focus', function(e) {
            if (e.memo == this.p.elt) {
                this.focus();
                e.stop();
            }
        }.bindAsEventListener(this));
        document.observe('AutoComplete:reset', this.reset.bind(this));
        document.observe('AutoComplete:submit', this.processInput.bind(this));

        this.initialized = true;
    },

    focus: function()
    {
        this.p.input.focus();
    },

    reset: function()
    {
        this.currentEntries().invoke('remove');
        this.updateInput('');
        this.processValue($F(this.p.elt));
    },

    processInput: function()
    {
        this.processValue($F(this.p.input));
        this.updateInput('');
    },

    processValue: function(value)
    {
        value.split(',').invoke('strip').each(function(a) {
            if (!a.empty() && this.addNewItem(a)) {
                this.p.onAdd(a);
            }
        }, this);
    },

    // Used as the updateElement callback.
    updateElement: function(item)
    {
        if (this.addNewItem(item)) {
            this.p.onAdd(item);
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
        if (!this.filterChoices([ value ]).size()) {
            return false;
        }

        displayValue = this.p.displayFilter(value);

        this.p.input.up('LI').insert({
            before: new Element('LI', { className: this.p.listClassItem })
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
        return this.p.input.up('UL').select('LI.' + this.p.listClassItem);
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

        this.p.input.setValue(input);
        this.resize();
    },

    updateHiddenInput: function()
    {
        this.p.elt.setValue(this.currentValues().join(', '));
    },

    resize: function()
    {
        this.p.input.setStyle({
            width: Math.max(80, $F(this.p.input).length * 9) + 'px'
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
            this.updateInput(elt);
        }

        this.focus();
    },

    keydownHandler: function(e)
    {
        var tmp;

        switch (e.keyCode || e.charCode) {
        case 188:
            // Comma
            if (!this.p.requireSelection) {
                this.processInput();
            }
            e.stop();
            return;

        case Event.KEY_DELETE:
        case Event.KEY_BACKSPACE:
            if (!$F(this.p.input).length &&
                (tmp = this.currentEntries().last())) {
                this.updateInput(tmp);
                e.stop();
                return;
            }
            break;
        }

        this.resize();
    }

});

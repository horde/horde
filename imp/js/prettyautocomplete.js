/**
 * An autocompleter implementation that provides a more advanced UI
 * (completed elements are stored in separate DIV elements).
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
    //   ignore, initialized, p

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
        if (this.initialized) {
            return;
        }

        // Build the DOM structure
        this.buildStructure();

        // Look for clicks on the box to simulate clicking in an input box
        this.p.box.observe('click', this.clickHandler.bindAsEventListener(this));

        this.p.input.observe('blur', function() {
            if (!this.ignore) {
                this.processValue($F(this.p.input));
                this.p.input.setValue('');
            }
        }.bind(this));
        this.p.input.observe('keydown', this.keydownHandler.bindAsEventListener(this));

        this.p.onShow = function() { this.ignore = true; }.bind(this);
        this.p.onHide = function() { this.ignore = false; }.bind(this);
        this.p.onSelect = this.updateElement.bind(this);
        this.p.paramName = this.p.elt.readAttribute('name');

        new Ajax.Autocompleter(this.p.input, this.p.uri, this.p);

        this.processValue($F(this.p.elt));

        document.observe('AutoComplete:reset', this.reset.bind(this));

        this.initialized = true;
    },

    reset: function()
    {
        this.currentEntries().invoke('remove');
        this.p.input.setValue('');
        this.processValue($F(this.p.elt));
    },

    buildStructure: function()
    {
        this.p.box = new Element('DIV', { className: this.p.boxClass });

        // The input element and the <li> wrapper
        this.p.input = new Element('INPUT', {
            autocomplete: 'off',
            className: this.p.growingInputClass
        }).setValue($F(this.p.elt));

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
        this.p.box.insert(this.p.elt.replace(this.p.box).hide());
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
            this.p.input.setValue('');
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

    updateHiddenInput: function()
    {
        this.p.elt.setValue(this.currentValues().join(', '));
    },

    /* Event handlers. */

    clickHandler: function(e)
    {
        var elt = e.element();

        if (elt.hasClassName(this.p.removeClass)) {
            elt.up('LI').remove();
            this.updateHiddenInput();
        } else {
            this.p.input.focus();
        }
    },

    keydownHandler: function(e)
    {
        // Check for a comma
        if (e.keyCode == 188) {
            if (!this.p.requireSelection) {
                this.processValue($F(this.p.input));
                this.p.input.setValue('');
            }
            e.stop();
        } else {
            this.p.input.setStyle({
                width: Math.max(80, $F(this.p.input).length * 9) + 'px'
            });
        }
    }

});

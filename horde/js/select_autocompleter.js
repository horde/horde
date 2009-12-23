/**
 * Provide a way to make an editable combo box.
 * Requires prototypejs and liquidmetal.js.
 *
 * ---
 *
 * Original code from select-autocompleter.js,
 * http://github.com/kneath/select-autocompleter/
 *
 * Copyright (c) 2008 Kyle Neath
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * ---
 *
 * To activate the control, call `new SelectAutocompleter(element, options)`
 * on any `<select>` tag you would like to replace.  Your server will receive
 * the same response as if the `<select>` was not replaced, so no backend
 * work is needed.
 *
 * Any class names on the `<select>` element will be carried over to the
 * `<input>` that replaces it as well as the `<ul>` containing the results.
 *
 * Options:
 * ========
 *   cutoffScore: (float) A decimal between 0 and 1 determining what
 *                Quicksilver score to cut off results for.
 *                Use higher values to show more relevant, but less results.
 *                Default is 0.1.
 *
 *   template: (string) A string describing the template for the drop down
 *                      list item. Default variables available: rawText,
 *                      highlightedText.
 *                      Use in conjunction with templateAttributes to build
 *                      rich autocomplete lists.
 *                      Default value is "{highlightedText}"
 *
 *   templateAttributes: (array) Attributes on the `<option>` element
 *                       SelectAutocompleter should use for its template.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @pacakage Horde
 */
var SelectAutocompleter = Class.create({

    // Variables defaulting to null: dropDown, select, element

    options: {
        cutoffScore: 0.1,
        template: "{highlightedText}",
        templateAttributes: []
    },
    // JSON object containing key/value for autocompleter
    data: {},
    // Contains all the searchable terms (strings)
    terms: [],
    // Contains the current filtered terms from the quicksilver search
    filteredTerms: [],

    initialize: function(select, options)
    {
        this.options = $H(this.options).merge(options);
        this.select = $(select).hide();

        // Setup the autocompleter element
        this.element = new Element('INPUT', { className: 'textfield ' + this.select.className });
        this.dropDown = new Element('UL', { className: 'auto-dropdown ' + this.select.className }).hide();

        this.element.observe('focus', this.onFocus.bind(this));
        this.element.observe('blur', function(){ this.onBlur.delay(100, this); }.bind(this));
        this.element.observe('keyup', this.keyListener.bindAsEventListener(this));

        this.select.insert({ after: new Element('DIV', { className: 'autocomplete' }).insert(this.element).insert(this.dropDown) });

        // Gather the data from the select tag
        this.select.select('OPTION').each(function(o) {
            var dataItem = $H(),
                text = o.innerHTML.strip();
            this.options.get('templateAttributes').each(function(attr){
                dataItem.set(attr, o.readAttribute(attr));
            });
            this.data[text] = dataItem.merge({ value: option.value });
            this.terms.push(text);
        }.bind(this));

        // Prepopulate the select tag's selected option
        this.element.value = $(this.select.options[this.select.selectedIndex]).innerHTML.strip();
    },

    onFocus: function()
    {
        this.element.setValue('');
        this.dropDown.show();
        this.updateTermsList();
    },

    onBlur: function()
    {
        this.dropDown.hide();
        if (this.termChosen != null){
            this.element.setValue(this.termChosen);
            this.select.setValue(this.data[this.termChosen].get('value'));
        } else {
            this.element.setValue = $(this.select.options[this.select.selectedIndex]).innerHTML.strip();
        }
    },

    keyListener: function(e)
    {
        var choices;

        switch (e.keyCode) {
        case Event.KEY_ESC:
            // Escape means we want out!
            this.onBlur();
            this.element.blur();
            break;

        case Event.KEY_UP:
        case Event.KEY_DOWN:
            // Up/Down arrows to navigate the list
            choices = this.dropDown.select('LI');
            if (choices.size() != 0) {
                // If there's no previous choice, or the current choice has
                // been filtered out.
                if (this.highlightedChoice == null ||
                    choices.indexOf(this.highlightedChoice) == -1) {
                    this.highlight(choices[0]);
                } else {
                    switch (e.keyCode) {
                    case Event.KEY_UP:
                        // Are we at the top of the list already?
                        if (choices.indexOf(this.highlightedChoice) == 0) {
                            this.highlight(choices[0]);
                        } else {
                            // Otherwise, move down one choice
                            this.highlight(choices[choices.indexOf(this.highlightedChoice) - 1]);
                        }
                        break;

                    case Event.KEY_DOWN:
                        // Are we at the bottom of the list already?
                        if (choices.indexOf(this.highlightedChoice) == choices.length - 1) {
                            this.highlight(choices[choices.length - 1]);
                        } else {
                            // Otherwise, move up one choice
                            this.highlight(choices[choices.indexOf(this.highlightedChoice) + 1]);
                        }
                        break;
                    }
                }
            }
            break;

        case Event.KEY_RETURN:
        case Event.KEY_ENTER:
            // Select an item through the keyboard
            e.stop(); // to prevent the form from being submitted
            this.termChosen = this.highlightedChoice.readAttribute('rawText');
            this.onBlur();
            this.element.blur();

        default:
            // Regular keys (filtering for something)
            this.updateTermsList();
        }
    },

    highlight: function(elt)
    {
        if (this.highlightedChoice) {
            this.highlightedChoice.removeClassName('highlighted')
        };
        this.highlightedChoice = elt.addClassName('highlighted');
    },

    updateTermsList: function()
    {
        var i, letter,
            filterValue = this.element.value,
            letters = [];

        this.buildFilteredTerms(filterValue);
        this.dropDown.update('');

        for (i = 0; i < filterValue.length; ++i) {
            letter = filterValue.substr(i, 1);
            if (letters.indexOf(letter) == -1) {
                letters.push(letter);
            }
        }

        this.filteredTerms.each(function(scoredTerm){
            var choice, re, template,
                formattedString = scoredTerm[1],
                reString = "";

            // Build the regular expression for highlighting matching terms
            letters.each(function(l) {
                reString += l;
            });

            // Build a formatted string highlighting matches with <strong>
            if (filterValue.length > 0){
                re = new RegExp("([" + reString + "])", "ig");
                formattedString = formattedString.replace(re, "<strong>$1</strong>");
            }

            // Build the template
            template = {
                highlightedText: formattedString,
                rawText: scoredTerm[1]
            };
            this.options.get('templateAttributes').each(function(attr){
                template["attr" + attr.capitalize()] = this.data[template.rawText].get(attr);
            }.bind(this));

            // Build the output element for the dropDown
            choice = new Element('LI', { rawText: scoredTerm[1] }).insert(this.options.get('template').interpolate(template));
            choice.observe('click', function() {
                this.termChosen = scoredTerm[1];
                this.onBlur();
            }.bind(this));
            choice.observe('mouseover', this.highlight.bind(this, choice));

            this.dropDown.insert(choice);
        }.bind(this));
    },

    buildFilteredTerms: function(filter)
    {
        this.filteredTerms = [];

        this.terms.each(function(term) {
            var score = LiquidMetal.score(term, filter);
            if (score >= this.options.get('cutoffScore')) {
                this.filteredTerms.push([ score, term ]);
            }
        }.bind(this));

        // Sort the terms
        this.filteredTerms.sort(function(a, b) { return b[0] - a[0]; });
    }
});

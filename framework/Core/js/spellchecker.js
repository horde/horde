/**
 * This spell checker was inspired by work done by Garrison Locke, but
 * was rewritten almost completely by Chuck Hagenbuch to use
 * Prototype/Scriptaculous.
 *
 * Requires: prototype.js (v1.6.1+), KeyNavList.js
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * Custom Events:
 * --------------
 * Custom events are triggered on the target element.
 *
 * 'SpellChecker:after'
 *    Fired when the spellcheck processing ends.
 *
 * 'SpellChecker:before'
 *    Fired before the spellcheck is performed.
 *
 * 'SpellChecker:noerror'
 *    Fired when no spellcheck errors are found.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 */

var SpellChecker = Class.create({

    // Vars used and defaulting to null:
    //   bad, choices, disabled, htmlAreaParent, lc, locale, reviewDiv,
    //   statusButton, statusClass, suggestions, target, url
    options: {},
    resumeOnDblClick: true,
    state: 'CheckSpelling',

    // Options:
    //   bs = (array) Button states
    //   locales = (array) List of locales. See KeyNavList for structure.
    //   sc = (string) Status class
    //   statusButton = (string/element) DOM ID or element of the status
    //                  button
    //   target = (string|Element) DOM element containing data
    //   url = (string) URL of specllchecker handler
    initialize: function(opts)
    {
        var d, lc, tmp, ul;

        this.url = opts.url;
        this.target = $(opts.target);
        this.statusButton = $(opts.statusButton);
        this.buttonStates = opts.bs;
        this.statusClass = opts.sc || '';
        this.disabled = false;

        this.options.onComplete = this.onComplete.bind(this);

        document.observe('click', this.onClick.bindAsEventListener(this));

        if (opts.locales) {
            this.lc = new KeyNavList(this.statusButton, {
                list: opts.locales,
                onChoose: this.setLocale.bindAsEventListener(this)
            });

            this.statusButton.insert({ after: new Element('SPAN', { className: 'spellcheckPopdownImg' }) });
        }

        this.setStatus('CheckSpelling');
    },

    setLocale: function(locale)
    {
        this.locale = locale;
    },

    targetValue: function()
    {
        return Object.isUndefined(this.target.value)
            ? this.target.innerHTML
            : this.target.value;
    },

    spellCheck: function()
    {
        this.target.fire('SpellChecker:before');

        var opts = Object.clone(this.options),
            p = $H(),
            url = this.url;

        this.setStatus('Checking');

        p.set(this.target.identify(), this.targetValue());
        opts.parameters = p.toQueryString();

        if (this.locale) {
            url += '/locale=' + this.locale;
        }
        if (this.htmlAreaParent) {
            url += '/html=1';
        }

        new Ajax.Request(url, opts);
    },

    onComplete: function(request)
    {
        var bad, content, washidden,
            i = 0,
            result = request.responseJSON;

        if (Object.isUndefined(result)) {
            this.setStatus('Error');
            return;
        }

        this.suggestions = result.suggestions || [];

        if (!this.suggestions.size()) {
            this.setStatus('CheckSpelling');
            this.target.fire('SpellChecker:noerror');
            return;
        }

        bad = result.bad || [];

        content = this.targetValue();
        content = this.htmlAreaParent
            ? content.replace(/\r?\n/g, '')
            : content.replace(/\r?\n/g, '~~~').escapeHTML();

        $A(bad).each(function(node) {
            var re_text = '<span index="' + (i++) + '" class="spellcheckIncorrect">' + node + '</span>';
            content = content.replace(new RegExp("(?:^|\\b)" + RegExp.escape(node) + "(?:\\b|$)", 'g'), re_text);

            // Go through and see if we matched anything inside a tag (i.e.
            // class/spellcheckIncorrect is often matched if using a
            // non-English lang).
            content = content.replace(new RegExp("(<[^>]*)" + RegExp.escape(re_text) + "([^>]*>)", 'g'), '\$1' + node + '\$2');
        }, this);

        if (!this.reviewDiv) {
            this.reviewDiv = new Element('DIV', { className: this.target.readAttribute('class') }).addClassName('spellcheck').setStyle({ overflow: 'auto' });
            if (this.resumeOnDblClick) {
                this.reviewDiv.observe('dblclick', this.resume.bind(this));
            }
        }

        if (!this.target.visible()) {
            this.target.show();
            washidden = true;
        }
        this.reviewDiv.setStyle({ width: this.target.clientWidth + 'px', height: this.target.clientHeight + 'px'});
        if (washidden) {
            this.target.hide();
        }

        if (!this.htmlAreaParent) {
            content = content.replace(/~~~/g, '<br />');
        }
        this.reviewDiv.update(content);

        if (this.htmlAreaParent) {
            $(this.htmlAreaParent).insert({ bottom: this.reviewDiv });
        } else {
            this.target.hide().insert({ before: this.reviewDiv });
        }

        this.setStatus('ResumeEdit');
    },

    onClick: function(e)
    {
        var data = [], index, elt = e.element();

        if (this.disabled) {
            return;
        }

        if (elt == this.statusButton) {
            switch (this.state) {
            case 'CheckSpelling':
                this.spellCheck();
                break;

            case 'ResumeEdit':
                this.resume();
                break;
            }

            e.stop();
        } else if (elt.hasClassName('spellcheckPopdownImg')) {
            this.lc.show();
            this.lc.ignoreClick(e);
            e.stop();
        } else if (elt.hasClassName('spellcheckIncorrect')) {
            index = e.element().readAttribute('index');

            $A(this.suggestions[index]).each(function(node) {
                data.push({ l: node, v: node });
            });

            if (this.choices) {
                this.choices.updateBase(elt);
                this.choices.opts.onChoose = function(val) {elt.update(val).writeAttribute({ className: 'spellcheckCorrected' });};
            } else {
                this.choices = new KeyNavList(elt, {
                    esc: true,
                    onChoose: function(val) {
                        elt.update(val).writeAttribute({ className: 'spellcheckCorrected' });
                    }
                });
            }

            this.choices.show(data);
            this.choices.ignoreClick(e);
            e.stop();
        }
    },

    resume: function()
    {
        if (!this.reviewDiv) {
            return;
        }

        var t;

        this.reviewDiv.select('span.spellcheckIncorrect').each(function(n) {
            n.replace(n.innerHTML);
        });

        t = this.reviewDiv.innerHTML;
        if (!this.htmlAreaParent) {
            t = t.replace(/<br *\/?>/gi, '~~~').unescapeHTML().replace(/~~~/g, "\n");
        }
        this.target.setValue(t);
        this.target.enable();

        if (this.resumeOnDblClick) {
            this.reviewDiv.stopObserving('dblclick');
        }
        this.reviewDiv.remove();
        this.reviewDiv = null;

        this.setStatus('CheckSpelling');

        if (!this.htmlAreaParent) {
            this.target.show();
        }

        this.target.fire('SpellChecker:after');
    },

    setStatus: function(state)
    {
        if (!this.statusButton) {
            return;
        }

        this.state = state;
        switch (this.statusButton.tagName) {
        case 'INPUT':
            this.statusButton.setValue(this.buttonStates[state]);
            break;

        case 'A':
            this.statusButton.update(this.buttonStates[state]);
            break;
        }
        this.statusButton.className = this.statusClass + ' spellcheck' + state;
    },

    isActive: function()
    {
        return this.reviewDiv;
    },

    disable: function(disable)
    {
        this.disabled = disable;
    }

});

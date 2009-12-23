/**
 * This spell checker was inspired by work done by Garrison Locke, but
 * was rewritten almost completely by Chuck Hagenbuch to use
 * Prototype/Scriptaculous.
 *
 * Requires: prototype.js (v1.6.1+), KeyNavList.js
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var SpellChecker = Class.create({

    // Vars used and defaulting to null:
    //   bad, choices, disabled, htmlAreaParent, lc, locale, onAfterSpellCheck,
    //   onBeforeSpellCheck, onNoError, reviewDiv, statusButton, statusClass,
    //   suggestions, target, url
    options: {},
    resumeOnDblClick: true,
    state: 'CheckSpelling',

    // url = (string) URL of specllchecker handler
    // target = (string) DOM ID of message data
    // statusButton = (string/element) DOM ID or element of the status button
    // bs = (array) Button states
    // locales = (array) List of locales. See KeyNavList for structure.
    // sc = (string) Status class
    initialize: function(url, target, statusButton, bs, locales, sc)
    {
        var d, lc, tmp, ul;

        this.url = url;
        this.target = target;
        this.statusButton = $(statusButton);
        this.buttonStates = bs;
        this.statusClass = sc || '';
        this.disabled = false;

        this.options.onComplete = this.onComplete.bind(this);

        document.observe('click', this.onClick.bindAsEventListener(this));

        if (locales) {
            this.lc = new KeyNavList(this.statusButton, {
                list: locales,
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
        var input = $(this.target);
        return (Object.isUndefined(input.value)) ? input.innerHTML : input.value;
    },

    // noerror - (function) A callback function to run if no errors are
    //           identified. If not specified, will remain in spell check
    //           mode even if no errors are present.
    spellCheck: function(noerror)
    {
        if (this.onBeforeSpellCheck) {
            this.onBeforeSpellCheck();
        }

        var opts = Object.clone(this.options),
            p = $H(),
            url = this.url;

        this.setStatus('Checking');

        this.onNoError = noerror;

        p.set(this.target, this.targetValue());
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
        var bad, content, d,
            i = 0,
            input = $(this.target),
            result = request.responseJSON;

        if (Object.isUndefined(result)) {
            this.setStatus('Error');
            return;
        }

        this.suggestions = result.suggestions || [];

        if (this.onNoError && !this.suggestions.size()) {
            this.setStatus('CheckSpelling');
            this.onNoError();
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
            this.reviewDiv = new Element('div', { className: input.readAttribute('className') }).addClassName('spellcheck').setStyle({ overflow: 'auto' });
            if (this.resumeOnDblClick) {
                this.reviewDiv.observe('dblclick', this.resume.bind(this));
            }
        }

        d = input.getDimensions();
        this.reviewDiv.setStyle({ width: d.width + 'px', height: d.height + 'px'});

        if (!this.htmlAreaParent) {
            content = content.replace(/~~~/g, '<br />');
        }
        this.reviewDiv.update(content);

        if (this.htmlAreaParent) {
            $(this.htmlAreaParent).insert({ bottom: this.reviewDiv });
        } else {
            input.hide().insert({ before: this.reviewDiv });
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

        var input = $(this.target),
            t;

        this.reviewDiv.select('span.spellcheckIncorrect').each(function(n) {
            n.replace(n.innerHTML);
        });

        t = this.reviewDiv.innerHTML;
        if (!this.htmlAreaParent) {
            t = t.replace(/<br *\/?>/gi, '~~~').unescapeHTML().replace(/~~~/g, "\n");
        }
        input.value = t;
        input.disabled = false;

        if (this.resumeOnDblClick) {
            this.reviewDiv.stopObserving('dblclick');
        }
        this.reviewDiv.remove();
        this.reviewDiv = null;

        this.setStatus('CheckSpelling');

        if (!this.htmlAreaParent) {
            input.show();
        }

        if (this.onAfterSpellCheck) {
            this.onAfterSpellCheck();
        }
    },

    setStatus: function(state)
    {
        if (!this.statusButton) {
            return;
        }

        this.state = state;
        switch (this.statusButton.tagName) {
        case 'INPUT':
            this.statusButton.value = this.buttonStates[state];
            break;

        case 'A':
            this.statusButton.update(this.buttonStates[state]);
            break;
        }
        this.statusButton.className = this.statusClass + ' spellcheck' + state;
    },

    isActive: function()
    {
        return (this.reviewDiv);
    },

    disable: function(disable)
    {
        this.disabled = disable;
    }

});

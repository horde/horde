/**
 * form_ghost.js - Provide text ghosting for text inputs
 * Requires prototype.js 1.7.0+
 *
 * Usage:
 * ------
 * new FormGhost(element, {
 *     // Ghosted class name
 *     css: 'formGhost'
 * });
 *
 * Ghosted text is copied from the element's 'title' attribute.
 *
 *
 * Events fired for ghosted elements:
 * ----------------------------------
 * 'FormGhost:ghost'
 * 'FormGhost:unghost'
 * 'FormGhost:reset'
 * 'FormGhost:submit'
 *
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Horde
 */

var FormGhost = Class.create({

    // Properties: elt, hasinput, isghost, opts

    initialize: function(elt, opts)
    {
        this.elt = $(elt);
        this.opts = Object.extend({
            css: 'formGhost'
        }, opts || {});

        this.elt.observe('keydown', this.keydownHandler.bindAsEventListener(this));
        this.elt.observe('focus', this.unghost.bind(this));
        this.elt.observe('blur', this.ghost.bind(this));
        this.elt.up('FORM').observe('submit', this.submit.bind(this));

        this.ghost();
    },

    ghost: function()
    {
        if (!this.isghost) {
            if ($F(this.elt).empty()) {
                this.elt.addClassName(this.opts.css).setValue(this.elt.readAttribute('title'));
                this.hasinput = false;
            } else {
                this.hasinput = true;
            }
            this.elt.fire('FormGhost:ghost');
            this.isghost = true;
        }
    },

    unghost: function()
    {
        if (this.isghost) {
            this.elt.removeClassName(this.opts.css);
            if (!this.hasinput) {
                this.elt.setValue('');
            }
            this.elt.fire('FormGhost:unghost');
            this.isghost = false;
        }
    },

    refresh: function()
    {
        if (!this.hasinput && this.isghost) {
            this.elt.setValue(this.elt.readAttribute('title'));
        }
    },

    reset: function()
    {
        this.elt.clear();
        this.isghost = false;
        this.ghost();
    },

    submit: function(e)
    {
        var action = this.elt.up('FORM').readAttribute('action');

        this.unghost();
        this.elt.fire('FormGhost:submit');
        if (action == '' || action == '#') {
            e.stop();
        }
    },

    /* Keydown event handler */
    keydownHandler: function(e)
    {
        var action,
            elt = e.element(),
            kc = e.keyCode || e.charCode,
            form = e.findElement('FORM');

        if (form && elt == this.elt) {
            switch (kc) {
            case Event.KEY_ESC:
            case Event.KEY_TAB:
                // Catch escapes.
                if (kc == Event.KEY_ESC || !elt.getValue()) {
                    this.elt.fire('FormGhost:reset');
                    e.stop();
                }
                elt.blur();
                break;

            case Event.KEY_RETURN:
                //this.unghost();
                this.elt.fire('FormGhost:submit');
                action = form.readAttribute('action');
                if (action == '' || action == '#') {
                    e.stop();
                }
                break;
            }
        }
    }
});

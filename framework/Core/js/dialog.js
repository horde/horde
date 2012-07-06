/**
 * Javascript API used to display a RedBox dialog in Horde.
 *
 * Usage:
 * ------
 * HordeDialog.display({
 *     // Cancel text
 *     cancel_text: 'Cancel',
 *     // Any DOM or HTML to be added to the form instead of the default INPUT
 *     // element
 *     form: '',
 *     // The ID for the form
 *     form_id: 'RB_confirm',
 *     // Additional FORM attributes
 *     form_opts: {},
 *     // Hidden parameters
 *     hidden: {},
 *     // Default value for the INPUT element
 *     input_val: '',
 *     // Don't insert the default INPUT element
 *     noinput: false,
 *     // OK text.
 *     ok_text: 'OK',
 *     password: '',
 *     // The header to display at top of dialog box
 *     header: '',
 *     // The text to display at top of dialog box
 *     text: ''
 * });
 *
 *
 * Events triggered:
 * -----------------
 * HordeDialog:close
 *   params: NONE
 *
 * HordeDialog:onClick
 *   params: Event object
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

var HordeDialog = {

    // Set by calling code: cancel_text, ok_text

    display: function(data)
    {
        if (Object.isString(data)) {
            data = decodeURIComponent(data).evalJSON(true);
        }

        data.form_opts = Object.extend({
            action: '#',
            id: data.form_id || 'RB_confirm'
        }, data.form_opts || {});

        var n = new Element('FORM', data.form_opts);
        if (data.header) {
            n.insert(new Element('H2').insert(data.header));
        }
        if (data.text) {
            n.insert(new Element('P').insert(data.text));
        }

        delete RedBox.onDisplay;

        n.addClassName('RB_confirm');
        if (data.form) {
            n.insert(data.form);
        } else if (!data.noinput) {
            n.insert(new Element('INPUT', { id: 'dialog_input', name: 'dialog_input', type: data.password ? 'password' : 'text', size: 15 }).setValue(data.input_val));
            RedBox.onDisplay = Form.focusFirstElement.curry(n);
        }

        if (data.hidden) {
            $H(data.hidden).each(function(pair) {
                n.insert(new Element('INPUT', {
                    name: pair.key,
                    type: 'hidden'
                }).setValue(pair.value));
            });
        }

        n.insert(
            new Element('INPUT', { type: 'button', className: 'button', value: data.ok_text || this.ok_text }).observe('click', this._onClick.bindAsEventListener(this))
        )
        n.insert(
            new Element('INPUT', { type: 'button', className: 'button', value: data.cancel_text || this.cancel_text }).observe('click', this.close.bind(this))
        )

        n.observe('keydown', function(e) {
            switch (e.keyCode || e.charCode) {
            case Event.KEY_RETURN:
                this._onClick(e);
                e.stop();
                break;

            case Event.KEY_ESC:
                this.close(e);
                e.stop();
                break;
            }
        }.bindAsEventListener(this));

        RedBox.overlay = true;
        RedBox.showHtml(n);
    },

    close: function()
    {
        var c = RedBox.getWindowContents();
        c.fire('HordeDialog:close');
        c.remove();
        RedBox.close();
    },

    _onClick: function(e)
    {
        RedBox.getWindowContents().fire('HordeDialog:onClick', e);
    }

};

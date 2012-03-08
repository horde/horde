/**
 * Javascript API used to display a RedBox dialog in IMP.
 *
 * Usage:
 * ------
 * IMPDialog.display({
 *     // [REQUIRED] Cancel text
 *     cancel_text: '',
 *     form: '',
 *     // The ID for the form
 *     form_id: 'RB_confirm',
 *     form_opts: {},
 *     input_val: '',
 *     noinput: false,
 *     // OK text.
 *     ok_text: '',
 *     password: '',
 *     reloadurl: '',
 *     submit_handler: false,
 *     // [REQUIRED] The text to display at top of dialog box
 *     text: '',
 *
 *     // If these are set, an AJAX action (to 'uri') will be initiated if the
 *     // OK button is pressed:
 *     params: {},
 *     type: '',
 *     uri: ''
 * });
 *
 *
 * Events triggered:
 * -----------------
 * IMPDialog:close
 *   params: NONE
 *
 * IMPDialog:onClick
 *   params: Event object
 *
 * IMPDialog:success
 *   params: type parameter
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

var IMPDialog = {

    display: function(data)
    {
        if (Object.isString(data)) {
            data = decodeURIComponent(data).evalJSON(true);
        }

        if (data.uri) {
            this.params = data.params;
            this.type = data.type;
            this.uri = data.uri;
        }

        if (data.reloadurl) {
            this.reloadurl = data.reloadurl;
        }

        if (!data.form_opts) {
            data.form_opts = {};
        }

        data.form_opts = Object.extend({
            action: '#',
            id: data.form_id || 'RB_confirm'
        }, data.form_opts);

        var n = new Element('FORM', data.form_opts).insert(
            new Element('P').insert(data.text)
        );

        delete RedBox.onDisplay;

        n.addClassName('RB_confirm');
        if (data.form) {
            n.insert(data.form);
        } else if (!data.noinput) {
            n.insert(new Element('INPUT', { name: 'dialog_input', type: data.password ? 'password' : 'text', size: 15 }).setValue(data.input_val));
            RedBox.onDisplay = Form.focusFirstElement.curry(n);
        }

        if (data.ok_text) {
            n.insert(
                new Element('INPUT', { type: 'button', className: 'button', value: data.ok_text }).observe('click', this._onClick.bind(this))
            );
        }

        n.insert(
            new Element('INPUT', { type: 'button', className: 'button', value: data.cancel_text }).observe('click', this._close.bind(this))
        ).observe('keydown', function(e) { if ((e.keyCode || e.charCode) == Event.KEY_RETURN) { e.stop(); this._onClick(e); } }.bind(this));

        RedBox.overlay = true;
        RedBox.showHtml(n);

        if (data.submit_handler) {
            HordeCore.handleSubmit(n, data.submit_handler);
        }
    },

    _close: function()
    {
        var c = RedBox.getWindowContents();
        [ c, c.descendants()].flatten().compact().invoke('stopObserving');
        c.fire('IMPDialog:close');
        RedBox.close();
    },

    _onClick: function(e)
    {
        if (this.uri) {
            var params = $H((!this.params || Object.isArray(this.params)) ? {} : this.params);
            params.update(e.findElement('form').serialize(true));

            new Ajax.Request(this.uri, {
                onSuccess: this._onSuccess.bind(this),
                parameters: params
            });
        } else {
            RedBox.getWindowContents().fire('IMPDialog:onClick', e);
            this._close();
        }
    },

    _onSuccess: function(r)
    {
        r = r.responseJSON;

        if (r.success || (r.response && r.response.success)) {
            this._close();
            this.noreload = false;
            RedBox.getWindowContents().fire('IMPDialog:success', this.type);
            if (!this.noreload) {
                if (this.reloadurl) {
                    location = this.reloadurl;
                } else {
                    location.reload();
                }
            }
        }

        if (r.msgs && window.HordeCore) {
            window.HordeCore.showNotifications(r.msgs);
        } else if (r.msgs && parent.HordeCore) {
            parent.HordeCore.showNotifications(r.msgs);
        } else if (r.error) {
            alert(r.error);
        }
    }

};

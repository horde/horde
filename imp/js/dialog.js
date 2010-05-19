/**
 * Javascript code used to display a RedBox dialog.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

var IMPDialog = {

    display: function(data)
    {
        if (Object.isString(data)) {
            data = decodeURIComponent(data).evalJSON(true);
        }
        if (data.dialog_load) {
            new Ajax.Request(data.dialog_load, { onComplete: this._onComplete.bind(this) });
        } else {
            this._display(data);
        }
    },

    _onComplete: function(response)
    {
        this._display(response.responseJSON.response);
    },

    _display: function(data)
    {
        this.params = data.params;
        this.type = data.type;
        this.uri = data.uri;

        var n = new Element('FORM', { action: '#', id: 'RB_confirm' }).insert(
                    new Element('P').insert(data.text)
                );

        if (data.form) {
            n.insert(data.form);
        } else {
            n.insert(new Element('INPUT', { name: 'dialog_input', type: data.password ? 'password' : 'text', size: 15 }));
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
        RedBox.onDisplay = Form.focusFirstElement.curry(n);
        RedBox.showHtml(n);
    },

    _close: function()
    {
        var c = RedBox.getWindowContents();
        [ c, c.descendants()].flatten().compact().invoke('stopObserving');
        RedBox.close();
    },

    _onClick: function(e)
    {
        var params = $H((!this.params || Object.isArray(this.params)) ? {} : this.params);
        params.update(e.findElement('form').serialize(true));

        new Ajax.Request(this.uri, { parameters: params, onSuccess: this._onSuccess.bind(this) });
    },

    _onSuccess: function(r)
    {
        r = r.responseJSON;

        if (r.response.success) {
            this._close();
            this.noreload = false;
            document.fire('IMPDialog:success', this.type);
            if (!this.noreload) {
                location.reload();
            }
        } else if (r.response.error) {
            alert(r.response.error);
        }
    }

};

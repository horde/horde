/**
 * Javascript code used to display a RedBox pasphrase dialog.
 *
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@curecanti.org>
 */

var IMPEncrypt = {

    display: function(data)
    {
        data = decodeURIComponent(data).evalJSON(true);
        this.action = data.action;
        this.params = data.params;
        this.uri = data.uri;

        var n = new Element('FORM', { action: '#', id: 'RB_confirm' }).insert(
                    new Element('P').insert(data.text)
                ).insert(
                    new Element('INPUT', { type: 'text', size: 15 })
                ).insert(
                    new Element('INPUT', { type: 'button', className: 'button', value: data.ok_text }).observe('click', this._onClick.bind(this))
                ).insert(
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
        params = this.params || {};
        params.passphrase = $F(e.findElement('form').down('input'));

        new Ajax.Request(this.uri, { parameters: params, onSuccess: this._onSuccess.bind(this), onFailure: this._onFailure.bind(this) });
    },

    _onSuccess: function(r)
    {
        try {
            r = r.responseText.evalJSON(true);
        } catch (e) {}

        if (r.response.success) {
            this._close();
            if (this.action) {
                this.action();
            } else {
                location.reload();
            }
        } else if (r.response.error) {
            alert(r.response.error);
        }
    },

    _onFailure: function(r)
    {
    }

};

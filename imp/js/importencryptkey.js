/**
 * Javascript API used to import a public encryption key from the message
 * display.
 *
 * Events triggered:
 * -----------------
 * IMPImportEncryptKey:success
 *   params: NONE
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

var IMPImportEncryptKey = {

    handles: {},

    clickHandler: function(e)
    {
        if (!Object.isElement(e.element())) {
            return false;
        }

        var id = e.element().readAttribute('id');

        if (this.handles[id]) {
            new Ajax.Request(this.uri, {
                onSuccess: this._onSuccess.bind(this, e.element()),
                parameters: this.handles[id]
            });
        }
    },

    _onSuccess: function(elt, r)
    {
        if (r.responseJSON.response) {
            elt.up('TR').remove();
            elt.fire('IMPImportEncryptKey:success');
        }
        if (HordeCore &&
            HordeCore.showNotifications &&
            r.responseJSON.msgs) {
            HordeCore.showNotifications(r.responseJSON.msgs);
        }
    }

};

document.observe('click', IMPImportEncryptKey.clickHandler.bindAsEventListener(IMPImportEncryptKey));

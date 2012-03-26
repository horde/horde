/**
 * Javascript API used to store preference that the sender's images should
 * never be blocked.
 *
 * Events triggered:
 * -----------------
 * IMPImageUnblock:success
 *   params: NONE
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

var IMPImageUnblock = {

    handles: {},

    clickHandler: function(e)
    {
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
            elt.fire('IMPImageUnblock:success');
            elt.up('TR').remove();
        }
        if (HordeCore &&
            HordeCore.showNotifications &&
            r.responseJSON.msgs) {
            HordeCore.showNotifications(r.responseJSON.msgs);
        }
    }

};

document.observe('click', IMPImageUnblock.clickHandler.bindAsEventListener(IMPImageUnblock));

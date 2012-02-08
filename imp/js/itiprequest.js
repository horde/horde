/**
 * Javascript API used to process itip requests.
 *
 * Events triggered:
 * -----------------
 * IMPItipRequest:success
 *   params: NONE
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

var IMPItipRequest = {

    handles: {},

    submitHandler: function(e)
    {
        var elt = e.element(),
            id = elt.readAttribute('id');

        if (this.handles[id]) {
            e.stop();
            elt.request({ onSuccess: this._onSuccess.bind(this, elt) });
        }
    },

    _onSuccess: function(elt, r)
    {
        if (r.responseJSON.response) {
            elt.fire('IMPItipRequest:success');
        }
        if (HordeCore &&
            HordeCore.showNotifications &&
            r.responseJSON.msgs) {
            HordeCore.showNotifications(r.responseJSON.msgs);
        }
    }

};

document.observe('submit', IMPItipRequest.submitHandler.bindAsEventListener(IMPItipRequest));

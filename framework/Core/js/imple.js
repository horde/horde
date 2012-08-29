/**
 * imple.js - Horde core Imple code.
 *
 * Fires these events:
 * ===================
 * [IMPLE NAME]:do
 * Parameter: parameters list to send to the AJAX endpoint
 *
 * [IMPLE NAME]:complete
 * Parameter: response from AJAX endpoint
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 */

var HordeImple = {

    actions: $H(),

    runImple: function(id, e)
    {
        var params = this.actions.get(id);

        if (Object.isString(params)) {
            eval(params);
        } else {
            if (e.type == 'submit' && e.element().match('FORM')) {
                params.imple_submit = Object.toJSON(e.element().serialize(true));
            }
            $(id).fire(params.imple + ':do', params);
            HordeCore.doAction('imple', params, this.impleCallback.bind(this, id));
        }

        if (e) {
            e.stop();
        }
    },

    impleCallback: function(r, id)
    {
        $(id).fire(this.actions.get(id).imple + ':complete', r);
    },

    // args = id, observe, params
    add: function(args)
    {
        if (args) {
            this.actions.set(args.id, args.params);
            $(args.id).observe(args.observe, this.runImple.bind(this, args.id));
        }
    }

};

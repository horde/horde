/**
 * Javascript for the Vatid block.
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 */

var HordeBlockVatid = {

    onSubmit: function(e)
    {
        var elt = e.element();

        elt.down('IMG').show();

        elt.request({
            onFailure: this.onFailure.bind(this, e),
            onSuccess: this.onSuccess.bind(this, e)
        });

        e.stop();
    },

    onFailure: function(e, r)
    {
        e.element().down('IMG').hide();
    },

    onSuccess: function(e, r)
    {
        var elt = e.element();

        elt.down('DIV.vatidResults').update(r.responseJSON.response).scrollTo();
        elt.down('IMG').hide();
    }

};

/**
 * Javascript for the Vatid block.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
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
        elt.down('IMG').hide();
    },

    onSuccess: function(e, r)
    {
        var elt = e.element();

        elt.down('DIV.vatidResults').update(r.responseJSON.response).scrollTo();

        elt.down('IMG').hide();
    }

};

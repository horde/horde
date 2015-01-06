/**
 * Javascript for the Vatid block.
 *
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2 (http://www.horde.org/licenses/lgpl)
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

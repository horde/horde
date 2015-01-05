/**
 * Horde identity selection javascript.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2.1 (http://www.horde.org/licenses/lgpl21)
 */

var HordeIdentitySelect = {

    newChoice: function()
    {
        var identity = $('identity'),
            id = Number($F(identity));

        this.identities[id].each(function(a) {
            var field = $(a[0]);

            switch (a[1]) {
            case "special":
                identity.fire('HordeIdentitySelect:change', {
                    i: id,
                    pref: a[0]
                });
                break;

            default:
                field.setValue(a[2]);
                break;
            }
        });
    },

    onDomLoad: function()
    {
        $('identity').observe('change', this.newChoice.bind(this));
        this.newChoice();
    }

};

document.observe('dom:loaded', HordeIdentitySelect.onDomLoad.bind(HordeIdentitySelect));

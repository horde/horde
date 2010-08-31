/**
 * Horde identity selection javascript.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 */

var HordeIdentitySelect = {

    newChoice: function()
    {
        var identity = $('identity'),
            id = Number($F(identity));

        if (id < 0) {
            identity.up('FORM').reset();
            identity.setValue(id);
            return;
        }

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
    }

};

document.observe('dom:loaded', HordeIdentitySelect.onDomLoad.bind(HordeIdentitySelect));

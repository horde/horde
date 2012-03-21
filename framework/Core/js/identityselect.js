/**
 * Horde identity selection javascript.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
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
            // This is a hotfix that cannot be properly solved without BC
            // breakage.
            if (!Object.isUndefined(CKEDITOR) &&
                CKEDITOR.instances['signature_html']) {
                CKEDITOR.instances['signature_html'].setData($('signature_html').getValue());
            }
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
        this.newChoice();
    }

};

document.observe('dom:loaded', HordeIdentitySelect.onDomLoad.bind(HordeIdentitySelect));

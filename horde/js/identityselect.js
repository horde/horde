/**
 * Horde identity selection javascript.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

var IdentitySelect = {

    newChoice: function()
    {
        var id = $('identity').down('[value=' + $F('identity') + ']').value;

        if (id < 0) {
            $('prefs').reset();
            $('identity').setValue(id);
            return;
        }

        this.identities[id].each(function(a) {
            var field = $(a[0]);
            if (!field) {
                return;
            }

            switch (a[1]) {
            case "enum":
                for (var j = 0; j < field.options.length; ++j) {
                    if (field.options[j].value == a[2]) {
                        field.selectedIndex = j;
                        break;
                    }
                }
                break;

            case "implicit":
                eval("newChoice_" + a[0] + "(" + a[2] + ")");
                break;

            case "checkbox":
            default:
                field.setValue(a[2]);
                break;
            }
        });
    },

    deleteIdentity: function()
    {
        var params, q,
            id = $('identity').down('[value=' + $F('identity') + ']').value;

        if (id >= 0) {
            q = this.deleteurl.indexOf('?');
            params = this.deleteurl.toQueryParams();
            params.id = id;
            window.location.assign(this.deleteurl.substring(0, q) + '?' + params.toQueryString());
        }
    },

    onDomLoad: function()
    {
        if ($('identity')) {
            $('identity').observe('change', this.newChoice.bind(this));
        }
        if ($('deleteidentity')) {
            $('deleteidentity').observe('click', this.deleteIdentity.bind(this));
        }
    }

};

document.observe('dom:loaded', IdentitySelect.onDomLoad.bind(IdentitySelect));

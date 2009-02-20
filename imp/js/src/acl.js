/**
 * Provides the javascript for the acl.php script (standard view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpAcl = {

    acl_loading: false,

    folderChange: function(e, clear)
    {
        if ($F('aclfolder')) {
            if (!this.acl_loading || clear != null) {
                this.acl_loading = true;
                $('acl').disable();
                $('folders').submit();
                e.stop();
            }
        }
    },

    changeHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'aclfolder':
            this.folderChange(e);
            break;
        }
    },

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element();

        while (Object.isElement(elt)) {
            switch (elt.readAttribute('id')) {
            case 'changefolder':
            case 'resetbut':
                this.folderChange(e, true);
                break;
            }

            elt = elt.up();
        }
    }

};

document.observe('change', ImpAcl.changeHandler.bindAsEventListener(ImpAcl));
document.observe('click', ImpAcl.clickHandler.bindAsEventListener(ImpAcl));

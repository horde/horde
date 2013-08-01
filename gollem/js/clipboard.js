/**
 * Provides the javascript for the clipboard.php script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var GollemClipboard = {

    // Variables set by clipboard.php:
    //   selectall, selectnone

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var id, tmp,
            elt = e.element();

        while (Object.isElement(elt)) {
            id = elt.readAttribute('id');

            switch (id) {
            case 'gollem-selectall':
                tmp = elt.getValue();
                elt.next('SPAN').update(tmp ? this.selectnone : this.selectall);
                $('gollem-clipboard').getInputs('checkbox').without(elt).invoke('setValue', tmp);
                return;

            case 'gollem-pastebutton':
                $('actionID').setValue('paste_items');
                $('gollem-clipboard').submit();
                return;

            case 'gollem-clearbutton':
                $('actionID').setValue('clear_items');
                $('gollem-clipboard').submit();
                return;

            case 'gollem-cancelbutton':
                $('gollem-clipboard').submit();
                return;
            }

            elt = elt.up();
        }
    }

};

document.observe('click', GollemClipboard.clickHandler.bindAsEventListener(GollemClipboard));

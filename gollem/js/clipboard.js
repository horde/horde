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
            case 'selectall':
                tmp = elt.getValue();
                elt.next('SPAN').update(tmp ? this.selectnone : this.selectall);
                $('clipboard').getInputs('checkbox').without(elt).invoke('setValue', tmp);
                return;

            case 'pastebutton':
                $('actionID').setValue('paste_items');
                $('clipboard').submit();
                return;

            case 'pastebutton':
                $('actionID').setValue('clear_items');
                $('clipboard').submit();
                return;

            case 'cancelbutton':
                $('clipboard').submit();
                return;
            }

            elt = elt.up();
        }
    }

};

document.observe('click', GollemClipboard.clickHandler.bindAsEventListener(GollemClipboard));

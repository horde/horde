/**
 * Provides the javascript for the selectlist.php script.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

var Gollem_Selectlist = {

    returnID: function()
    {
        var formid = $F('formid'),
            field = parent.opener.document[formid].selectlist_selectid,
            field2 = parent.opener.document[formid].actionID;

        if (parent.opener.closed || !field || !field2) {
            alert(GollemText.opener_window);
            window.close();
            return;
        }

        field.value = $F('cacheid');
        field2.value = 'selectlist_process';

        parent.opener.document[formid].submit();
        window.close();
    },

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
            case 'addbutton':
                $('actionID').setValue('select');
                $('selectlist').submit();
                return;

            case 'cancelbutton':
                window.close();
                return;

            case 'donebutton':
                this.returnID();
                return;
            }

            elt = elt.up();
        }
    },

    onDomLoad: function()
    {
        $('selectlist').observe('click', this.clickHandler.bindAsEventListener(this));
    }

};

document.observe('dom:loaded', Gollem_Selectlist.onDomLoad.bind(Gollem_Selectlist));

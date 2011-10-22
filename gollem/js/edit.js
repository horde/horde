/**
 * Provides the javascript for the edit.php script.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var GollemEdit = {

    onDomLoad: function()
    {
        $('cancelbutton').observe('click', function() {
            window.close();
        });

        $('editcontent').focus();
    }

};

document.observe('dom:loaded', GollemEdit.onDomLoad.bind(GollemEdit));

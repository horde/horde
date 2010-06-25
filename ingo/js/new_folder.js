/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var IngoNewFolder = {

    newFolderName: function(name, tagname)
    {
        var form = document.getElementsByName(name);
        var selector = form[0].elements.namedItem(tagname);

        if (selector.selectedIndex == 1){
            var folder = window.prompt(this.folderprompt + '\n', '');

            if ((folder != null) && (folder != '')) {
                form[0].actionID.value = 'create_folder';
                form[0].new_folder_name.value = folder;
                form[0].submit();
            }
        }

        return true;
    }

};

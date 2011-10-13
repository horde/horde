/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var IngoNewFolder = {

    // Set in PHP code: folderprompt

    changeHandler: function(e)
    {
        var folder,
            elt = e.element(),
            id = elt.identify() + '_new',
            newfolder = $(id),
            sel = $(elt[elt.selectedIndex]);

        if (!newfolder &&
            sel.hasClassName('flistCreate') &&
            (folder = window.prompt(this.folderprompt + '\n', ''))  &&
            !folder.empty()) {
            this.setNewFolder(elt, folder);
        }
    },

    setNewFolder: function(elt, folder)
    {
        elt = $(elt);

        var sel,
            id = elt.identify() + '_new';

        elt.selectedIndex = elt.down('.flistCreate').index;
        sel = $(elt[elt.selectedIndex]);

        elt.insert({
            after: new Element('INPUT', { id: id, name: id, type: 'hidden' }).setValue(folder)
        });
        sel.update(sel.text + ' [' + folder.escapeHTML() + ']');
    },

    onDomLoad: function()
    {
        $$('.flistSelect').invoke('observe', 'change', this.changeHandler.bindAsEventListener(this));
    }

};

document.observe('dom:loaded', IngoNewFolder.onDomLoad.bind(IngoNewFolder));

/**
 * Provides the javascript for creating a new mailbox.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    ASL (http://www.horde.org/licenses/apache)
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
            e.stop();
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

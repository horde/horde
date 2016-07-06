/**
 * AnselTree - Manage and build a tree of galleries for the Ansel dynamic view
 * sidebar.
 *
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */
AnselTree = Class.create({
    node: null,

    initialize: function(node)
    {
        this.node = node;
        this.node.observe('click', this.clickHandler.bindAsEventListener(this));
    },

    create: function(galleries)
    {
        var result;

        this.node.update();
        $H(galleries).values().each(function(g) {
            result = this._buildGallery(g);
        }.bind(this));
        this.node.update(result);
    },

    _buildGallery: function(g)
    {
        var parent = new Element('div', { class: 'ansel_tree_tile'});
        var img = new Element('img', {
            class: 'ansel-tree-image',
            src: g.ki
        });
        img.store('gid', g.id);
        parent.update(g.n).insert(img);
        if (g.sg && g.sg.length) {
            g.sg.each(function(s) {
                parent.insert(this._buildGallery(s));
            }.bind(this));
        }
        return parent;
    },

    clickHandler: function(e)
    {
        if (e.isRightClick() || typeof e.element != 'function') {
            return;
        }
        var elt = e.element();
        while (Object.isElement(elt)) {
            if (elt.hasClassName('ansel-tree-image')) {
                this.node.fire('AnselLayout:galleryClick', { gid: elt.retrieve('gid') });
                e.stop();
                return;
            }
            elt = elt.up();
        }
    }
});
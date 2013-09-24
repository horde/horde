/**
 * DragHandler library for use with prototypejs.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var DragHandler = {

    // dropelt,
    // droptarget,
    // hoverclass,

    handleDrop: function(e)
    {
        this.dropelt.hide();
        this.droptarget.show();
        this.dropelt.fire('DragHandler:drop', e);
        e.stop();
    },

    handleEnter: function(e)
    {
        if (!this.dropelt.visible()) {
            this.dropelt.clonePosition(this.droptarget).show();
            this.droptarget.hide();
        }
    },

    handleLeave: function(e)
    {
        var pointer = e.pointer(),
            vp = document.viewport.getDimensions();

        if (pointer.x <= 0 ||
            pointer.x >= vp.width ||
            pointer.y <= 0 ||
            pointer.y >= vp.height) {
            this.dropelt.hide();
            this.droptarget.show();
        }
    },

    handleOver: function(e)
    {
        if (e.target == this.dropelt) {
            this.dropelt.addClassName(this.hoverclass);
            e.stop();
        } else {
            this.dropelt.removeClassName(this.hoverclass);
        }
    },

    onDomLoad: function()
    {
        document.on('dragenter', 'body', this.handleEnter.bindAsEventListener(this));
        document.on('dragleave', 'body', this.handleLeave.bindAsEventListener(this));
        document.observe('dragover', this.handleOver.bindAsEventListener(this));
        document.observe('drop', this.handleDrop.bind(this));
    }

};

document.observe('dom:loaded', DragHandler.onDomLoad.bind(DragHandler));

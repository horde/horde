/**
 * DragHandler library for use with prototypejs.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var DragHandler = {

    dragtrack: [],
    // dropelt,
    // droptarget,

    handleDrop: function(e)
    {
        this.dragtrack = [];
        this.dropelt.hide();
        this.droptarget.show();
        this.dropelt.fire('DragHandler:drop', e);
        e.stop();
    },

    handleEnter: function(e)
    {
        if (!Object.isElement(e.target)) {
            this.dragtrack.push(e.target);
        } else if (!this.dropelt.visible()) {
            this.dropelt.clonePosition(this.droptarget).show();
            this.droptarget.hide();

            if (e.target != this.droptarget &&
                !e.target.descendantOf(this.droptarget)) {
                this.dragtrack.push(e.target);
            }
        } else if (this.dragtrack.lastIndexOf(e.target) == -1) {
            this.dragtrack.push(e.target);
        }
    },

    handleLeave: function(e)
    {
        this.dragtrack = this.dragtrack.without(e.target);

        if (!this.dragtrack.length) {
            this.dropelt.hide();
            this.droptarget.show();
        }
    },

    handleOver: function(e)
    {
        if (e.target == this.dropelt) {
            e.stop();
        }
    }

};

document.observe('dragenter', DragHandler.handleEnter.bindAsEventListener(DragHandler));
document.observe('dragleave', DragHandler.handleLeave.bindAsEventListener(DragHandler));
document.observe('dragover', DragHandler.handleOver.bindAsEventListener(DragHandler));
document.observe('drop', DragHandler.handleDrop.bind(DragHandler));


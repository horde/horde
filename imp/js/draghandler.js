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

    to: -1,

    handleDrop: function(e)
    {
        if (this.dropelt.hasClassName(this.hoverclass)) {
            this.dropelt.fire('DragHandler:drop', e);
        }
        this.hide();
        e.stop();
    },

    hide: function()
    {
        this.dropelt.hide();
        this.droptarget.show();
    },

    handleOver: function(e)
    {
        if (!this.dropelt.visible()) {
            this.dropelt.clonePosition(this.droptarget).show();
            this.droptarget.hide();
        }

        clearTimeout(this.to);
        this.to = this.hide.bind(this).delay(0.25);

        if (e.target == this.dropelt) {
            this.dropelt.addClassName(this.hoverclass);
        } else {
            this.dropelt.removeClassName(this.hoverclass);
        }

        e.stop();
    }

};

document.observe('dragover', DragHandler.handleOver.bindAsEventListener(DragHandler));
document.observe('drop', DragHandler.handleDrop.bindAsEventListener(DragHandler));

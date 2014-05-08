/**
 * DragHandler library for use with prototypejs.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2013-2014 Horde LLC
 * @license    GPLv2 (http://www.horde.org/licenses/gpl)
 */

var DragHandler = {

    // dropelt,
    // droptarget,
    // hoverclass,
    // leave

    to: -1,

    handleDrop: function(e)
    {
        if (this.dropelt.hasClassName(this.hoverclass)) {
            this.dropelt.fire('DragHandler:drop', e);
        }
        this.leave = true;
        this.hide();
        e.stop();
    },

    hide: function()
    {
        if (this.leave) {
            this.dropelt.hide();
            this.droptarget.show();
            this.leave = false;
        }
    },

    handleLeave: function(e)
    {
        clearTimeout(this.to);
        this.to = this.hide.bind(this).delay(0.25);
        this.leave = true;
    },

    handleOver: function(e)
    {
        if (!this.dropelt.visible()) {
            this.dropelt.clonePosition(this.droptarget).show();
            this.droptarget.hide();
        }

        this.leave = false;

        if (e.target == this.dropelt) {
            this.dropelt.addClassName(this.hoverclass);
        } else {
            this.dropelt.removeClassName(this.hoverclass);
        }

        e.stop();
    }

};

document.observe('dragleave', DragHandler.handleLeave.bindAsEventListener(DragHandler));
document.observe('dragover', DragHandler.handleOver.bindAsEventListener(DragHandler));
document.observe('drop', DragHandler.handleDrop.bindAsEventListener(DragHandler));

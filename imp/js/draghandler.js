/**
 * DragHandler library (files support only) for use with prototypejs.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2013-2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var DragHandler = {

    // dropelt,
    // droptarget,
    // hoverclass,
    // leave

    to: -1,

    handleObserve: function(e)
    {
        if (this.dropelt) {
            if (Prototype.Browser.IE &&
                !(("onpropertychange" in document) && (!!window.matchMedia))) {
                // IE 9 supports drag/drop, but not dataTransfer.files
                e.stop();
            } else {
                switch (e.type) {
                case 'dragleave':
                    this.handleLeave(e);
                    break;

                case 'dragover':
                    this.handleOver(e);
                    break;

                case 'drop':
                    this.handleDrop(e);
                    break;
                }
            }
        }
    },

    handleDrop: function(e)
    {
        if (this.dropelt.hasClassName(this.hoverclass) &&
            e.dataTransfer &&
            e.dataTransfer.files) {
            this.dropelt.fire('DragHandler:drop', e.dataTransfer.files);
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

document.observe('dragleave', DragHandler.handleObserve.bindAsEventListener(DragHandler));
document.observe('dragover', DragHandler.handleObserve.bindAsEventListener(DragHandler));
document.observe('drop', DragHandler.handleObserve.bindAsEventListener(DragHandler));

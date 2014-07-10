/**
 * DragHandler library for use with prototypejs.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2013-2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var DragHandler = {

    // dropelt,
    // droptarget,
    // hoverclass,
    // leave,

    to: -1,

    isFileDrag: function(e)
    {
        return (e.dataTransfer &&
                e.dataTransfer.files &&
                e.dataTransfer.files.length);
    },

    handleObserve: function(e)
    {
        if (this.dropelt &&
            (e.dataTransfer ||
             this.dropelt.visible() ||
             (e.memo && e.memo.dataTransfer))) {
            if (Prototype.Browser.IE &&
                !(("onpropertychange" in document) && (!!window.matchMedia))) {
                // IE 9 supports drag/drop, but not dataTransfer.files
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
        if (this.isFileDrag(e)) {
            if (this.dropelt.hasClassName(this.hoverclass)) {
                this.dropelt.fire('DragHandler:drop', e.dataTransfer.files);
            }
            e.stop();
        }
        this.leave = true;
        this.hide();
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
            e.stop();
        } else {
            this.dropelt.removeClassName(this.hoverclass);
            if (Prototype.Browser.IE) {
                e.stop();
            }
        }
    }

};

document.observe('dragleave', DragHandler.handleObserve.bindAsEventListener(DragHandler));
document.observe('dragover', DragHandler.handleObserve.bindAsEventListener(DragHandler));
document.observe('drop', DragHandler.handleObserve.bindAsEventListener(DragHandler));

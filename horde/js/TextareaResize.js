/**
 * TextareaResize: a library that automatically resizes a text area based on
 * its contents.
 *
 * Requires prototypejs 1.6+.
 *
 * Usage:
 * ------
 * cs = new TextareaResize(id[, options]);
 *
 *   id = (string|Element) DOM ID/Element object of textarea.
 *   options = (object) Additional options:
 *      'max_rows' - (Number) The maximum number of rows to display.
 *      'observe_time' - (Number) The interval between form field checks.
 *
 * Custom Events:
 * --------------
 * TexareaResize:resize
 *   Fired when the textarea is resized.
 *   params: NONE
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

var TextareaResize = Class.create({
    // Variables used: elt, max_rows, size

    initialize: function(id, opts)
    {
        opts = opts || {};

        this.elt = $(id);
        this.max_rows = opts.max_rows || 5;
        this.size = -1;

        new Form.Element.Observer(this.elt, opts.observe_time || 1, this.resize.bind(this));

        this.resize();
    },

    resize: function()
    {
        var old_rows, rows,
            size = $F(this.elt).length;

        if (size == this.size) {
            return;
        }

        old_rows = rows = Number(this.elt.readAttribute('rows', 1));

        if (size > this.size) {
            while (rows < this.max_rows) {
                if (this.elt.scrollHeight == this.elt.clientHeight) {
                    break;
                }
                this.elt.writeAttribute('rows', ++rows);
            }
        } else if (rows > 1) {
            do {
                this.elt.writeAttribute('rows', --rows);
                if (this.elt.scrollHeight != this.elt.clientHeight) {
                    this.elt.writeAttribute('rows', ++rows);
                    break;
                }
            } while (rows > 1);
        }

        this.size = size;

        if (rows != old_rows) {
            this.elt.fire('TextareaResize:resize');
        }
    }

});

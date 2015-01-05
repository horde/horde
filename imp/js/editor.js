/**
 * Ckeditor normalization object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var IMP_Editor = Class.create({

    // config,
    // dready,
    // editor,
    // id,
    // iready
    // wait,

    initialize: function(id, config)
    {
        this.config = Object.clone(config);
        this.id = id;

        this.start();

        this.editor.on('instanceReady', function(evt) {
            this.iready = true;
            document.fire('IMP_Editor:ready', evt.editor);
        }.bind(this));
        this.editor.on('dataReady', function(evt) {
            if (!this.dready) {
                document.fire('IMP_Editor:dataReady', evt.editor);
                this.dready = true;
            }
        }.bind(this));
        this.editor.on('instanceDestroyed', function(evt) {
            this.dready = this.iready = this.editor = false;
            document.fire('IMP_Editor:destroy', evt.editor);
        }.bind(this));
    },

    start: function()
    {
        if (!this.editor) {
            if (Object.isUndefined(this.config.height)) {
                this.config.height = Math.max($(this.id).getHeight(), 200) - 75;
            }
            this.editor = CKEDITOR.replace(this.id, this.config);
        }
    },

    destroy: function()
    {
        if (this.editor) {
            this.editor.destroy(true);
        }
    },

    busy: function()
    {
        return this.wait || !this.iready || !this.dready;
    },

    getData: function()
    {
        return this.editor.getData();
    },

    setData: function(data)
    {
        if (this.busy()) {
            this.setData.bind(this, data).delay(0.1);
        } else {
            this.wait = true;
            this.editor.setData(data, function() {
                this.wait = false;
            }.bind(this));
        }
    },

    resize: function(width, height)
    {
        if (this.busy()) {
            this.resize.bind(this, width, height).delay(0.1);
        } else {
            this.editor.resize(width, height);
        }
    },

    focus: function()
    {
        if (this.busy()) {
            this.focus.bind(this).delay(0.1);
        } else {
            this.editor.focus();
        }
    },

    updateElement: function()
    {
        if (this.busy()) {
            this.updateElement.bind(this).delay(0.1);
        } else {
            this.editor.updateElement();
        }
    }

});

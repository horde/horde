/**
 * File upload widget based on puploader.
 * See: http://www.plupload.com
 *
 * Uses the pluploader lower level API to sort-of duplicate the idea behind the
 * jquery widget.
 *
 * Requires: puploader.js (v1.2.3+) as well as the runtime files for the desired
 *           runtimes, e.g. puploader.html5.js.
 *
 * Usage:
 * ======
 * var uploader = new Horde_Uploader({
 *      'container' - (string) Dom id of the parent container to place the
 *                             widget it.
 *      'target' - (string) Url of target page
 *      'drop_target' (string) Dom id of element to hold file list
 *      'swf_path' - (string) (optional) Path to flash file
 *      'xap_path' - (string) (optional) Path to silverlight file
 *      'browsebutton_class' |
 *      'uploadbutton_class' | - CSS class names for various elements.
 *      'filelist_class'     |
 *      'browse_button'  dom id to use for the file browser button.
 *      'upload_button'  dom id to use for the upload start butotn.
 *      'text' - hash of various strings used in the interface.,
 *      'return_target'
 * });
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
var Horde_Uploader = Class.create({

    handlers: {

        filesAdded: function(up, files)
        {
            $(this._params['return_button']).hide();
            files.each(function(file) {
                var remove = new Element('div', {'class': 'hordeUploaderRemove'}).update('&nbsp;');
                var newdiv = new Element('li',
                    { 'class': this._params.filelistitem_class,
                      'id': file.id
                    })
                    .insert(new Element('div', { 'class': 'hordeUploaderFilename' }).update(file.name))
                    .insert(new Element('div', { 'class': 'hordeUploaderFileaction' }).update(remove))
                    .insert(new Element('div', { 'class': 'hordeUploaderFilestatus' }).update('&nbsp'))
                    .insert(new Element('div', { 'class': 'hordeUploaderFilesize' }).update(plupload.formatSize(file.size)));
                remove.observe('click', function() { var f = up.getFile(newdiv.id); up.removeFile(f); $(newdiv.id).remove(); });
                $(this._params['drop_target']).select('.hordeUploaderFileUl').each(function(p) { p.insert(newdiv) });
            }.bind(this));
        },

        /**
         * TODO: tweak the selector usage... implement a progress bar.
         */
        progress: function(up, file)
        {
            // Refresh the uploader when done. Might be a better way to do this
            // but the documentation is pretty lacking.
            if (up.total.percent == 100) {
                up.files = [];
                up.refresh();
                $(this._params['return_button']).show();
            }
            $(file.id).select('.hordeUploaderFilestatus').each(function(p) { p.update(file.percent + '%') });
        },

        /**
         *
         */
        init: function(up, params)
        {
        },

        /**
         * Receives the server response in response and is responsible for
         * updating the UI accordingly
         */
        fileuploaded: function(up, file, response) {
            try {
                var result = response.response.evalJSON();
                if (result.status != 200) {
                    up.unbind('UploadProgress', this.handlers.progress);
                    $(file.id).select('.hordeUploaderFilestatus').each(function(p) { $(p).update(result.error.message); });
                    $(file.id).setStyle({'fontWeight': 'bold', 'color': 'red'});
                    $(file.id).select('.hordeUploaderFileaction').each(function(p) { $(p).addClassName(this._params['error_class']) }.bind(this));
                } else {
                    $(file.id).setStyle({'fontWeight': 'bold', 'color': 'green'});
                    $(file.id).select('.hordeUploaderFileaction').each(function(p) {
                        $(p).select('.hordeUploaderRemove').each(function(r) { r.remove(); });
                        $(p).update('&nbsp;').addClassName(this._params['success_class']);
                    }.bind(this));
                }
            } catch (Exception) {
                    up.unbind('UploadProgress', this.handlers.progress);
                    $(file.id).select('.hordeUploaderFilestatus').each(function(p) { $(p).update(Exception); });
            }
        }
    },

    /**
     * Params required: container, target.
     * Params not required, but have no defaults: swf_path, xap_path
     * Parms with sensible defaults: browsebutton_class, uploadbutton_class,
     *                               filelist_class, browse_button, drop_target,
     *                               upload_button, upload_button
     */
    initialize: function(params)
    {
        this._params = Object.extend({
            browsebutton_class: 'button',
            uploadbutton_class: 'button',
            header_class: 'hordeUploaderHeader',
            subheader_class: 'hordeUploaderSubHeader',
            container_class: 'hordeUploaderContainer',
            filelist_class: 'hordeUploaderFilelist',
            filelistitem_class: 'hordeUploaderFilelistItem',
            browse_button: 'browseimages',
            drop_target: 'filelist',
            upload_button: 'uploadimages',
            return_button: 'return',
            returnbutton_class: 'button',
            success_class: 'hordeUploaderSuccess',
            error_class: 'hordeUploaderError'
        }, params);

        this._build();

        this._puploader = new plupload.Uploader({
            runtimes: 'html5, flash, silverlight, browserplus',
            browse_button: this._params['browse_button'],
            url: this._params['target'],
            drop_element: this._params['drop_target'],
            flash_swf_url: this._params['swf_path'],
            silverlight_xap_url: this._params['xap_path']
        });

        this._puploader.bind('UploadProgress', this.handlers.progress, this);
        this._puploader.bind('Init', this.handlers.init, this);
        this._puploader.bind('FilesAdded', this.handlers.filesAdded, this);
        this._puploader.bind('FileUploaded', this.handlers.fileuploaded, this);

    },

    init: function()
    {
        this._puploader.init();
        $(this._params['upload_button']).observe('click', function(e) {
            this._puploader.start();
            e.stop();
        }.bindAsEventListener(this));
        this.setReturnTarget(this._params['return_target']);
        $(this._params['return_button']).hide();
    },

    setReturnTarget: function(path)
    {
        $(this._params['return_button']).href = path;
    },

    /**
     * Draw the upload widget.
     *
     * TODO: eh, make pretty :)
     *
     */
    _build: function()
    {
        /* Filelist, with embedded UL */
        var filelist = new Element('div',
            { 'id': this._params['drop_target'],
              'class': this._params['filelist_class'] }).insert(new Element('ul', { 'class': 'hordeUploaderFileUl' }));

        /* Browse button */
        var browse = new Element('a', { 'id': this._params['browse_button'], 'class': this._params['browsebutton_class'] }).update(this._params.text.add);

        /* Upload button */
        var upload = new Element('a', { 'id': this._params['upload_button'], 'class': this._params['uploadbutton_class'] }).update(this._params.text.start);

        /* Return button (Activated when uploader is DONE). */
        var returnButton = new Element('a', { 'id': this._params['return_button'], 'class': this._params['returnbutton_class'] }).update(this._params.text.returnButton);

        /* Header section */
        var header = new Element('div', { 'class': this._params['header_class'] }).update(this._params.text.header);
        var subheader = new Element('div', { 'class': this._params['subheader_class'] }).update(this._params.text.subheader);

        /* filelist header rows */
        var fileheader = new Element('div')
            .insert(new Element('div', { 'class': 'hordeUploaderFilename' }).update('Filename'))
            .insert(new Element('div', { 'class': 'hordeUploaderFileaction'}).update('&nbsp;'))
            .insert(new Element('div', { 'class': 'hordeUploaderFilestatus'}).update('Status'))
            .insert(new Element('div', { 'class': 'hordeUploaderFilesize' }).update('Size'));


        /* Build full widget, Insert into the DOM */
        $(this._params['container']).insert(
            new Element('div', {'class': this._params['container_class'] })
                .insert(header)
                .insert(subheader)
                .insert(fileheader)
                .insert(new Element('div', { 'class': 'clear' }))
                .insert(filelist)
                .insert(browse)
                .insert(upload)
                .insert(returnButton));
    }

});
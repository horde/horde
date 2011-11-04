/**
 * File upload widget based on puploader.
 * See: http://www.plupload.com
 *
 * Uses the pluploader lower level API to sort-of duplicate the idea behind the
 * jquery widget.
 *
 * Requires: puploader.js (v1.5.1+) as well as the runtime files for the desired
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

    // Holds files selected by user while they are in the process of being
    // added to the queue.
    _queue: [],

    // Default event handlers.
    handlers: {

        filesadded: function(up, files)
        {
            this._queue = files;
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
                    $(file.id).addClassName(this._params['filelistitemerror_class']);
                    $(file.id).select('.hordeUploaderFileaction').each(function(p) {
                        $(p).select('.hordeUploaderRemove').each(function(r) { r.remove(); });
                        $(p).addClassName(this._params['error_class']) }.bind(this));
                } else {
                    $(file.id).addClassName(this._params['filelistitemdone_class']);
                    $(file.id).select('.hordeUploaderFileaction').each(function(p) {
                        $(p).select('.hordeUploaderRemove').each(function(r) { r.remove(); });
                        $(p).update('&nbsp;').addClassName(this._params['success_class']);
                    }.bind(this));
                }
            } catch (Exception) {
                    up.unbind('UploadProgress', this.handlers.progress);
                    $(file.id).select('.hordeUploaderFileaction').each(function(p) {
                        $(p).select('.hordeUploaderRemove').each(function(r) { r.remove(); });
                        $(p).update('&nbsp;').addClassName(this._params['error_class']);
                    }.bind(this));
                    $(file.id).select('.hordeUploaderFilestatus').each(function(p) { $(p).update(Exception); });
            }
        },

        error: function(up, err) {
            var file = err.file, message;
            if (file) {
                message = err.message;
                if (err.details) {
                    message += ' (' + err.details + ')';
                }
                if (err.code == plupload.FILE_SIZE_ERROR) {
                    alert(this._params.text.size + ' ' + file.name);
                }
                if (err.code == plupload.FILE_EXTENSION_ERROR) {
                    alert(this._params.text.type + ' ' + file.name);
                }
            }
        },

        queuechanged: function(up) {
            this.updateList();
        },

        // Handlers that may be useful for client code. Not used by default.
        // Override before creating object if needed.
        uploadfile: function(up, file) {},
        statechanged: function(up) {},
        filesremoved: function(up, files) {},
        chunkuploaded: function(up, file, response) {},
        uploadcomplete: function(up, files) {}
    },

    /**
     * Params required: container, target.
     * Params not required, but have no defaults: swf_path, xap_path
     * Parms with sensible defaults: browsebutton_class, uploadbutton_class,
     *                               filelist_class, browse_button, drop_target,
     *                               upload_button, upload_button
     */
    initialize: function(params, handlers)
    {
        this._params = Object.extend({
            browsebutton_class: 'button hordeUploaderAdd',
            uploadbutton_class: 'button hordeUploaderStart',
            header_class: 'hordeUploaderHeader',
            headercontent_class: 'hordeUploaderHeaderContent',
            subheader_class: 'hordeUploaderSubHeader',
            container_class: 'hordeUploaderContainer',
            filelist_class: 'hordeUploaderFilelist',
            filelistitem_class: 'hordeUploaderFilelistItem',
            filelistitemdone_class: 'hordeUploaderFilelistItemDone',
            filelistitemerror_class: 'hordeUploaderFilelistItemError',
            browse_button: 'browseimages',
            drop_target: 'filelist',
            upload_button: 'uploadimages',
            return_button: 'return',
            returnbutton_class: 'button',
            success_class: 'hordeUploaderSuccess',
            error_class: 'hordeUploaderError',
            footer_class: 'hordeUploaderFooter',
            multipart: false,
            max_file_size: false,
            chunk_size: false
        }, params);
        this.handlers = Object.extend(this.handlers, handlers);
        this._build();
        var opts = {
            runtimes: 'html5, flash, silverlight, browserplus',
            browse_button: this._params['browse_button'],
            url: this._params['target'],
            drop_element: this._params['drop_target'],
            flash_swf_url: this._params['swf_path'],
            silverlight_xap_url: this._params['xap_path'],
            multipart: this._params['multipart']
        };
        if (this._params['max_file_size']) {
            opts.max_file_size = this._params['max_file_size'];
        }
        if (this._params['chunk_size']) {
            opts.chunk_size = this._params['chunk_size'];
        }
        this._puploader = new plupload.Uploader(opts);
        this._puploader.bind('UploadProgress', this.handlers.progress, this);
        this._puploader.bind('Init', this.handlers.init, this);
        this._puploader.bind('FilesAdded', this.handlers.filesadded, this);
        this._puploader.bind('UploadFile', this.handlers.uploadfile, this);
        this._puploader.bind('FileUploaded', this.handlers.fileuploaded, this);
        this._puploader.bind('Error', this.handlers.error, this);
        this._puploader.bind('StateChanged', this.handlers.statechanged, this);
        this._puploader.bind('QueueChanged', this.handlers.queuechanged, this);
        this._puploader.bind('FilesRemoved', this.handlers.filesremoved, this);
        this._puploader.bind('ChunkUploaded', this.handlers.chunkuploaded, this);
        this._puploader.bind('UploadComplete', this.handlers.uploadcomplete, this);
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

    updateList: function()
    {
        $(this._params['return_button']).hide();
        this._queue.each(function(file) {
            var f = file;
            if (f.status == plupload.QUEUED) {
                var remove = new Element('div', {'class': 'hordeUploaderRemove'}).update('&nbsp;');
                var newdiv = new Element('li',
                    { 'class': this._params.filelistitem_class,
                      'id': f.id
                    })
                    .insert(new Element('div', { 'class': 'hordeUploaderFilename' }).update(f.name))
                    .insert(new Element('div', { 'class': 'hordeUploaderFileaction' }).update(remove))
                    .insert(new Element('div', { 'class': 'hordeUploaderFilestatus' }).update('&nbsp'))
                    .insert(new Element('div', { 'class': 'hordeUploaderFilesize' }).update(plupload.formatSize(f.size)));
                remove.observe('click', function() { var f = up.getFile(newdiv.id); up.removeFile(f); $(newdiv.id).remove(); });
                $(this._params['drop_target']).select('.hordeUploaderFileUl').each(function(p) { p.insert(newdiv) });
            }
        }.bind(this));
        this._queue = [];
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
        var subheader = new Element('div', { 'class': this._params['subheader_class'] }).update(this._params.text.subheader);
        var headercontent = new Element('div', { 'class': this._params['headercontent_class'] }).update(this._params.text.header);
        headercontent.insert(subheader);
        var header = new Element('div', { 'class': this._params['header_class'] }).update(headercontent);

        /* Footer */
        var footer = new Element('div', { 'class': this._params['footer_class'] });

        /* filelist header rows */
        var fileheader = new Element('div', { 'class': 'hordeUploaderFilelistHeader'})
            .insert(new Element('div', { 'class': 'hordeUploaderFilename' }).update('Filename'))
            .insert(new Element('div', { 'class': 'hordeUploaderFileaction'}).update('&nbsp;'))
            .insert(new Element('div', { 'class': 'hordeUploaderFilestatus'}).update('Status'))
            .insert(new Element('div', { 'class': 'hordeUploaderFilesize' }).update('Size'));


        /* Build full widget, Insert into the DOM */
        $(this._params['container']).insert(
            new Element('div', {'class': this._params['container_class'] })
                .insert(header)
                .insert(fileheader)
                .insert(new Element('div', { 'class': 'clear' }))
                .insert(filelist)
                .insert(footer.insert(browse).insert(upload).insert(returnButton))
        );
    }

});
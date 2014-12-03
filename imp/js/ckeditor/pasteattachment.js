/**
 * Paste attachment plugin for CKEditor.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2013-2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

CKEDITOR.plugins.add('pasteattachment', {

    init: function(editor)
    {
        function attachCallback(r)
        {
            var iframe = editor.getThemeSpace('contents').$.down('IFRAME');

            Prototype.Selector.select('[dropatc_id=' + r.file_id + ']', iframe.contentDocument || iframe.contentWindow.document).each(function(elt) {
                if (r.success) {
                    elt.setAttribute(r.img.related[0], r.img.related[1]);
                    elt.setAttribute('height', elt.height);
                    elt.setAttribute('width', elt.width);
                    IMP_Ckeditor_Imagepoll.add(elt);
                } else {
                    elt.parentNode.removeChild(elt);
                }
            });
        }

        function uploadAtc(files)
        {
            ImpCompose.attachlist.uploadAttach(
                files,
                { img_data: 1 },
                attachCallback
            ).each(function(file) {
                var fr = new FileReader();
                fr.onload = function(e) {
                    var elt = new CKEDITOR.dom.element('img');
                    elt.setAttributes({
                        dropatc_id: file.key,
                        src: e.target.result
                    });
                    editor.insertElement(elt);
                };
                fr.readAsDataURL(file.value);
            });
        }

        function fireEventInParent(type, e)
        {
            var evt;

            try {
                evt = new CustomEvent(type, { bubbles: true, cancelable: true });
            } catch (ex) {
                evt = document.createEvent('DragEvent');
                evt.initEvent(type, true, true);
            }

            evt.memo = e.data.$;

            editor.getThemeSpace('contents').$.dispatchEvent(evt);
        }

        function addImages(files)
        {
            var error = 0,
                upload = [];

            $A(files).each(function(f) {
                if (f.type.startsWith('image/')) {
                    if (f.getAsFile) {
                        f = f.getAsFile();
                    }
                    if (!f.name) {
                        f.name = DimpCore.text.image_data;
                    }
                    upload.push(f);
                } else {
                    ++error;
                }
            });

            if (upload.size()) {
                uploadAtc(upload);
            }

            if (error) {
                HordeCore.notify(
                    DimpCore.text.dragdropimg_error.sub('%d', error),
                    'horde.error'
                );
            }
        }

        editor.on('contentDom', function(e1) {
            editor.document.on('drop', function(e2) {
                var d = e2.data.$;

                fireEventInParent('drop', e2);

                /* Only handle file data here. For other data (i.e. text)
                 * have the browser handle it natively, except for IE -- it is
                 * buggy so grab the data from the dataTransfer object
                 * ourselves and insert. */
                if (!DragHandler.isFileDrag(d)) {
                    if (Prototype.Browser.IE &&
                        d.dataTransfer.types &&
                        d.dataTransfer.types[0] === 'Text') {
                        editor.insertText(d.dataTransfer.getData('Text'));
                        e2.data.preventDefault();
                    }
                    return;
                }

                e2.data.preventDefault();

                addImages(d.dataTransfer.files);
            });

            editor.document.on('dragover', function(e3) {
                if (Prototype.Browser.IE) {
                    e3.data.preventDefault();
                }
                fireEventInParent('dragover', e3);
            });

            /* This works on Chrome. 'paste' action below works on FF. */
            editor.document.on('paste', function(ev) {
                var d = ev.data.$;
                try {
                    addImages(
                        (d.clipboardData || d.originalEvent.clipboardData).items
                    );
                } catch (e) {}
            });
        });

        editor.on('paste', function(ev) {
            if (ev.data.html) {
                var b, data, i,
                    a = [],
                    span = new Element('SPAN').insert(ev.data.html).down();

                /* Only support images for now. */
                if (span && span.match('IMG')) {
                    data = (span.readAttribute('src') || '').split(',', 2);
                    if (data.size() != 2) {
                        /* IE 10 doesn't support pasting images, so don't try
                         * to copy HTML IMG source. */
                        if (span.hasAttribute('imp_related_attr')) {
                            ev.data.html = '';
                        }
                        return;
                    }

                    try {
                        data[1] = Base64.atob(data[1]);
                    } catch (e) {
                        HordeCore.notify(DimpCore.text.paste_error, 'horde.error');
                        ev.data.html = '';
                        return;
                    }
                    a.length = data[1].length;

                    for (i = 0; i < a.length; ++i) {
                        a[i] = data[1].charCodeAt(i);
                    }

                    b = new Blob(
                        [ new Uint8Array(a) ],
                        { type: data[0].split(':')[1].split(';')[0] }
                    );
                    b.name = DimpCore.text.image_data;

                    uploadAtc([ b ]);

                    ev.data.html = '';
                }
            }
        });
    }

});

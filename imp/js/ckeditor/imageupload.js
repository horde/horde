/**
 * Add image upload capability to the image plugin.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2013-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

document.observe('dom:loaded', function() {
    window.CKEDITOR && window.CKEDITOR.on('dialogDefinition', function(ev) {
        var params, rf, upload,
            definition = ev.data.definition;

        if (ev.data.name == 'image') {
            upload = definition.getContents('Upload');

            rf = upload.add({
                hidden: true,
                id: 'related_fields',
                type: 'text'
            });

            params = $H({ composeCache: $F(ImpCompose.getCacheElt()) });
            HordeCore.addRequestParams(params);

            upload.get('uploadButton').filebrowser = {
                action: 'QuickUpload',
                onSelect: function(fileUrl, data) {
                    delete rf.attrdata;
                    if (!Object.isString(data)) {
                        rf.attrdata = data;
                    }
                    return true;
                },
                params: params.toObject(),
                target: 'info:txtUrl'
            };

            definition.getContents('info').add({
                align: 'center',
                id: 'uploadshortcut',
                label: ev.editor.lang.common.upload,
                onClick: function() {
                    definition.dialog.selectPage('Upload');
                },
                style: 'display:inline-block;margin-top:10px;',
                type: 'button'
            }, 'browse');

            definition.dialog.on('cancel', function(ev2) {
                if (rf.attrdata) {
                    ImpCkeditorImgs.remove(ev.editor, [
                        new CKEDITOR.dom.element('IMG').writeAttribute(rf.attrdata)
                    ]);
                }
            });

            definition.dialog.on('hide', function(ev2) {
                var elt = new CKEDITOR.dom.element(ev2.sender.imageElement.$);
                if (elt.isVisible()) {
                    elt.setAttributes(rf.attrdata);
                    ImpCkeditorImgs.add(ev.editor, elt.$);
                }
            });
        }
    });
});

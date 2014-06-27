/**
 * Add image upload capability to the image plugin.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2013-2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

document.observe('dom:loaded', function() {
    CKEDITOR.on('dialogDefinition', function(ev) {
        var params, rf, upload,
            definition = ev.data.definition;

        if (ev.data.name == 'image') {
            upload = definition.getContents('Upload');

            rf = upload.add({
                commit: function(type, element, internalCommit) {
                    if (type == 1 && rf.attrdata) {
                        element.setAttributes(rf.attrdata);
                        delete rf.attrdata;
                    }
                },
                hidden: true,
                id: 'related_fields',
                type: 'text'
            });

            params = $H({ composeCache: $F(DimpCompose.getCacheElt()) });
            HordeCore.addRequestParams(params);

            upload.get('uploadButton').filebrowser = {
                action: 'QuickUpload',
                onSelect: function(fileUrl, data) {
                    if (!Object.isString(data)) {
                        rf.attrdata = data;
                    }
                    var c;
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
                var a;
            });

            definition.dialog.on('ok', function(ev2) {
                var b;
            });
        }
    });
});

/**
 * Managing HTML signature in the preferences UI.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpHtmlSignaturePrefs = {

    // Variables defined by other code: editor, sigs

    changeIdentity: function(e)
    {
        switch (e.memo.pref) {
        case 'signature_html_select':
            if (this.editor) {
                this.editor.setData(this.sigs[e.memo.i]);
            } else {
                this.changeIdentity.bind(this, e).delay(0.1);
            }
            break;
        }
    },

    onDomLoad: function()
    {
        /* Add ability to "upload" image to editor without using server. */
        CKEDITOR.on('dialogDefinition', function(ev) {
            var definition = ev.data.definition,
                button, upload;

            if (ev.data.name == 'image') {
                upload = definition.getContents('Upload');
                upload.hidden = false;

                upload.get('upload').label = ev.editor.lang.common.upload;

                button = upload.get('uploadButton');
                button.label = ev.editor.lang.common.upload;
                button.onClick = function(ev2) {
                    var f = ev2.data.dialog.getContentElement('Upload', 'upload').getInputElement().$.files, fr;

                    if (f.length) {
                        fr = new FileReader();
                        fr.onload = function(e) {
                            var d = definition.dialog;
                            d.getContentElement('info', 'txtUrl').setValue(e.target.result);
                            d.selectPage('info');
                        };
                        fr.readAsDataURL(f[0]);
                    }
                };
                button.type = 'button';

                /* Add shortcut to Upload tab in first dialog tab. */
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
            }
        });

        this.editor = new IMP_Editor('signature_html', IMP.ckeditor_config);
    }

};

document.observe('dom:loaded', ImpHtmlSignaturePrefs.onDomLoad.bind(ImpHtmlSignaturePrefs));
document.observe('HordeIdentitySelect:change', ImpHtmlSignaturePrefs.changeIdentity.bindAsEventListener(ImpHtmlSignaturePrefs));

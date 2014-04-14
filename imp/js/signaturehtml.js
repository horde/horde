/**
 * Provides the javascript for managing HTML signature in the preferences UI.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ImpHtmlSignaturePrefs = {

    // Variables defined by other code: ready, sigs

    changeIdentity: function(e)
    {
        switch (e.memo.pref) {
        case 'signature_html_select':
            if (this.ready) {
                CKEDITOR.instances.signature_html.setData(this.sigs[e.memo.i]);
            } else {
                this.changeIdentity.bind(this, e).delay(0.1);
            }
            break;
        }
    },

    onDomLoad: function()
    {
        CKEDITOR.on('instanceReady', function(e) {
            this.ready = true;
        }.bind(this));
        CKEDITOR.replace('signature_html', Object.clone(IMP.ckeditor_config));
    }

};

document.observe('dom:loaded', ImpHtmlSignaturePrefs.onDomLoad.bind(ImpHtmlSignaturePrefs));
document.observe('HordeIdentitySelect:change', ImpHtmlSignaturePrefs.changeIdentity.bindAsEventListener(ImpHtmlSignaturePrefs));

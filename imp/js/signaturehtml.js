/**
 * Provides the javascript for managing HTML signature in the preferences UI.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpHtmlSignaturePrefs = {

    // Variables defined by other code: ready, sigs

    changeIdentity: function(e)
    {
        switch (e.memo.pref) {
        case 'signature_html_select':
            if (this.ready) {
                CKEDITOR.instances['signature_html'].setData(this.sigs[e.memo.i]);
            } else {
                this.changeIdentity.bind(this, e).defer();
            }
            break;
        }
    }

};

CKEDITOR.on('instanceReady', function(e) { ImpHtmlSignaturePrefs.ready = true; });
document.observe('HordeIdentitySelect:change', ImpHtmlSignaturePrefs.changeIdentity.bindAsEventListener(ImpHtmlSignaturePrefs));

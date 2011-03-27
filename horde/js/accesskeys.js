/**
 * Accesskeys javascript file.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */
var AccessKeys = {

    macos: navigator.appVersion.indexOf('Mac') !=- 1,

    elements: [],

    replace: function()
    {
        $$('*[accesskey]').each(function(elm) {
            this.elements[elm.readAttribute('accesskey').toUpperCase()] = elm;
            elm.writeAttribute('accesskey', null);
        }, this);
        document.observe('keydown', this.keydownHandler.bindAsEventListener(this));
    },

    keydownHandler: function(e)
    {
        var elt, evt, key;

        if ((this.macos && e.ctrlKey) ||
            (!this.macos && e.altKey && !e.ctrlKey)) {
            key = String.fromCharCode(e.keyCode || e.charCode).sub('"', '\\"');
            if (elt = this.elements[key.toUpperCase()]) {
                e.stop();
                elt.focus();

                // Trigger a mouse event on the accesskey element.
                if (elt.tagName == 'INPUT') {
                    // NOOP
                } else if (elt.match('A') && elt.onclick) {
                    elt.onclick();
                } else if (document.createEvent) {
                    evt = document.createEvent('MouseEvents');
                    evt.initMouseEvent('click', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
                    elt.dispatchEvent(evt);
                } else {
                    evt = document.createEventObject();
                    elt.fireEvent('onclick', evt);
                }
            }
        }
    }
};

document.observe('dom:loaded', AccessKeys.replace.bind(AccessKeys));

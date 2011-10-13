/**
 * Accesskeys javascript file.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */
var AccessKeys = {

    macos: navigator.appVersion.indexOf("Mac") !=- 1,

    keydownHandler: function(e)
    {
        var elt, elts, evt, key, href;

        if ((this.macos && e.ctrlKey) ||
            (!this.macos && e.altKey && !e.ctrlKey)) {
            // Need to search for both upper and lowercase value
            key = String.fromCharCode(e.keyCode || e.charCode).sub('"', '\\"');;
            elts = $$('[accesskey="' + key.toUpperCase() + '"]');
            if (key.toUpperCase() != key.toLowerCase()) {
                elts = elts.concat($$('[accesskey="' + key.toLowerCase() + '"]'));
            }

            if (elt = elts.first()) {
                // Remove duplicate accesskeys
                if (elts.size() > 1) {
                    elts.slice(1).invoke('writeAttribute', 'accesskey', null);
                }

                e.stop();

                if (Prototype.Browser.Opera && elt.tagName == 'LABEL') {
                    elt = $(elt.readAttribute('for'));
                    if (!elt) {
                        return;
                    }
                }

                try {
                    elt.focus();
                } catch (e) {
                }

                if (navigator.userAgent.indexOf('Chrome/') > -1 && !this.macos &&
                    elt.tagName == 'A') {
                    return;
                }

                // Trigger a mouse event on the accesskey element.
                if (elt.tagName == 'INPUT') {
                    // NOOP
                } else if (elt.match('A') && elt.onclick) {
                    elt.onclick();
                } else if (elt.match('A') && Prototype.Browser.IE &&
                           (href = elt.readAttribute('href')) &&
                           href.substr(0, 1) != '#') {
                    if (href.indexOf('javascript:') == 0) {
                        eval(href.substr(11));
                    } else {
                        window.open(href);
                    }
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

document.observe('keydown', AccessKeys.keydownHandler.bindAsEventListener(AccessKeys));

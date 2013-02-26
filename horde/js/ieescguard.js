/**
 * Javascript code for attaching an onkeydown listener to textarea and
 * text input elements to prevent loss of data when the user hits the
 * ESC key.
 *
 * Requires prototypejs 1.6.0.2+.
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 */

if (Prototype.Browser.IE) {
    document.observe('keydown', function(e) {
        var elt = e.element();

        if ((e.keyCode || e.charCode) == Event.KEY_ESC &&
            (elt.match('TEXTAREA') || elt.match('INPUT[type="text"]'))) {
            e.stop();
        }
    });
}

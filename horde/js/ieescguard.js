/**
 * Javascript code for attaching an onkeydown listener to textarea and
 * text input elements to prevent loss of data when the user hits the
 * ESC key.
 *
 * Requires prototypejs 1.6.0.2+.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/* Finds all text inputs (input type="text") and textarea tags, and
 * attaches the onkeydown listener to them to avoid ESC clearing the
 * text. */
if (Prototype.Browser.IE) {
    document.observe('dom:loaded', function() {
        [ $$('TEXTAREA'), $$('INPUT[type="text"]') ].flatten().each(function(t) {
            t.observe('keydown', function(e) { return e.keyCode != 27; });
        });
    });
}

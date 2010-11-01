/**
 * Javascript code for finding all tables with classname "striped" and
 * dynamically striping their row colors.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Matt Warden <mwarden@gmail.com>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

window.Horde = window.Horde || {};

Horde.stripeAllElements = function()
{
    $$('.striped').each(this.stripeElement.bind(this));
};

Horde.stripeElement = function(elt)
{
    var classes = [ 'rowEven', 'rowOdd' ],
        e = $(elt).childElements(),
        i = 0;

    if (elt.tagName == 'TABLE') {
        // Tables can have more than one tbody element; get all child
        // tbody tags and interate through them.
        e.each(this.stripeElement.bind(this));
    } else {
        // Toggle the classname of any child node that is an element.
        e.each(function(c) {
            c.removeClassName(classes[i]).addClassName(classes[++i % 2]);
        });
    }
};

/* We have to wait for the full DOM to be loaded to ensure we don't
 * miss anything. */
document.observe('dom:loaded', Horde.stripeAllElements.bind(Horde));

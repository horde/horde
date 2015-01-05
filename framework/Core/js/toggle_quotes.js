/**
 * Provides the javascript for toggle quotes (Highlightquotes text filter).
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2011-2015 Horde LLC
 * @license    LGPL-2.1 (http://www.horde.org/licenses/lgpl21)
 */

var Horde_ToggleQuotes = {

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element();

        while (Object.isElement(elt)) {
            if (elt.match('SPAN.toggleQuoteShow')) {
                [ elt, elt.next() ].invoke('toggle');
                elt.next(1).blindDown({ duration: 0.2, queue: { position: 'end', scope: 'showquote', limit: 2 } });
            } else if (elt.match('SPAN.toggleQuoteHide')) {
                [ elt, elt.previous() ].invoke('toggle');
                elt.next().blindUp({ duration: 0.2, queue: { position: 'end', scope: 'showquote', limit: 2 } });
            }

            elt = elt.up();
        }
    }

};

document.observe('click', Horde_ToggleQuotes.clickHandler.bindAsEventListener(Horde_ToggleQuotes));

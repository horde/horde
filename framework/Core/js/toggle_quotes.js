/**
 * Provides the javascript for toggle quotes (Highlightquotes text filter).
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */

var Horde_ToggleQuotes = {

    onDomLoad: function()
    {
        document.observe('click', this._clickHandler.bindAsEventListener(this));
    },

    _clickHandler: function(e)
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

document.observe('dom:loaded', Horde_ToggleQuotes.onDomLoad.bind(Horde_ToggleQuotes));

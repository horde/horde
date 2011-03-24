/**
 * Provides the javascript for the thread.php script (standard view).
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @package  IMP
 */

var ImpThread = {

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

document.observe('dom:loaded', ImpThread.onDomLoad.bind(ImpThread));

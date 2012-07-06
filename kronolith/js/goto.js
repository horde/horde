/**
 * goto.js - Menu goto handling.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Kronolith
 */

var KronolithGoto =
{
    // Variables defined externally: dayurl, monthurl, weekurl, yearurl

    calendarSelect: function(e, type)
    {
        // Only trigger if this is the goto menu.
        if (!e.findElement('A.kgotomenu')) {
            return;
        }

        var q, url,
            params = $H({ date: e.memo.getFullYear() + (e.memo.getMonth() + 1).toPaddedString(2) + (e.memo.getDate()).toPaddedString(2) });

        switch (type) {
        case 'day':
            url = this.dayurl;
            break;

        case 'month':
            url = this.monthurl;
            break;

        case 'week':
            url = this.weekurl;
            break;

        case 'year':
            url = this.yearurl;
            break;
        }

        q = url.indexOf('?');
        if (q != -1) {
            params.update(url.toQueryParams());
            url = url.substring(0, q);
        }

        window.location = url + '?' + params.toQueryString();
    },

    onDomLoad: function()
    {
        $('horde-sidebar').down('A.kgotomenu').observe('click', function(e) {
            Horde_Calendar.open(e.element(), Object.isUndefined(window.KronolithDate) ? new Date() : window.KronolithDate);
        });
    }

};

document.observe('dom:loaded', KronolithGoto.onDomLoad.bind(KronolithGoto));
document.observe('Horde_Calendar:select', KronolithGoto.calendarSelect.bindAsEventListener(KronolithGoto, 'day'));
document.observe('Horde_Calendar:selectMonth', KronolithGoto.calendarSelect.bindAsEventListener(KronolithGoto, 'month'));
document.observe('Horde_Calendar:selectWeek', KronolithGoto.calendarSelect.bindAsEventListener(KronolithGoto, 'week'));
document.observe('Horde_Calendar:selectYear', KronolithGoto.calendarSelect.bindAsEventListener(KronolithGoto, 'year'));

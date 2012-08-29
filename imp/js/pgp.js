/**
 * Provides javascript features for the PGP preferences screen.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ImpPgp = {

    replaceDate: function(d)
    {
        $('generate_expire_date').setValue(d.getTime()).next('SPAN').update(this.months[d.getMonth()] + ' ' + d.getDate() + ', ' + (d.getYear() + 1900));
    },

    clickHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'generate_expire':
            e.element().next().toggle();
            break;

        default:
            if (e.element().hasClassName('calendarImg')) {
                Horde_Calendar.open(elt.identify(), new Date(Number($('generate_expire_date').getValue())));
                e.memo.stop();
            }
            break;
        }
    },

    calendarSelectHandler: function(e)
    {
        this.replaceDate(e.memo);
    },

    onDomLoad: function()
    {
        HordeCore.initHandler('click');

        var now = new Date();
        now.setFullYear(now.getFullYear() + 1);
        this.replaceDate(now);
    }

};

document.observe('dom:loaded', ImpPgp.onDomLoad.bind(ImpPgp));
document.observe('HordeCore:click', ImpPgp.clickHandler.bindAsEventListener(ImpPgp))
document.observe('Horde_Calendar:select', ImpPgp.calendarSelectHandler.bindAsEventListener(ImpPgp));

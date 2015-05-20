/**
 * PGP preferences screen.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpPgp = {

    replaceDate: function(d)
    {
        $('generate_expire_date').setValue(d.getTime()).next('SPAN').update(this.months[d.getMonth()] + ' ' + d.getDate() + ', ' + (d.getFullYear()));
    },

    clickHandler: function(e)
    {
        var elt = e.element();

        switch (elt.readAttribute('id')) {
        case 'generate_expire':
            elt.next().toggle();
            break;

        default:
            if (elt.hasClassName('calendarImg')) {
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
document.observe('HordeCore:click', ImpPgp.clickHandler.bindAsEventListener(ImpPgp));
document.observe('Horde_Calendar:select', ImpPgp.calendarSelectHandler.bindAsEventListener(ImpPgp));

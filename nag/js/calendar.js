/**
 * calendar.js - Calendar related javascript.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @package  Nag
 */

var NagCalendar =
{
    calendarSelect: function(e)
    {
        var prefix;

        switch (e.element().identify()) {
        case 'dueimg':
            prefix = 'due';
            break;

        case 'startimg':
            prefix = 'start';
            break;

        default:
            return;
        }

        $(prefix + '_year').setValue(e.memo.getFullYear());
        $(prefix + '_month').setValue(e.memo.getMonth() + 1);
        $(prefix + '_day').setValue(e.memo.getDate());

        this.doAction(prefix + '_year');
    },

    updateWday: function(p)
    {
        $(p + '_wday').update('(' + Horde_Calendar.fullweekdays[this.getFormDate(p).getDay()] + ')');
    },

    getFormDate: function(p)
    {
        return new Date($F(p + '_year'), $F(p + '_month') - 1, $F(p + '_day'));
    },

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(),
            id = elt.readAttribute('id');

        switch (id) {
        case 'dueimg':
        case 'startimg':
            Horde_Calendar.open(elt, this.getFormDate(id.slice(0, -3)));
            e.stop();
            break;

        case 'due_am_pm_am':
        case 'due_am_pm_am_label':
        case 'due_am_pm_pm':
        case 'due_am_pm_pm_label':
            $('due_type_specified').setValue(1);
            break;
        }
    },

    changeHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'due_day':
        case 'due_month':
        case 'due_year':
            this.updateWday('due');
            // Fall-through

        case 'due_hour':
        case 'due_minute':
            $('due_type_specified').setValue(1);
            break;

        case 'start_day':
        case 'start_month':
        case 'start_year':
            this.updateWday('start');
            break;
        }
    },

    onDomLoad: function()
    {
        this.updateWday('due');
        this.updateWday('start');

        $('nag_taskform_active').observe('click', this.clickHandler.bindAsEventListener(this));
        $('nag_taskform_active').observe('change', this.changeHandler.bindAsEventListener(this));
    }

};

document.observe('dom:loaded', NagCalendar.onDomLoad.bind(NagCalendar));
document.observe('Horde_Calendar:select', NagCalendar.calendarSelect.bindAsEventListener(NagCalendar));

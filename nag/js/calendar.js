/**
 * calendar.js - Calendar related javascript.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Nag
 */

var NagCalendar =
{
    calendarSelect: function(e)
    {
        var prefix, radio;

        switch (e.element().identify()) {
        case 'dueimg':
            prefix = 'due';
            radio = 'due_type_specified';
            break;

        case 'startimg':
            prefix = 'start';
            radio = 'start_date_specified';
            break;

        default:
            return;
        }

        $(prefix + '_date').setValue(e.memo.toString(Nag.conf.date_format));
        $(radio).setValue(1);

        this.updateWday(prefix);
    },

    updateWday: function(p)
    {
        $(p + '_wday').update('(' + Horde_Calendar.fullweekdays[this.getFormDate(p).getDay()] + ')');
    },

    getFormDate: function(p)
    {
        return Date.parseExact($F(p + '_date'), Nag.conf.date_format);
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
        case 'due_date':
            this.updateWday('due');
            // Fall-through

        case 'due_time':
            $('due_type_specified').setValue(1);
            break;

        case 'start_date':
            this.updateWday('start');
            // Fall-through

        case 'start_time':
            $('start_date_specified').setValue(1);
            break;

        case 'alarm_unit':
        case 'alarm_value':
            $('alarmon').setValue(1);
            break;
        }
    },

    onDomLoad: function()
    {
        this.updateWday('due');
        this.updateWday('start');

        $('nag_form_task_active').observe('click', this.clickHandler.bindAsEventListener(this));
        $('nag_form_task_active').observe('change', this.changeHandler.bindAsEventListener(this));
    }
};

document.observe('dom:loaded', NagCalendar.onDomLoad.bind(NagCalendar));
document.observe('Horde_Calendar:select', NagCalendar.calendarSelect.bindAsEventListener(NagCalendar));

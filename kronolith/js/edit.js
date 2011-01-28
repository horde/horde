/**
 * edit.js - Base application logic.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @package  Kronolith
 */

var KronolithEdit =
{
    calendarSelect: function(e)
    {
        var prefix;

        switch (e.element().identify()) {
        case 'end_img':
            prefix = 'end';
            break;

        case 'recur_end_img':
            prefix = 'recur_end';
            break;

        case 'start_img':
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
        case 'allday':
        case 'allday_label':
            this.doAction('allday');
            break;

        case 'am':
        case 'am_label':
        case 'pm':
        case 'pm_label':
            this.doAction('am');
            break;

        case 'attendees_button':
            Horde.popup({
                params: Object.toQueryString({
                    date: (('000' + $F('start_year')).slice(-4) + ('0' + $F('start_month')).slice(-2) + ('0' + $F('start_day')).slice(-2) + ('0' + $F('start_hour')).slice(-2) + ('0' + $F('start_min')).slice(-2) + '00'),
                    enddate: (('000' + $F('end_year')).slice(-4) + ('0' + $F('end_month')).slice(-2) + ('0' + $F('end_day')).slice(-2) + ('0' + $F('end_hour')).slice(-2) + ('0' + $F('end_min')).slice(-2) + '00')
                }),
                url: elt.readAttribute('href')
            });
            e.stop();
            break;

        case 'eam':
        case 'eam_label':
        case 'epm':
        case 'epm_label':
            this.doAction('eam');
            break;

        case 'edit_current':
        case 'edit_future':
            $('start_year').setValue(parseInt($F('recur_ex').substr(0, 4), 10));
            $('start_month').selectedIndex = parseInt($F('recur_ex').substr(4, 2), 10) - 1;
            $('start_day').selectedIndex = parseInt($F('recur_ex').substr(6, 2), 10) - 1;

            this.updateWday('start');
            this.updateEndDate();

            $('recur_weekly_interval').adjacent('.checkbox').invoke('setValue', 0);
            break;

        case 'end_img':
        case 'recur_end_img':
        case 'start_img':
            Horde_Calendar.open(elt, this.getFormDate(id.slice(0, -4)));
            e.stop();
            break;

        case 'mo':
        case 'tu':
        case 'we':
        case 'th':
        case 'fr':
        case 'sa':
        case 'su':
            this.setInterval('recurweekly', 'recur_weekly_interval');
            this.setRecur(2);
            break;

        case 'nooverwrite':
        case 'yesoverwrite':
            if ($F('nooverwrite')) {
                $('notification_options').hide();
            } else {
                $('notification_options').show();
                $('yesalarm').setValue(1);
            }
            break;

        case 'recurdaily':
        case 'recurdaily_label':
            this.setInterval('recurdaily', 'recur_daily_interval');
            break;

        case 'recurmonthday':
        case 'recurmonthday_label':
            this.setInterval('recurmonthday', 'recur_day_of_month_interval');
            break;

        case 'recurmonthweek':
        case 'recurmonthweek_label':
            this.setInterval('recurmonthweek', 'recur_week_of_month_interval');
            break;

        case 'recurnone':
            this.clearFields(0);
            break;

        case 'recurweekly':
        case 'recurweekly_label':
            this.setInterval('recurweekly', 'recur_weekly_interval');
            break;

        case 'recuryear':
        case 'recuryear_label':
            this.setInterval('recuryear', 'recur_yearly_interval');
            break;

        case 'recuryearday':
        case 'recuryearday_label':
            this.setInterval('recuryearday', 'recur_yearly_day_interval');
            break;

        case 'recuryearweekday':
        case 'recuryearweekday_label':
            this.setInterval('recuryearweekday', 'recur_yearly_weekday_interval');
            break;

        default:
            if (elt.readAttribute('name') == 'resetButton') {
                $('eventform').reset();
                this.updateWday('start');
                this.updateWday('end');
            } else {
                if (!elt.match('TD')) {
                    elt = elt.up('TD');
                }
                if (elt && elt.hasClassName('toggle')) {
                    elt.down().toggle().next().toggle();
                    $('section' + elt.identify().substr(6)).toggle();
                }
            }
            break;
        }
    },

    changeHandler: function(e)
    {
        this.doAction(e.element().readAttribute('id'));
    },

    keypressHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'recur_daily_interval':
            this.setRecur(1);
            break;

        case 'recur_weekly_interval':
            this.setRecur(2);
            break;

        case 'recur_day_of_month_interval':
            this.setRecur(3);
            break;

        case 'recur_week_of_month_interval':
            this.setRecur(4);
            break;

        case 'recur_yearly_interval':
            this.setRecur(5);
            break;

        case 'recur_yearly_day_interval':
            this.setRecur(6);
            break;

        case 'recur_yearly_weekday_interval':
            this.setRecur(7);
            break;
        }
    },

    doAction: function(id)
    {
        var endDate, endHour, duration, durHour, durMin, failed, startDate,
            startHour;

        switch (id) {
        case 'allday':
            if ($F('allday')) {
                if (KronolithVar.twentyFour) {
                    $('start_hour').selectedIndex = 0;
                } else {
                    $('start_hour').selectedIndex = 11;
                    $('am').setValue(1);
                }
                $('start_min').setValue(0);
                $('dur_day').setValue(1);
                $('dur_hour').setValue(0);
                $('dur_min').setValue(0);
            }
            this.updateEndDate();
            $('duration').setValue(1);
            break;

        case 'am':
            $('allday').setValue(0);
            this.updateEndDate();
            break;

        case 'dur_day':
        case 'dur_hour':
        case 'dur_min':
            $('allday').setValue(0);
            this.updateEndDate();
            $('end').setValue(1);
            break;

        case 'eam':
        case 'epm':
            break;

        case 'end_year':
        case 'end_month':
        case 'end_day':
            this.updateWday('end');
            // Fall-through

        case 'end_hour':
        case 'end_min':
        case 'pm':
            $('end').setValue(1);

            startHour = this.convertTo24Hour(parseInt($F('start_hour'), 10), 'pm');
            endHour = this.convertTo24Hour(parseInt($F('end_hour'), 10), 'epm');
            startDate = Date.UTC(
                $F('start_year'),
                $F('start_month') - 1,
                $F('start_day'),
                startHour,
                $F('start_min')
            );
            endDate = Date.UTC(
                $F('end_year'),
                $F('end_month') - 1,
                $F('end_day'),
                endHour,
                $F('end_min')
            );

            if (endDate < startDate) {
                if (KronolithVar.twentyFour &&
                    $F('start_year') == $F('end_year') &&
                    $F('start_month') == $F('end_month') &&
                    $F('start_day') == $F('end_day') &&
                    !$F('pm') && !$F('epm')) {
                    /* If the end hour is marked as the (default) AM, and
                     * the start hour is also AM, automatically default
                     * the end hour to PM if the date is otherwise the
                     * same - assume that the user wants a 9am-2pm event
                     * (for example), instead of throwing an error. */

                    // Toggle the end date to PM.
                    $('epm').checked = true;

                    // Recalculate end time
                    endHour = this.convertTo24Hour(parseInt($F('end_hour'), 10), 'epm');
                    endDate = Date.UTC(
                        $F('end_year'),
                        $F('end_month') - 1,
                        $F('end_day'),
                        endHour,
                        $F('end_min')
                    );
                } else {
                    alert(KronolithText.enddate_error);
                    endDate = startDate;
                    failed = true;
                }
            }

            duration = (endDate - startDate) / 1000;
            $('dur_day').setValue(Math.floor(duration / 86400));
            duration %= 86400;

            durHour = Math.floor(duration / 3600);
            duration %= 3600;

            durMin = Math.floor(duration / 60 / 5);

            $('dur_hour').selectedIndex = durHour;
            $('dur_min').selectedIndex = durMin;
            $('allday').setValue(false);

            if (failed) {
                this.updateEndDate();
            }
            break;

        case 'recur_end_year':
        case 'recur_end_month':
        case 'recur_end_day':
            $('recur_end_type').setValue(1);
            this.updateWday('recur_end');
            break;

        case 'recur_daily_interval':
            this.setRecur(1);
            break;

        case 'recur_weekly_interval':
            this.setRecur(2);
            break;

        case 'recur_day_of_month_interval':
            this.setRecur(3);
            break;

        case 'recur_week_of_month_interval':
            this.setRecur(4);
            break;

        case 'recur_yearly_interval':
            this.setRecur(5);
            break;

        case 'recur_yearly_day_interval':
            this.setRecur(6);
            break;

        case 'recur_yearly_weekday_interval':
            this.setRecur(7);
            break;

        case 'start_year':
        case 'start_month':
        case 'start_day':
            this.updateWday('start');
            // Fall-through

        case 'start_hour':
        case 'start_min':
            $('allday').setValue(0);
            this.updateEndDate();
            break;
        }
    },

    updateEndDate: function()
    {
        var endHour, endYear, msecs,
            startHour = this.convertTo24Hour(parseInt($F('start_hour'), 10), 'pm'),
            startDate = new Date(
                $F('start_year'),
                $F('start_month') - 1,
                $F('start_day'),
                startHour,
                $F('start_min')
            ),
            endDate = new Date(),
            minutes = $F('dur_day') * 1440;

        minutes += $F('dur_hour') * 60;
        minutes += parseInt($F('dur_min'));
        msecs = minutes * 60000;

        endDate.setTime(startDate.getTime() + msecs);

        endYear = endDate.getFullYear();

        $('end_year').setValue(endYear);
        $('end_month').selectedIndex = endDate.getMonth();
        $('end_day').selectedIndex = endDate.getDate() - 1;

        endHour = endDate.getHours()
        if (!KronolithVar.twentyFour) {
            if (endHour < 12) {
                if (endHour == 0) {
                    endHour = 12;
                }
                $('eam').setValue(1);
            } else {
                if (endHour > 12) {
                    endHour -= 12;
                }
                $('epm').setValue(1);
            }
            endHour -= 1;
       }

        $('end_hour').selectedIndex = endHour;
        $('end_min').selectedIndex = endDate.getMinutes() / 5;

        this.updateWday('end');
    },

    // Converts a 12 hour based number to its 24 hour format
    convertTo24Hour: function(val, elt)
    {
        if (!KronolithVar.twentyFour) {
            if ($F(elt)) {
                if (val != 12) {
                    val += 12;
                }
            } else if (val == 12) {
                val = 0;
            }
        }

        return val;
    },

    setInterval: function(elt, id)
    {
        if (!$F(id)) {
            $(elt).setValue(1);
        }

        switch (id) {
        case 'recur_daily_interval':
            KronolithEdit.clearFields(1);
            break;

        case 'recur_weekly_interval':
            KronolithEdit.clearFields(2);
            break;

        case 'recur_day_of_month_interval':
            KronolithEdit.clearFields(3);
            break;

        case 'recur_week_of_month_interval':
            KronolithEdit.clearFields(4);
            break;

        case 'recur_yearly_interval':
            KronolithEdit.clearFields(5);
            break;
        }
    },

    setRecur: function(index)
    {
        document.eventform.recur[index].checked = true;
        KronolithEdit.clearFields(index);
    },

    clearFields: function(index)
    {
        if (index != 1) {
            $('recur_daily_interval').setValue('');
        }
        if (index != 2) {
            $('recur_weekly_interval').setValue('');
            $('recur_weekly_interval').adjacent('.checkbox').invoke('setValue', 0);
        }
        if (index != 3) {
            $('recur_day_of_month_interval').setValue('');
        }
        if (index != 4) {
            $('recur_week_of_month_interval').setValue('');
        }
        if (index != 5) {
            $('recur_yearly_interval').setValue('');
        }
    },

    onDomLoad: function()
    {
        this.updateWday('start');
        this.updateWday('end');
        if ($('recur_end_wday')) {
            this.updateWday('recur_end');
        }
        $('eventform').observe('click', this.clickHandler.bindAsEventListener(this));
        $('eventform').observe('change', this.changeHandler.bindAsEventListener(this));
        $('eventform').observe('keypress', this.keypressHandler.bindAsEventListener(this));

        $('title').focus();
    }

};

document.observe('dom:loaded', KronolithEdit.onDomLoad.bind(KronolithEdit));
document.observe('Horde_Calendar:select', KronolithEdit.calendarSelect.bindAsEventListener(KronolithEdit));

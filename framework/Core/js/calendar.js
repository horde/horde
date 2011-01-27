/**
 * Horde javascript calendar widget.
 *
 * Custom Events:
 * --------------
 * Horde_Calendar:select
 *   params: Date object
 *   Fired when a date is selected.
 *
 * Horde_Calendar:selectMonth
 *   params: Date object
 *   Fired when a month is selected.
 *
 * Horde_Calendar:selectWeek
 *   params: Date object
 *   Fired when a week is selected.
 *
 * Horde_Calendar:selectYear
 *   params: Date object
 *   Fired when a year is selected.
 *
 * @category Horde
 * @package  Core
 */

var Horde_Calendar =
{
    // Variables set externally: click_month, click_week, click_year,
    //                           firstDayOfWeek, fullweekdays, months,
    //                           weekdays
    // Variables defaulting to null: date, month, openDate, trigger, year

    open: function(trigger, data)
    {
        var date = data ? data : new Date();

        this.openDate = date.getTime();
        this.trigger = $(trigger);
        this.draw(this.openDate, true);
    },

    /**
     * Days in the month (month is a zero-indexed javascript month).
     */
    daysInMonth: function(month, year)
    {
        switch (month) {
        case 3:
        case 5:
        case 8:
        case 10:
            return 30;

        case 1:
            return (year % 4 == 0 && (year % 100 != 0 || year % 400 == 0))
                ? 29
                : 28;

        default:
            return 31;
        }
    },

    weeksInMonth: function(month, year)
    {
        var firstWeekDays, weeks,
            firstOfMonth = (new Date(year, month, 1)).getDay();

        if ((this.firstDayOfWeek == 1 && firstOfMonth == 0) ||
            (this.firstDayOfWeek == 0 && firstOfMonth == 6)) {
            firstWeekDays = 7 - firstOfMonth + this.firstDayOfWeek;
            weeks = 1;
        } else {
            firstWeekDays = this.firstDayOfWeek - firstOfMonth;
            weeks = 0;
        }

        firstWeekDays %= 7;

        return Math.ceil((this.daysInMonth(month, year) - firstWeekDays) / 7) + weeks;
    },

    // http://javascript.about.com/library/blstdweek.htm
    weekOfYear: function(d)
    {
        var newYear = new Date(d.getFullYear(), 0, 1),
            day = newYear.getDay();
        if (this.firstDayOfWeek != 0) {
            day = ((day + (7 - this.firstDayOfWeek)) % 7);
        }
        return Math.ceil((((d - newYear) / 86400000) + day + 1) / 7);
    },

    draw: function(timestamp, init)
    {
        this.date = new Date(timestamp);
        this.month = this.date.getMonth();
        this.year = this.date.getFullYear();

        var cell, i, i_max, p, row, startOfView, vp,
            count = 1,
            div = $('hordeCalendar'),
            tbody = div.down('TBODY'),

            // Requires init above
            daysInMonth = this.daysInMonth(this.month, this.year),
            daysInView = this.weeksInMonth(this.month, this.year) * 7,
            firstOfMonth = (new Date(this.year, this.month, 1)).getDay(),

            // Cache today and open date.
            today = new Date(),
            today_year = today.getFullYear(),
            today_month = today.getMonth(),
            today_day = today.getDate(),
            open = new Date(this.openDate),
            open_year = open.getFullYear(),
            open_month = open.getMonth(),
            open_day = open.getDate();

        if (this.firstDayOfWeek == 0) {
            startOfView = 1 - firstOfMonth;
        } else {
            // @TODO Adjust this for days other than Monday.
            startOfView = (firstOfMonth == 0)
                ? -5
                : 2 - firstOfMonth;
        }

        div.down('.hordeCalendarYear').update(this.year);
        div.down('.hordeCalendarMonth').update(this.months[this.month]);
        tbody.update('');

        for (i = startOfView, i_max = startOfView + daysInView; i < i_max; ++i) {
            if (count == 1) {
                row = new Element('TR');
                if (this.click_week) {
                    row.insert(new Element('TD').insert(new Element('A', { className: 'hordeCalendarWeek' }).insert(this.weekOfYear(new Date(this.year, this.month, (i < 1) ? 1 : i)))));
                }
            }

            cell = new Element('TD');

            if (i < 1 || i > daysInMonth) {
                cell.addClassName('hordeCalendarEmpty');
                row.insert(cell);
            } else {
                if (today_year == this.year &&
                    today_month == this.month &&
                    today_day == i) {
                    cell.writeAttribute({ className: 'hordeCalendarToday' });
                }

                if (open_year == this.year &&
                    open_month == this.month &&
                    open_day == i) {
                    cell.addClassName('hordeCalendarCurrent');
                }

                row.insert(cell.insert(new Element('A', { className: 'hordeCalendarDay', href: '#' }).insert(i)));
            }

            if (count == 7) {
                tbody.insert(row);
                count = 0;
            }
            ++count;
        }

        if (count > 1) {
            tbody.insert(row);
        }

        div.show();

        // Position the popup every time in case of a different input,
        // window sizing changes, etc.
        if (init) {
            p = this.trigger.cumulativeOffset();
            vp = document.viewport.getDimensions();

            if (p.left + div.offsetWidth > vp.width) {
                div.setStyle({ left: (vp.width - 10 - div.offsetWidth) + 'px' });
            } else {
                div.setStyle({ left: p.left + 'px' });
            }

            if (p.top + div.offsetHeight > vp.height) {
                div.setStyle({ top: (vp.height - 10 - div.offsetHeight) + 'px' });
            } else {
                div.setStyle({ top: p.top + 'px' });
            }
        }

        // IE 6 only.
        if (Prototype.Browser.IE && !window.XMLHttpRequest) {
            iframe = $('hordeCalendarIframe');
            if (!iframe) {
                iframe = new Element('IFRAME', { name: 'hordeCalendarIframe', id: 'hordeCalendarIframe', src: 'javascript:false;', scrolling: 'no', frameborder: 0 }).hide();
                $(document.body).insert(iframe);
            }
            iframe.clonePosition(div).setStyle({
                position: 'absolute',
                display: 'block',
                zIndex: 1
            });
        }

        div.setStyle({ zIndex: 999 });
    },

    hideCal: function()
    {
        var iefix = $('hordeCalendarIframe');

        $('hordeCalendar').hide();

        if (iefix) {
            iefix.hide();
        }
    },

    changeYear: function(by)
    {
        this.draw((new Date(this.date.getFullYear() + by, this.date.getMonth(), 1)).getTime());
    },

    changeMonth: function(by)
    {
        var newMonth = this.date.getMonth() + by,
            newYear = this.date.getFullYear();

        if (newMonth == -1) {
            newMonth = 11;
            newYear -= 1;
        }

        this.draw((new Date(newYear, newMonth, 1)).getTime());
    },

    init: function()
    {
        var i, link, row,
            offset = this.click_week ? 1 : 0,
            thead = new Element('THEAD'),
            table = new Element('TABLE', { className: 'hordeCalendarPopup', cellSpacing: 0 }).insert(thead).insert(new Element('TBODY'));

        // Title bar.
        link = new Element('A', { href: '#', className: 'hordeCalendarClose rightAlign' }).insert('x');
        thead.insert(new Element('TR').insert(new Element('TD', { colspan: 6 + offset })).insert(new Element('TD').insert(link)));

        // Year.
        row = new Element('TR');
        link = new Element('A', { className: 'hordeCalendarPrevYear', href: '#' }).insert('&laquo;');
        row.insert(new Element('TD').insert(link));

        tmp = new Element('TD', { align: 'center', colspan: 5 + offset });
        if (this.click_year) {
            tmp.insert(new Element('A', { className: 'hordeCalendarYear' }));
        } else {
            tmp.addClassName('hordeCalendarYear');
        }
        row.insert(tmp);

        link = new Element('A', { className: 'hordeCalendarNextYear', href: '#' }).insert('&raquo;');
        row.insert(new Element('TD', { className: 'rightAlign' }).insert(link));

        thead.insert(row);

        // Month name.
        row = new Element('TR');
        link = new Element('A', { className: 'hordeCalendarPrevMonth', href: '#' }).insert('&laquo;');
        row.insert(new Element('TD').insert(link));

        tmp = new Element('TD', { align: 'center', colspan: 5 + offset });
        if (this.click_year) {
            tmp.insert(new Element('A', { className: 'hordeCalendarMonth' }));
        } else {
            tmp.addClassName('hordeCalendarMonth');
        }
        row.insert(tmp);

        link = new Element('A', { className: 'hordeCalendarNextMonth', href: '#' }).insert('&raquo;');
        row.insert(new Element('TD', { className: 'rightAlign' }).insert(link));

        thead.insert(row);

        // Weekdays.
        row = new Element('TR');
        if (this.click_week) {
            row.insert(new Element('TH'));
        }
        for (i = 0; i < 7; ++i) {
            row.insert(new Element('TH').insert(this.weekdays[(i + this.firstDayOfWeek) % 7]));
        }
        thead.insert(row);

        $(document.body).insert({ bottom: new Element('DIV', { id: 'hordeCalendar' }).setStyle({ position: 'absolute', 'z-index': 999 }).hide().insert(table) });

        $('hordeCalendar').observe('click', this.clickHandler.bindAsEventListener(this));
    },

    clickHandler: function(e)
    {
        var elt = e.element(), day;

        if (elt.hasClassName('hordeCalendarDay')) {
            this.hideCal();
            this.trigger.fire('Horde_Calendar:select', new Date(this.year, this.month, parseInt(elt.textContent || elt.innerText, 10)));
        } else if (elt.hasClassName('hordeCalendarClose')) {
            this.hideCal();
        } else if (elt.hasClassName('hordeCalendarPrevYear')) {
            this.changeYear(-1);
        } else if (elt.hasClassName('hordeCalendarNextYear')) {
            this.changeYear(1);
        } else if (elt.hasClassName('hordeCalendarPrevMonth')) {
            this.changeMonth(-1);
        } else if (elt.hasClassName('hordeCalendarNextMonth')) {
            this.changeMonth(1);
        } else if (this.click_year && elt.hasClassName('hordeCalendarYear')) {
            this.trigger.fire('Horde_Calendar:selectYear', new Date(this.year, this.month, 1));
            this.hideCal();
        } else if (this.click_month && elt.hasClassName('hordeCalendarMonth')) {
            this.trigger.fire('Horde_Calendar:selectMonth', new Date(this.year, this.month, 1));
            this.hideCal();
        } else if (this.click_week && elt.hasClassName('hordeCalendarWeek')) {
            day = elt.up('TR').down('A.hordeCalendarDay');
            this.trigger.fire('Horde_Calendar:selectWeek', new Date(this.year, this.month, day.textContent || day.innerText));
            this.hideCal();
        }

        e.stop();
    }

};

document.observe('dom:loaded', Horde_Calendar.init.bind(Horde_Calendar));

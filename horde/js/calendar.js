/**
 * Horde javascript calendar widget.
 *
 * Custom Events:
 * --------------
 * Horde_Calendar:select
 *   params: Date object
 */

var Horde_Calendar =
{
    // Variables set externally: firstDayOfWeek, fullweekdays, months,
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

            // We may need to adjust the number of days in the view if
            // we're starting weeks on Sunday.
            if (firstOfMonth == 0) {
                //daysInView -= 7;
            }

            if ((new Date(this.year, this.month, daysInMonth)).getDay() == 0) {
                daysInView += 7;
            }
        } else {
            // @TODO Adjust this for days other than Monday.
            startOfView = (firstOfMonth == 0)
                ? -5
                : 2 - firstOfMonth;
        }

        div.down('THEAD').down('TR', 1).down('TD', 1).update(this.year);
        div.down('THEAD').down('TR', 2).down('TD', 1).update(this.months[this.month]);
        tbody.update('');

        for (i = startOfView, i_max = startOfView + daysInView; i < i_max; ++i) {
            if (count == 1) {
                row = new Element('TR');
            }

            cell = new Element('TD');

            if (i < 1 || i > daysInMonth) {
                cell.addClassName('hordeCalendarEmpty');
                row.appendChild(cell);

                if (count == 7) {
                    tbody.insert(row);
                    count = 0;
                }

                ++count;
                continue;
            }

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

            row.insert(cell.insert(new Element('A', { href: '#' }).insert(i).observe('click', this.dayOnClick.bindAsEventListener(this))));

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
                document.body.appendChild(iframe);
            }
            iframe.clonePosition(div).setStyle({
                position: 'absolute',
                display: 'block',
                zIndex: 1
            });
            div.setStyle({ zIndex: 2 });
        }
    },

    dayOnClick: function(e)
    {
        this.hideCal();

        this.trigger.fire('Horde_Calendar:select', new Date(this.year, this.month, parseInt(e.element().innerHTML, 10)));

        e.stop();
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
            thead = new Element('THEAD'),
            table = new Element('TABLE', { className: 'hordeCalendarPopup', cellSpacing: 0 }).insert(thead).insert(new Element('TBODY'));

        // Title bar.
        link = new Element('A', { href: '#', className: 'hordeCalendarClose' }).insert('x');
        link.observe('click', function(e) { this.hideCal(); e.stop(); }.bind(this));
        thead.insert(new Element('TR').insert(new Element('TD', { colSpan: 7, className: 'rightAlign' }).insert(link)));

        // Year.
        row = new Element('TR');
        link = new Element('A', { href: '#' }).insert('&laquo;');
        link.observe('click', this.changeYear.bind(this, -1));
        row.insert(new Element('TD').insert(link));

        row.insert(new Element('TD', { colSpan: 5, align: 'center' }));

        link = new Element('A', { href: '#' }).insert('&raquo;');
        link.observe('click', this.changeYear.bind(this, 1));
        row.insert(new Element('TD', { className: 'rightAlign' }).insert(link));

        thead.insert(row);

        // Month name.
        row = new Element('TR');
        link = new Element('A', { href: '#' }).insert('&laquo;');
        link.observe('click', this.changeMonth.bind(this, -1));
        row.insert(new Element('TD').insert(link));

        row.insert(new Element('TD', { colSpan: 5, align: 'center' }));

        link = new Element('A', { href: '#' }).insert('&raquo;');
        link.observe('click', this.changeMonth.bind(this, 1));
        row.insert(new Element('TD', { className: 'rightAlign' }).insert(link));

        thead.insert(row);

        // Weekdays.
        row = new Element('TR');
        for (i = 0; i < 7; ++i) {
            row.insert(new Element('TH').insert(this.weekdays[(i + this.firstDayOfWeek) % 7]));
        }
        thead.insert(row);

        $(document.body).insert({ bottom: new Element('DIV', { id: 'hordeCalendar' }).setStyle({ position: 'absolute' }).hide().insert(table) });
    }

};

document.observe('dom:loaded', Horde_Calendar.init.bind(Horde_Calendar));

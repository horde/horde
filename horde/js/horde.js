/**
 * General javascript code useful to various Horde pages.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/* Helper methods for setting/getting element text without mucking
 * around with multiple TextNodes. */
Element.addMethods({
    setText: function(element, text)
    {
        var t = 0;
        $A(element.childNodes).each(function(node) {
            if (node.nodeType == 3) {
                if (t++) {
                    Element.remove(node);
                } else {
                    node.nodeValue = text;
                }
            }
        });

        if (!t) {
            $(element).insert(text);
        }

        return element;
    },

    getText: function(element, recursive)
    {
        var text = '';
        $A(element.childNodes).each(function(node) {
            if (node.nodeType == 3) {
                text += node.nodeValue;
            } else if (recursive && node.hasChildNodes()) {
                text += $(node).getText(true);
            }
        });
        return text;
    }
});

/* Create some utility functions. */
Object.extend(Array.prototype, {
    // Need our own diff() function because prototypejs's without() function
    // does not handle array input.
    diff: function(values)
    {
        return this.select(function(value) {
            return !values.include(value);
        });
    },
    numericSort: function()
    {
        return this.collect(Number).sort(function(a,b) {
            return (a > b) ? 1 : ((a < b) ? -1 : 0);
        });
    }
});

Object.extend(String.prototype, {
    // We define our own version of evalScripts() to make sure that all
    // scripts are running in the same scope and that all functions are
    // defined in the global scope. This is not the case when using
    // prototype's evalScripts().
    evalScripts: function()
    {
        var re = /function\s+([^\s(]+)/g;
        this.extractScripts().each(function(s) {
            var func;
            eval(s);
            while (func = re.exec(s)) {
                window[func[1]] = eval(func[1]);
            }
        });
    },

    /* More efficient String.unfilterJSON() function. */
    unfilterJSON: function(filter) {
        if (filter) {
            return this.replace(filter, '$1');
        } else if (this.startsWith('/*-secure-') &&
                   this.endsWith('*/')) {
            return this.slice(10, -2);
        }
        return this;
    }
});

Object.extend(Date.prototype, {
    /**
     * Returns the corrected week number, i.e. the week number of the next
     * monday, including today.
     *
     * @return integer  This date's week number.
     */
    getRealWeek: function()
    {
        var monday = this;
        if (monday.getDay() < 1) {
            monday = monday.clone().next().monday();
        }
        return monday.getWeek();
    },

    /**
     * Moves a date to the end of the corresponding week.
     *
     * @return Date  The same Date object, now pointing to the end of the week.
     */
    moveToEndOfWeek: function(weekStart)
    {
        var weekEndDay = weekStart + 6;
        if (weekEndDay > 6) {
            weekEndDay -= 7;
        }
        if (this.getDay() != weekEndDay) {
            this.moveToDayOfWeek(weekEndDay, 1);
        }
        return this;
    },

    /**
     * Moves a date to the begin of the corresponding week.
     *
     * @return Date  The same Date object, now pointing to the begin of the
     *               week.
     */
    moveToBeginOfWeek: function(weekStart)
    {
        if (this.getDay() != weekStart) {
            this.moveToDayOfWeek(weekStart, -1);
        }
        return this;
    },

    /**
     * Format date and time to be passed around as a short url parameter,
     * cache id, etc.
     *
     * @return string  Date and time.
     */
    dateString: function()
    {
        return this.toString('yyyyMMdd');
    },

    /**
     * Converts this date from the proleptic Gregorian calendar to the no of
     * days since 24th November, 4714 B.C. (Julian calendar).
     *
     * @return integer  The number of days since 24th November, 4714 B.C.
     */
    toDays: function()
    {
        var day = this.getDate(),
            month = this.getMonth() + 1,
            year = this.getYear() + 1900;

        if (month > 2) {
            // March = 0, April = 1, ..., December = 9, January = 10,
            // February = 11
            month -= 3;
        } else {
            month += 9;
            year--;
        }

        var hb_negativeyear = year < 0,
            century = year / 100 | 0,
            year = year % 100;

        if (hb_negativeyear) {
            // Subtract 1 because year 0 is a leap year;
            // And N.B. that we must treat the leap years as occurring
            // one year earlier than they do, because for the purposes
            // of calculation, the year starts on 1st March:
            return ((14609700 * century + (year == 0 ? 1 : 0)) / 400 | 0) +
                   ((1461 * year + 1) / 4 | 0) +
                   ((153 * month + 2) / 5 | 0) +
                   day + 1721118;
        }

        return (146097 * century / 4 | 0) +
               (1461 * year / 4 | 0) +
               ((153 * month + 2) / 5 | 0) +
               day + 1721119;
    },

    /**
     * Returns the difference to another date in days.
     *
     * @param Date date  Another date.
     *
     * @return integer  The number of days between this and the other date.
     */
    subtract: function(date)
    {
        return this.toDays() - date.toDays();
    }

});

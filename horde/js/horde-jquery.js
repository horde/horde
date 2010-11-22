/**
 * Some general javascript code, prototypejs free, safe to use with jquery.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */
Date.prototype.getRealWeek = function()
{
    var monday = this;
    if (monday.getDay() < 1) {
        monday = monday.clone().next().monday();
    }
    return monday.getWeek();
};

/**
 * Moves a date to the end of the corresponding week.
 *
 * @return Date  The same Date object, now pointing to the end of the week.
 */
Date.prototype.moveToEndOfWeek = function(weekStart)
{
    var weekEndDay = weekStart + 6;
    if (weekEndDay > 6) {
        weekEndDay -= 7;
    }
    if (this.getDay() != weekEndDay) {
        this.moveToDayOfWeek(weekEndDay, 1);
    }
    return this;
};

/**
 * Moves a date to the begin of the corresponding week.
 *
 * @return Date  The same Date object, now pointing to the begin of the
 *               week.
 */
Date.prototype.moveToBeginOfWeek = function(weekStart)
{
    if (this.getDay() != weekStart) {
        this.moveToDayOfWeek(weekStart, -1);
    }
    return this;
};

/**
 * Format date and time to be passed around as a short url parameter,
 * cache id, etc.
 *
 * @return string  Date and time.
 */
Date.prototype.dateString = function()
{
    return this.toString('yyyyMMdd');
};

Number.prototype.toPaddedString = function(len,pad)
{
    len=(len) ? Number(len) : 2;
    if (isNaN(len)) {
      return null;
    }
    var dflt = (isNaN(this.toString())) ? " " : "0";
    pad = (pad) ? pad.toString().substr(0,1) : dflt;
    var str = this.toString();
    if (dflt=="0") {
        while (str.length < len) {
            str=pad+str;
        }
    } else {
        while (str.length < len) {
            str += pad;
        }
    }
    return str;
};

String.prototype.toPaddedString = Number.prototype.toPaddedString;

Array.prototype.numericSort = function()
{
    return $.map(this, function(n) {
        return new Number(n);
    }).sort(function(a, b) {
        return (a > b) ? 1 : ((a < b) ? -1 : 0);
    });
}

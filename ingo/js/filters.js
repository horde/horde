/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var IngoFilters = {

    moveFromTo: function(from, to, upurl, downurl)
    {
        var steps = to - from;
        if (steps < 0) {
            window.location = upurl + '&steps=' + -steps;
        } else if (steps > 0) {
            window.location = downurl + '&steps=' + steps;
        }
        return true;
    }

};

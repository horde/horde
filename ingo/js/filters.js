/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
    },

    onDomLoad: function()
    {
        if ($('apply_filters')) {
            $('apply_filters').observe('click', function(e) {
                $('actionID').setValue('apply_filters');
                $('filters').submit();
                e.stop();
            });
        }
    }

};

document.observe('dom:loaded', IngoFilters.onDomLoad.bind(IngoFilters));

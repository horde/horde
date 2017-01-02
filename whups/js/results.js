/**
 * JavaScript for result lists.
 *
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

function table_sortCallback(tableId, column, sortDown)
{
    HordeCore.doAction('setPrefValue', { pref: 'sortby', value: column });
    HordeCore.doAction('setPrefValue', { pref: 'sortdir', value: sortDown });
}

document.observe('dom:loaded', function() {
    $('check-all').observe('click', function() {
        var inputs = $('delete-form').getInputs('checkbox', 'ticket[]'),
            check = inputs.any(function(c) { return !c.checked; });
        inputs.each(function(input) {
            input.checked = check;
        });
        $('check-all').checked = check;
    });
});

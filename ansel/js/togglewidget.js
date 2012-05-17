/**
 * @param string node  The DOM id of the node to show or hide.
 *                     The node that contains the toggle link should be named
 *                     {node}-toggle
 */
function doActionToggle(node)
{
    $(node).toggle();
    togglePlusMinus(node);
    return false;
}

function togglePlusMinus(node)
{
    var pref_value;

    if ($(node + '-toggle').hasClassName('show')) {
        $(node + '-toggle').removeClassName('show');
        $(node + '-toggle').addClassName('hide');
        pref_value = 1;
    } else if ($(node + '-toggle').hasClassName('hide')) {
        $(node + '-toggle').removeClassName('hide');
        $(node + '-toggle').addClassName('show');
        pref_value = 0;
    }

    HordeCore.doAction('setPrefValue', {
        pref: foo,
        value: pref_value
    });
}

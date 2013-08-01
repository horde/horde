/**
 * @param string node  The DOM id of the node to show or hide.
 *                     The node that contains the toggle link should be named
 *                     {node}-toggle
 */
function doActionToggle(node, pref_name)
{
    togglePlusMinus(node, pref_name);
    node = node.replace('-toggle', '');
    $(node).toggle();
    return false;
}

function togglePlusMinus(node, pref_name)
{
    var pref_value;

    if ($(node).hasClassName('show')) {
        $(node).removeClassName('show');
        $(node).addClassName('hide');
        pref_value = 1;
    } else if ($(node).hasClassName('hide')) {
        $(node).removeClassName('hide');
        $(node).addClassName('show');
        pref_value = 0;
    }

    HordeCore.doAction('setPrefValue', {
        pref: pref_name,
        value: pref_value
    });
}

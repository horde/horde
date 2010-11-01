/**
 * @param string node  The DOM id of the node to show or hide.
 *                     The node that contains the toggle link should be named
 *                     {node}-toggle
 *
 * @param string requestType  The class name of the Ajax_Imple type for this
 *                            widget.
 *
 */
function doActionToggle(node, requestType)
{
    $(node).toggle();
    togglePlusMinus(node, requestType);
    return false;
}

function togglePlusMinus(node, requestType)
{
    var pref_value;

    if ($(node + '-toggle').hasClassName('show')) {
        $(node + '-toggle').removeClassName('show');
        $(node + '-toggle').addClassName('hide');
        var pref_value = 1;
    } else if ($(node + '-toggle').hasClassName('hide')) {
        $(node + '-toggle').removeClassName('hide');
        $(node + '-toggle').addClassName('show');
        var pref_value = 0;
    }

    var url = Ansel.widgets[requestType].url;
    var params = { "value": "value=" + pref_value };
    new Ajax.Request(url, { parameters: params });
}
/**
 * Javascript code for making the ToC collapsible.
 *
 * $Horde: wicked/js/toc.js,v 1.3 2007/01/02 00:42:09 jan Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

addEvent(window, 'load', wicked_toc);
function wicked_toc()
{
    var toc = document.getElementById('toc');
    var ol, h2;
    for (var i = 0; i < toc.childNodes.length; ++i) {
        var tagName = toc.childNodes[i].tagName.toUpperCase();
        if (tagName == 'OL') {
            ol = toc.childNodes[i];
        } else if (tagName == 'H2') {
            h2 = toc.childNodes[i];
        }
    }

    if (!ol) {
        return;
    }

    h2.style.cursor = 'pointer';

    addEvent(h2, 'click', function() {
        if (ol.style.display != 'none') {
            ol.style.display = 'none';
        } else {
            ol.style.display = '';
        }
    });
}

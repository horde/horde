/**
 * Javascript code for making the TOC collapsible.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

document.observe('dom:loaded', function() {
    var h2 = $('toc').down('h2'),
        ol = $('toc').down('ol');
    if (!ol) {
        return;
    }
    h2.setStyle({ cursor: 'pointer' });
    h2.observe('click', ol.toggle.bind(ol));
});

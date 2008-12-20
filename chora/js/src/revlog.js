/**
 * Revision log javascript.
 */

var revlog_selected = null;
var isMSIE = /*@cc_on!@*/false;

function revlog_highlight()
{
    var revlog_body = $('revlog_body');

    $A(revlog_body.getElementsByTagName('TR')).each(function(tr) {
        if (isMSIE) {
            Event.observe(tr, 'mouseover', (function() { Element.addClassName(this, 'hover'); }).bind(tr));
            Event.observe(tr, 'mouseout', (function() { Element.removeClassName(this, 'hover'); }).bind(tr));
        }
        Event.observe(tr, 'click', revlog_toggle.bindAsEventListener(tr));
    });
}

function revlog_toggle(e)
{
    // Ignore clicks on links.
    var elt = Event.element(e);
    while (elt != this) {
        if (elt.tagName.toUpperCase() == 'A' && elt.getAttribute('href')) {
            return;
        }
        elt = elt.parentNode;
    }

    if (revlog_selected != null) {
        Element.removeClassName(revlog_selected, 'selected');
        if (revlog_selected == this) {
            revlog_selected = null;
            Element.removeClassName('revlog_body', 'selection');
            return;
        }
    }

    revlog_selected = this;
    Element.addClassName(this, 'selected');
    Element.addClassName('revlog_body', 'selection');
}

function revlog_sdiff(link)
{
    link.href = link.href.replace(/r1=([\d\.]+)/, 'r1=' + revlog_selected.id.substring(3));
}

Event.observe(window, 'load', revlog_highlight);

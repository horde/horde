var eventTabs = null;

function ShowTab(tab)
{
    if (eventTabs == null) {
        eventTabs = $('page').select('.tabset ul li');
    }

    eventTabs.each(function(c) {
        var t = $(c.id.substring(3));
        if (!t) {
            return;
        }
        if (c.id == 'tab' + tab) {
            c.addClassName('horde-active');
            t.show();
        } else {
            c.removeClassName('horde-active');
            t.hide();
        }
    });

    return false;
}

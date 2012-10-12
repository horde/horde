var contactTabs = null;
function ShowTab(tab)
{
    if (contactTabs == null) {
        contactTabs = $('page').select('.horde-buttonbar')[0].down();
    }

    contactTabs.select('li').each(function(item) {
        if (item.id == 'tab' + tab) {
            item.addClassName('horde-active');
            $(item.id.substring(3)).show();
        } else {
            item.removeClassName('horde-active');
            $(item.id.substring(3)).hide();
        }
    });

    return false;
}

/**
 * Horde sidebar javascript.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */
var HordeSidebar = {
    // Vars set in Horde_Sidebar
    //   opts, text

    refreshEvents: function()
    {
        $('horde-sidebar').select('div.horde-resources div').each(function(s) {
            s.observe('mouseover', s.addClassName.curry('horde-resource-over'));
            s.observe('mouseout', s.removeClassName.curry('horde-resource-over'));
        });
    },

    clickHandler: function(e)
    {
        if (e.isRightClick() || typeof e.element != 'function') {
            return;
        }

        var elt = e.element(),
            params = ';DOMAIN=' + this.opts.cookieDomain
                + ';PATH=' + this.opts.cookiePath + ';'

        while (Object.isElement(elt)) {
            switch (elt.className) {
            case 'horde-collapse':
                elt.up().next().blindUp();
                elt.title = this.text.expand;
                elt.removeClassName('horde-collapse');
                elt.addClassName('horde-expand');
                document.cookie = 'horde_sidebar_c_' + elt.identify() + '=1' + params;
                return;

            case 'horde-expand':
                elt.up().next().blindDown();
                elt.title = this.text.collapse;
                elt.removeClassName('horde-expand');
                elt.addClassName('horde-collapse');
                document.cookie = 'horde_sidebar_c_' + elt.identify() + '=0' + params;
                return;
            }

            elt = elt.up();
        }
        // Workaround Firebug bug.
        Prototype.emptyFunction();
    },

    onDomLoad: function()
    {
        this.refreshEvents();
    }
};

document.observe('dom:loaded', HordeSidebar.onDomLoad.bind(HordeSidebar));
document.observe('click', HordeSidebar.clickHandler.bindAsEventListener(HordeSidebar));

/**
 * Maillog display for dynamic view.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2015 Horde LLC
 * @license    GPL-2 (http://www.horde.org/licenses/gpl)
 */

var ImpMaillog = {

    // Vars set by calling code:
    //   error_msg

    onDomLoad: function()
    {
        var base;

        if (base = ImpCore.baseAvailable()) {
            base.HordeCore.notify(this.error_msg, 'horde.error');
            window.close();
        } else {
            document.body.insert(this.error_msg.escapeHTML());
        }
    }

};

/* Initialize onload handler. */
document.observe('dom:loaded', ImpMaillog.onDomLoad.bind(ImpMaillog));

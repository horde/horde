/**
 * Provides the javascript for managing alarms.
 *
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2.1 (http://www.horde.org/licenses/lgpl21)
 */

var HordeAlarmPrefs = {

    // Variables defaulting to null: pref

    updateParams: function()
    {
        [ 'notify', 'mail', 'sms' ].each(function(method) {
            var p = $(method + 'Params');
            if (p) {
                if ($(this.pref).getValue().include(method)) {
                    p.show();
                } else {
                    p.hide();
                }
            }
        }, this);
    },

    onDomLoad: function()
    {
        $(this.pref).observe('change', this.updateParams.bind(this));
        this.updateParams();
    }

};

document.observe('dom:loaded', HordeAlarmPrefs.onDomLoad.bind(HordeAlarmPrefs));

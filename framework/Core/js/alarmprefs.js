/**
 * Provides the javascript for managing alarms.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
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

document.observe('dom:load', HordeAlarmPrefs.onDomLoad.bind(HordeAlarmPrefs));

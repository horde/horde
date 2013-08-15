/**
 * Provides javascript support for the dynamic passwd view.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

document.observe('dom:loaded', function() {
    $('passwd-newpassword0').observe('keyup', function(e) {
        HordeCore.doAction(
            'validatePassword', {
                password: $('passwd-newpassword0').getValue(),
                backend: $('passwd-backend').getValue()
            }, {
                callback: function(r) {
                    $H(r).each(function(policy) {
                        if (policy.value) {
                            $(policy.key + '_result').addClassName('policy_good');
                            $(policy.key + '_result').removeClassName('policy_bad');
                        } else {
                            $(policy.key + '_result').removeClassName('policy_good');
                            $(policy.key + '_result').addClassName('policy_bad');
                        }
                    });
                }.bind(this)
            }
        );
    });
});

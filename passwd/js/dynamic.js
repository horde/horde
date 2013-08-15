/**
 * Provides javascript support for the dynamic passwd view.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

/* Passwd Object */
PasswdCore = {
    validatePassword: function(e) {
        HordeCore.doAction(
            'validatePassword', {
                password: $('passwd-newpassword0').getValue(),
                backend: $('passwd-backend').getValue()
            }, {
                callback: function(r) {
                    submitFlag = true;
                    $H(r).each(function(policy) {
                        if (policy.value) {
                            $(policy.key + '_result').addClassName('policy_good');
                            $(policy.key + '_result').removeClassName('policy_bad');
                        } else {
                            submitFlag = false;
                            $(policy.key + '_result').removeClassName('policy_good');
                            $(policy.key + '_result').addClassName('policy_bad');
                        }
                        if (submitFlag) {
                            $('passwd-submit').enable();
                        } else {
                            $('passwd-submit').disable();
                        }
                    });
                }.bind(this)
            }
        );
    }
}


document.observe('dom:loaded', function() {
    PasswdCore.validatePassword();
    $('passwd-newpassword0').observe('keyup', PasswdCore.validatePassword);
});

/**
 * Code for mnemo/memo.php.
 *
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 * @author  Jan Schneider <jan@horde.org>
 */

var Mnemo_Memo = {
    // Externally set properties:
    //  confirm
    updateCharacterCount: function()
    {
        if ($('mnemo-body')) {
            $('mnemo-count').update(
                $F('mnemo-body').replace(/[\r\n]/g, '').length
            );;
        }
    },

    onDomLoad: function()
    {
        if ($('mnemo-passphrase')) {
            $('mnemo-passphrase').focus();
        }
        if ($('mnemo-body')) {
            $('mnemo-body').focus();
        }

        if ($('mnemo-delete')) {
            $('mnemo-delete').observe(
                'click',
                function(e)
                {
                    if (this.confirm) {
                        if (!window.confirm(this.confirm)) {
                            e.stop();
                        }
                    }
                }.bindAsEventListener(this)
            );
        }

        if ($('mnemo-body')) {
            $('mnemo-body').observe('change', this.updateCharacterCount);
            $('mnemo-body').observe('click', this.updateCharacterCount);
            $('mnemo-body').observe('keypress', this.updateCharacterCount.defer.bind(this.updateCharacterCount));
        }
    }
}
document.observe('dom:loaded', Mnemo_Memo.onDomLoad.bind(Mnemo_Memo));

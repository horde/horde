/**
 * Code for mnemo/view.php.
 *
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 * @author  Jan Schneider <jan@horde.org>
 */

var Mnemo_View = {
    // Externally set properties:
    //  confirm
    onDomLoad: function()
    {
        if ($('mnemo-passphrase')) {
            $('mnemo-passphrase').focus();
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
    }
}
document.observe('dom:loaded', Mnemo_View.onDomLoad.bind(Mnemo_View));

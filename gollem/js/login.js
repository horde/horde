/**
 * Provides the javascript for the login.php script.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

var GollemLogin = {

    changeHandler: function(e)
    {
        switch (e.element().readAttribute('id')) {
        case 'backend_key':
            $('horde_login').submit();
            break;
        }
    }

}

document.observe('change', GollemLogin.changeHandler);

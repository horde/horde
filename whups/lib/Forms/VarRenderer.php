<?php
/**
 * This file contains all Horde_Core_Ui_VarRenderer extensions for Whups
 * specific form fields.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */

/**
 * The Horde_Core_Ui_VarRenderer_whups class provides additional methods for
 * rendering Horde_Form_Type_whups_email fields.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */
class Horde_Core_Ui_VarRenderer_whups extends Horde_Core_Ui_VarRenderer_Html {

    function _renderVarInput_whups_email($form, &$var, &$vars)
    {
        $name = $var->getVarName();

        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('whups', 'ContactAutoCompleter'), array(
            'triggerId' => $name
        ));

        return sprintf('<input type="text" name="%s" id="%s" value="%s" autocomplete="off"%s />',
                       $name,
                       $name,
                       @htmlspecialchars($var->getValue($vars)),
                       $this->_getActionScripts($form, $var))
            . '<span id="' . $name . '_loading_img" style="display:none;">'
            . Horde::img('loading.gif', _("Loading..."))
            . '</span>';
    }

}

<?php
/**
 * This file contains all Horde_Core_Ui_VarRenderer extensions required for
 * editing contacts.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Turba
 */

/**
 * The Horde_Core_Ui_VarRenderer_Turba class provides additional methods for
 * rendering Turba specific fields.
 *
 * @todo    Clean this hack up with Horde_Form/H4
 * @author  Jan Schneider <jan@horde.org>
 * @package Turba
 */
class Horde_Core_Ui_VarRenderer_Turba extends Horde_Core_Ui_VarRenderer_Html
{
    /**
     * Render tag field.
     */
    protected function _renderVarInput_TurbaTags($form, $var, $vars)
    {
        $varname = htmlspecialchars($var->getVarName());
        $value = $var->getValue($vars);

        $html = sprintf('<input id="%s" type="text" name="%s" value="%s" />', $varname, $varname, $value);
        $html .= sprintf('<span id="%s_loading_img" style="display:none;">%s</span>',
            $varname,
            Horde::img('loading.gif', _("Loading...")));

        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('Turba_Ajax_Imple_TagAutoCompleter', array('id' => $varname));
        return $html;
    }
}

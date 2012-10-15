<?php
/**
 * This file contains all Horde_Core_Ui_VarRenderer extensions required for
 * editing calendars.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Kronolith
 */

/**
 * The Horde_Core_Ui_VarRenderer_Kronolith class provides additional methods for
 * rendering Kronolith specific fields.
 *
 * @todo    Clean this hack up with Horde_Form/H6
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Horde_Core_Ui_VarRenderer_Kronolith extends Horde_Core_Ui_VarRenderer_Html
{
    /**
     * Render tag field.
     */
    protected function _renderVarInput_KronolithTags($form, $var, $vars)
    {
        $varname = @htmlspecialchars($var->getVarName(), ENT_QUOTES, $this->_charset);
        $value = $var->getValue($vars);

        $html = sprintf('<input id="%s" type="text" name="%s" value="%s" />', $varname, $varname, $value);
        $html .= sprintf('<span id="%s_loading_img" style="display:none;">%s</span>',
            $varname,
            Horde::img('loading.gif', _("Loading...")));

        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')
            ->create(
                'Kronolith_Ajax_Imple_TagAutoCompleter',
                array('id' => $varname));
        return $html;
    }

}
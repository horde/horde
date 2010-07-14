<?php
/**
 * This file contains all Horde_Core_Ui_VarRenderer extensions required for
 * editing accounts.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/**
 * The Horde_Core_Ui_VarRenderer_fima class provides additional methods for
 * rendering Fima specific Horde_Form_Type fields.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Horde_Core_Ui_VarRenderer_Fima extends Horde_Core_Ui_VarRenderer_Html {

    protected function _renderVarInput_fima_dspostings($form, &$var, &$vars)
    {
        $varname = @htmlspecialchars($var->getVarName(), ENT_QUOTES, $this->_charset);
        $value = $var->getValue($vars);

        return sprintf('<input id="%sdelete" type="radio" class="radio" name="%s[type]" value="delete"%s /><label for="%sdelete">&nbsp;%s</label><br />',
                       $varname,
                       $varname,
                       $value['type'] == 'delete' ? ' checked="checked"' : '',
                       $varname,
                       _("Delete postings."))
            . sprintf('<input id="%sshift" type="radio" class="radio" name="%s[type]" value="shift"%s /><label for="%sshift">&nbsp;%s</label><br />',
                      $varname,
                      $varname,
                      $value['type'] == 'shift' ? ' checked="checked"' : '',
                      $varname,
                      _("Shift postings to"))
            . Fima::buildAccountWidget($varname . '[account]', $value['account'], 'onchange="document.getElementsByName(\'dspostings[type]\')[1].checked = true;"', false, false, array(array('type', $vars->get('type'))));
    }

    protected function _renderVarInput_fima_dssubaccounts($form, &$var, &$vars)
    {
        $varname = @htmlspecialchars($var->getVarName(), ENT_QUOTES, $this->_charset);
        $value = $var->getValue($vars);

        return sprintf('<input id="%snone" type="radio" class="radio" name="%s[type]" value="none"%s /><label for="%snone">&nbsp;%s</label><br />',
                       $varname,
                       $varname,
                       $value['type'] == 'none' ? ' checked="checked"' : '',
                       $varname,
                       _("Keep subaccounts and postings."))
            . sprintf('<input id="%sdelete" type="radio" class="radio" name="%s[type]" value="delete"%s /><label for="%sdelete">&nbsp;%s</label><br />',
                      $varname,
                      $varname,
                      $value['type'] == 'delete' ? ' checked="checked"' : '',
                      $varname,
                      _("Delete subaccounts and postings."))
            . sprintf('<input id="%sshift" type="radio" class="radio" name="%s[type]" value="shift"%s /><label for="%sshift">&nbsp;%s</label><br />',
                      $varname,
                      $varname,
                      $value['type'] == 'shift' ? ' checked="checked"' : '',
                      $varname,
                      _("Delete subaccounts and shift postings to"))
            . Fima::buildAccountWidget($varname . '[account]', $value['account'], 'onchange="document.getElementsByName(\'dssubaccounts[type]\')[2].checked = true;"', false, false, array(array('type', $vars->get('type'))));
    }

}

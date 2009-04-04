<?php
/**
 * This file contains all Horde_UI_VarRenderer extensions required for editing
 * accounts.
 *
 * $Horde: fima/lib/UI/VarRenderer/fima.php,v 1.0 2008/06/19 20:58:27 trt Exp $
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Horde_UI_VarRenderer */
require_once 'Horde/UI/VarRenderer.php';

/** Horde_UI_VarRenderer_html */
require_once 'Horde/UI/VarRenderer/html.php';

/**
 * The Horde_UI_VarRenderer_fima class provides additional methods for
 * rendering Fima specific Horde_Form_Type fields.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Horde_UI_VarRenderer_fima extends Horde_UI_VarRenderer_html {

    function _renderVarInput_fima_dspostings($form, &$var, &$vars)
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

    function _renderVarInput_fima_dssubaccounts($form, &$var, &$vars)
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

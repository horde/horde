<?php
/**
 * Extension of Horde's variable renderer that support Ingo's folders variable
 * type.
 *
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Horde_Core_Ui_VarRenderer_Ingo extends Horde_Core_Ui_VarRenderer_Html
{
    protected function _renderVarInput_ingo_folders(&$form, &$var, &$vars)
    {
        return Ingo::flistSelect($var->type->getFolder(), 'horde_form', 'folder');
    }
}

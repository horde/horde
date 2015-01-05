<?php
/**
 * Copyright 2006-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Extension of Horde's variable renderer that support Ingo's folders variable
 * type.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Horde_Core_Ui_VarRenderer_Ingo extends Horde_Core_Ui_VarRenderer_Html
{
    public function __construct($params = array())
    {
        parent::__construct($params);

        // This will autoload the class.
        class_exists('Ingo_Form_Type_Longemail');
    }

    protected function _renderVarInput_ingo_folders(&$form, &$var, &$vars)
    {
        return Ingo_Flist::select($var->type->getFolder(), 'folder');
    }

    protected function _renderVarInput_ingo_form_type_longemail($form, &$var, &$vars)
    {
        return $this->_renderVarInput_longtext($form, $var, $vars);
    }
}

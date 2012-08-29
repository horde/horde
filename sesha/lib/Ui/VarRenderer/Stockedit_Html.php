<?php
/**
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Bo Daley <bo@darkwork.net>
 * @package Sesha
 */
class Horde_Core_UI_VarRenderer_Stockedit_Html extends Horde_Core_Ui_VarRenderer_Html {

    protected function _renderVarInput_client($form, $var, $vars)
    {
        return $this->_renderVarInput_enum($form, $var, $vars);
    }

}

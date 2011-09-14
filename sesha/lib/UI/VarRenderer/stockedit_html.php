<?php
/**
 * $Horde: sesha/lib/UI/VarRenderer/stockedit_html.php,v 1.10 2009-12-10 17:42:38 jan Exp $
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Bo Daley <bo@darkwork.net>
 * @package Sesha
 */
class Horde_UI_VarRenderer_stockedit_html extends Horde_Ui_VarRenderer_Html {

    function _renderVarInput_client(&$form, &$var, &$vars)
    {
        return $this->_renderVarInput_enum($form, $var, $vars);
    }

}

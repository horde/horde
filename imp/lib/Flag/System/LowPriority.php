<?php
/**
 * This class implements the low priority flag.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Flag_System_LowPriority extends IMP_Flag_System_Match_Header
{
    /**
     */
    protected $_css = 'flagLowpriority';

    /**
     */
    protected $_id = 'lowp';

    /**
     */
    protected function _getLabel()
    {
        return _("Low Priority");
    }

    /**
     */
    public function match(Horde_Mime_Headers $data)
    {
        return ($GLOBALS['injector']->getInstance('IMP_Ui_Headers')->getPriority($data) == 'low');
    }

}

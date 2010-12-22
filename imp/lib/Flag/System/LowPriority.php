<?php
/**
 * This class implements the low priority flag.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Flag_System_LowPriority extends IMP_Flag_System
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
     * @param Horde_Mime_Headers $data  Headers object for a message.
     */
    public function match($data)
    {
        return ($GLOBALS['injector']->getInstance('IMP_Ui_Headers')->getPriority($data) == 'low');
    }

}

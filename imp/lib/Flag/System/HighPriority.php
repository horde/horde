<?php
/**
 * This class implements the high priority flag.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Flag_System_HighPriority extends IMP_Flag_System
{
    /**
     */
    protected $_abbreviation = '!';

    /**
     */
    protected $_bgcolor = '#fcc';

    /**
     */
    protected $_css = 'flagHighpriority';

    /**
     */
    protected $_id = 'highp';

    /**
     */
    protected function _getLabel()
    {
        return _("High Priority");
    }

    /**
     * @param Horde_Mime_Headers $data  Headers object for a message.
     */
    public function match($data)
    {
        return ($GLOBALS['injector']->getInstance('IMP_Ui_Headers')->getPriority($data) == 'high');
    }

}

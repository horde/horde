<?php
/**
 * This class implements the high priority flag.
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
class IMP_Flag_System_HighPriority extends IMP_Flag_System_Match_Header
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
     */
    public function match(Horde_Mime_Headers $data)
    {
        return ($GLOBALS['injector']->getInstance('IMP_Ui_Headers')->getPriority($data) == 'high');
    }

}

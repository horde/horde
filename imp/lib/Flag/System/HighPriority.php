<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class implements the high priority flag.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Flag_System_HighPriority
extends IMP_Flag_Base
implements IMP_Flag_Match_Header
{
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
    public function matchHeader(Horde_Mime_Headers $data)
    {
        return ($GLOBALS['injector']->getInstance('IMP_Mime_Headers')->getPriority($data) == 'high');
    }

}

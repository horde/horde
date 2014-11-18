<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Null storage driver for the IMP_Maillog class.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Maillog_Storage_Null extends IMP_Maillog_Storage_Base
{
    /**
     */
    public function saveLog(
        IMP_Maillog_Message $msg, IMP_Maillog_Log_Base $log
    )
    {
        return false;
    }

    /**
     */
    public function getLog(IMP_Maillog_Message $msg, array $types = array())
    {
        return array();
    }

    /**
     */
    public function deleteLogs(array $msgs)
    {
    }

    /**
     */
    public function getChanges($ts)
    {
        return array();
    }

}

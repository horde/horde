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
    public function saveLog($msg_id, $data)
    {
    }

    /**
     */
    public function getLog($msg_id)
    {
        return new Horde_History_Log($msg_id);
    }

    /**
     */
    public function deleteLogs($msg_ids)
    {
    }

    /**
     */
    public function getChanges($ts)
    {
        return array();
    }

}

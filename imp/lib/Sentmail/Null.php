<?php
/**
 * A null implementation of the sentmail driver.
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
class IMP_Sentmail_Null extends IMP_Sentmail
{
    /**
     */
    protected function _log($action, $message_id, $recipient, $success)
    {
    }

    /**
     */
    public function favouriteRecipients($limit, $filter = null)
    {
        return array();
    }

    /**
     */
    public function numberOfRecipients($hours, $user = false)
    {
        return 0;
    }

    /**
     */
    protected function _deleteOldEntries($before)
    {
    }

}

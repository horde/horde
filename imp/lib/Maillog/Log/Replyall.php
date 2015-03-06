<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Reply-all log entry.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Maillog_Log_Replyall
extends IMP_Maillog_Log_Sentmail
{
    /**
     */
    protected $_action = 'reply_all';

    /**
     */
    protected function _getMessage()
    {
        return sprintf(
             _("You replied to all recipients of this message on %s."),
            $this->date
        );
    }

}

<?php
/**
 * This class implements the seen flag (RFC 3501 [2.3.2]).
 * Unseen display formatting is handled by the IMP_Flag_System_Unseen class.
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
class IMP_Flag_Imap_Seen extends IMP_Flag_Imap
{
    /**
     */
    protected $_canset = true;

    /**
     */
    protected $_cssIcon = 'flagSeen';

    /**
     */
    protected $_imapflag = Horde_Imap_Client::FLAG_SEEN;

    /**
     */
    protected function _getLabel()
    {
        return _("Seen");
    }

}

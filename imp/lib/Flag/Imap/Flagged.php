<?php
/**
 * This class implements the flagged for followup flag (RFC 3501 [2.3.2]).
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
class IMP_Flag_Imap_Flagged extends IMP_Flag_Imap
{
    /**
     */
    protected $_bgcolor = '#fcc';

    /**
     */
    protected $_canset = true;

    /**
     */
    protected $_css = 'flagFlagged';

    /**
     */
    protected $_imapflag = Horde_Imap_Client::FLAG_FLAGGED;

    /**
     */
    protected function _getLabel()
    {
        return _("Flagged for Followup");
    }

}

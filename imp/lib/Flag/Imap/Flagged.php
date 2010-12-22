<?php
/**
 * This class implements the flagged for followup flag (RFC 3501 [2.3.2]).
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
    protected $_imapflag = '\\flagged';

    /**
     */
    protected function _getLabel()
    {
        return _("Flagged for Followup");
    }

}

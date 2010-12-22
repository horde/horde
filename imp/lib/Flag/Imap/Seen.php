<?php
/**
 * This class implements the seen flag (RFC 3501 [2.3.2]).
 * Unseen display formatting is handled by the IMP_Flag_System_Unseen class.
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
    protected $_imapflag = '\\seen';

    /**
     */
    protected function _getLabel()
    {
        return _("Seen");
    }

}

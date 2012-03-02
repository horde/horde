<?php
/**
 * This class implements the not junk flag.
 * See: http://www.ietf.org/mail-archive/web/morg/current/msg00441.html
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Flag_Imap_NotJunk extends IMP_Flag_Imap
{
    /**
     */
    protected $_css = 'flagNotJunk';

    /**
     */
    protected $_imapflag = '$notjunk';

    /**
     */
    protected function _getLabel()
    {
        return _("Not Junk");
    }

}

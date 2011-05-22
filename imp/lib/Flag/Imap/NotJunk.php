<?php
/**
 * This class implements the not junk flag.
 * See: http://www.ietf.org/mail-archive/web/morg/current/msg00441.html
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Flag_Imap_NotJunk extends IMP_Flag_Imap
{
    /**
     */
    // TODO: BC
    protected $_imapflag = '$notjunk';

    /**
     */
    protected function _getLabel()
    {
        return _("Not Junk");
    }

}

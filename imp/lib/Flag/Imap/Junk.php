<?php
/**
 * This class implements the junk flag.
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
class IMP_Flag_Imap_Junk extends IMP_Flag_Imap
{
    /**
     */
    protected $_abbreviation = 'J';

    /**
     */
    protected $_css = 'flagJunk';

    /**
     */
    // TODO: BC
    protected $_imapflag = '$junk';

    /**
     */
    protected function _getLabel()
    {
        return _("Junk");
    }

}

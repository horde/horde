<?php
/**
 * This class implements the forwarded flag (RFC 5550 [5.9]).
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
class IMP_Flag_Imap_Forwarded extends IMP_Flag_Imap
{
    /**
     */
    protected $_bgcolor = '#bfdfdf';

    /**
     */
    protected $_css = 'flagForwarded';

    /**
     */
    protected $_imapflag = Horde_Imap_Client::FLAG_FORWARDED;

    /**
     */
    protected function _getLabel()
    {
        return _("Forwarded");
    }

}

<?php
/**
 * This class implements the answered flag (RFC 3501 [2.3.2]).
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Flag_Imap_Answered extends IMP_Flag_Imap
{
    /**
     */
    protected $_bgcolor = '#cfc';

    /**
     */
    protected $_css = 'flagAnswered';

    /**
     */
    protected $_imapflag = '\\answered';

    /**
     */
    protected function _getLabel()
    {
        return _("Answered");
    }

}

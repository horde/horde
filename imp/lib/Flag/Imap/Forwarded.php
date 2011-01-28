<?php
/**
 * This class implements the forwarded flag (RFC 5550 [5.9]).
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
    protected $_imapflag = '$forwarded';

    /**
     */
    protected function _getLabel()
    {
        return _("Forwarded");
    }

}

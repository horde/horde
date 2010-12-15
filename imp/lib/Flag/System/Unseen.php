<?php
/**
 * This class implements formatting for unseen messages. Unseen occurs when
 * the seen flag (RFC 3501 [2.3.2]) is NOT set; thus, it can not be handled
 * in the seen flag object.
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
class IMP_Flag_System_Unseen extends IMP_Flag_System
{
    /**
     */
    protected $_abbreviation = 'U';

    /**
     */
    protected $_bgcolor = '#eef';

    /**
     */
    protected $_css = 'flagUnseen';

    /**
     */
    protected $_id = 'unseen';

    /**
     */
    protected function _getLabel()
    {
        return _("Unseen");
    }

    /**
     */
    public function changed($obs, $add)
    {
        foreach ($obs as $val) {
            if ($val instanceof IMP_Flag_Imap_Seen) {
                return !$add;
            }
        }

        return null;
    }

    /**
     * @param array $data  List of IMAP flags.
     */
    public function match($data)
    {
        return !in_array('\\seen', $data);
    }

}

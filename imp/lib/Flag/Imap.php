<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class provides the data structure for a message flag.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read string $imapflag  The IMAP flag string.
 */
abstract class IMP_Flag_Imap
extends IMP_Flag_Base
implements IMP_Flag_Match_Flag
{
    /**
     * The IMAP flag string used on the server.
     *
     * @var string
     */
    protected $_imapflag;

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'id':
        case 'imapflag':
            return $this->_imapflag;

        default:
            return parent::__get($name);
        }
    }

    /**
     */
    public function matchFlag(array $data)
    {
        foreach ($data as $val) {
            if (strcasecmp($this->imapflag, $val) === 0) {
                return true;
            }
        }

        return false;
    }

}

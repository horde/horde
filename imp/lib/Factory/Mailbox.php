<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based IMP_Mailbox factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Mailbox extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the IMP_Mailbox:: instance.
     *
     * @param string $mbox  The IMAP mailbox name.
     *
     * @return IMP_Mailbox  The singleton mailbox instance.
     * @throws IMP_Exception
     */
    public function create($mbox)
    {
        if ($mbox instanceof IMP_Mailbox) {
            return $mbox;
        }

        if (!isset($this->_instances[$mbox])) {
            $this->_instances[$mbox] = new IMP_Mailbox($mbox);
        }

        return $this->_instances[$mbox];
    }

}

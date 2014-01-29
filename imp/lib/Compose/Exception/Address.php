<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Extension of IMP_Compose_Exception that handles the situation of invalid
 * address input. Allows details of individual e-mail address errors to be
 * communicated to the user.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Compose_Exception_Address
extends IMP_Compose_Exception
implements Countable, IteratorAggregate
{
    /* Severity level. */
    const BAD = 1;
    const WARN = 2;

    /**
     * The list of error addresses.
     *
     * @var array
     */
    protected $_addresses = array();

    /**
     * Add an address to the bad list.
     *
     * @param Horde_Mail_Rfc822_Object $address  Bad address.
     * @param Exception|string $msg              Error message.
     * @param integer $level                     Severity level.
     */
    public function addAddress(
        Horde_Mail_Rfc822_Object $address, $msg, $level = self::BAD
    )
    {
        $ob = new stdClass;
        $ob->address = $address;
        $ob->error = ($msg instanceof Exception)
            ? $msg->getMessage()
            : strval($msg);
        $ob->level = $level;

        $this->_addresses[] = $ob;
    }

    /* Countable method. */

    /**
     * Returns the number of error addresses.
     *
     * @return integer  The number of error addresses.
     */
    public function count()
    {
        return count($this->_addresses);
    }

    /* IteratorAggregate method. */

    /**
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_addresses);
    }

}

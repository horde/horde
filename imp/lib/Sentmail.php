<?php
/**
 * The IMP_Sentmail:: class contains all functions related to handling
 * logging of sent mail and retrieving sent mail statistics.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Sentmail
{
    /* Action constants. */
    const NEWMSG = 'new';
    const REPLY = 'reply';
    const FORWARD = 'forward';
    const REDIRECT = 'redirect';
    const MDN = 'mdn';

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of the concrete subclass to return.
     *                        The class name is based on the storage driver
     *                        ($driver).
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return IMP_Sentmail_Driver  The newly created concrete instance.
     * @throws IMP_Exception
     */
    static public function factory($driver, $params = array())
    {
        $class = __CLASS__ . '_' . ucfirst(basename($driver));

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new IMP_Exception(__CLASS__ . ': Driver not found: ' . $driver);
    }

}

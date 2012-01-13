<?php
/* Need to define this outside of class since constants in class can not be
 * assigned from a function return. */
define('VC_WINDOWS', !strncasecmp(PHP_OS, 'WIN', 3));

/**
 * Version Control generalized library.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @package Vcs
 */
class Horde_Vcs
{
    /* Sorting options */
    const SORT_NONE = 0;    // don't sort
    const SORT_AGE = 1;     // sort by age
    const SORT_NAME = 2;    // sort by filename
    const SORT_REV = 3;     // sort by revision number
    const SORT_AUTHOR = 4;  // sort by author name

    const SORT_ASCENDING = 0;   // ascending order
    const SORT_DESCENDING = 1;  // descending order

    /**
     * Attempts to return a concrete Horde_Vcs instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Horde_Vcs subclass to return.
     *                       The code is dynamically included.
     * @param array $params  A hash containing any additional configuration
     *                       or  parameters a subclass might need.
     *
     * @return Horde_Vcs  The newly created concrete instance.
     * @throws Horde_Vcs_Exception
     */
    static public function factory($driver, $params = array())
    {
        $class = 'Horde_Vcs_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Vcs_Exception($class . ' not found.');
    }
}

<?php
/**
 * IMP_Quota:: provides an API for retrieving quota details from a mail
 * server.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Quota
{
    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of concrete subclass to return.
     * @param array $params   A hash containing any additional configuration
     *                        parameters a subclass might need.
     *
     * @return IMP_Quota_Driver  The concrete instance.
     * @throws IMP_Exception
     */
    static public function factory($driver, $params = array())
    {
        $driver = basename($driver);
        $class = __CLASS__ . '_' . ucfirst($driver);

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new IMP_Exception('Could not create ' . __CLASS__ .  ' instance: ' . $driver);
    }

}

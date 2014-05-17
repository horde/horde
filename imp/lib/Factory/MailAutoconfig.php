<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for the Mail autoconfiguration object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_MailAutoconfig
extends Horde_Core_Factory_Injector
{
    /**
     * Return the mail autoconfig instance.
     *
     * @return Horde_Mail_Autoconfig  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        /* Need to manually set the drivers, since we should be using Horde
         * objects for Http_Client and Net_DNS2_Resolver. The return from
         * getDrivers() is already in priority order, so we don't need to
         * worry about that. */
        $drivers = array();
        foreach (Horde_Mail_Autoconfig::getDrivers() as $val) {
            $val = clone $val;

            if (($val instanceof Horde_Mail_Autoconfig_Driver_Guess) ||
                ($val instanceof Horde_Mail_Autoconfig_Driver_Srv)) {
                $val->dns = $injector->getInstance('Net_DNS2_Resolver');
            } elseif ($val instanceof Horde_Mail_Autoconfig_Driver_Thunderbird) {
                $val->http = $injector->getInstance('Horde_Http_Client');
            }

            $drivers[] = $val;
        }

        return new Horde_Mail_Autoconfig(array('drivers' => $drivers));
    }

}

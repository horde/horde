<?php
/**
 * A Horde_Injector based Horde_Timezone factory.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Core_Factory_Timezone extends Horde_Core_Factory_Injector
{
    /**
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        if (empty($GLOBALS['conf']['timezone']['location'])) {
            throw new Horde_Exception('Timezone database location is not configured');
        }

        return new Horde_Timezone(array(
            'cache' => $injector->getInstance('Horde_Cache'),
            'location' => $GLOBALS['conf']['timezone']['location'],
            'temp' => Horde::getTempDir()
        ));
    }
}

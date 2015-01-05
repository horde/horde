<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @link      http://www.horde.org/components/Horde_Test
 * @package   Test
 */

/**
 * Generates test hashtable object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @ignore
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @link      http://www.horde.org/components/Horde_Test
 * @package   Test
 */
class Horde_Test_Factory_Hashtable
{
    /**
     * Create a hashtable object for testing.
     *
     * @return Horde_HashTable_Memory  The hashtable object.
     * @throws Horde_Test_Exception
     */
    public function create()
    {
        if (!class_exists('Horde_HashTable_Base')) {
            throw new Horde_Test_Exception('The HashTable package is unavailable!');
        }

        return new Horde_HashTable_Memory();
    }
}

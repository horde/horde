<?php
/**
 * Test the Cache binder.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Core
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Core
 */

/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Cache binder.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Core
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Binder_CacheTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $this->injector->addBinder(
            'Horde_Cache',
            new Horde_Core_Binder_Cache()
        );
    }

    public function testInjectorReturnsCache()
    {
        $GLOBALS['conf']['cache']['driver'] = 'Null';
        $this->assertType(
            'Horde_Cache_Base',
            $this->injector->getInstance('Horde_Cache')
        );
    }

}
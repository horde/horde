<?php
/**
 * Test the Group factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Group factory.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_GroupTest extends PHPUnit_Framework_TestCase
{
    public function testMock()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $injector->bindFactory('Horde_Group', 'Horde_Core_Factory_Group', 'create');
        $GLOBALS['conf']['group']['driver'] = 'mock';
        $this->assertInstanceOf(
            'Horde_Group_Mock',
            $injector->getInstance('Horde_Group')
        );
    }
}
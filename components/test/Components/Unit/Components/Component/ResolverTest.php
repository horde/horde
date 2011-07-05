<?php
/**
 * Test the component resolver.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the component resolver.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */
class Components_Unit_Components_Component_ResolverTest
extends Components_TestCase
{
    public function testResolveName()
    {
        $resolver = $this->_getResolver();
        $this->assertInstanceOf(
            'Components_Component',
            $resolver->resolveName('Install', 'pear.horde.org', array('git'))
        );
    }

    private function _getResolver()
    {
        return new Components_Component_Resolver(
            new Components_Helper_Root(
                null, null, dirname(__FILE__) . '/../../../fixture'
            ),
            $this->getComponentFactory()
        );
    }
}

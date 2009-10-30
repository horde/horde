<?php
/**
 * Provides functions required by several Kolab_Server tests.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * Skip LDAP based tests if we don't have ldap or Net_LDAP2.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_TestCase extends PHPUnit_Framework_TestCase
{
    protected function getComposite()
    {
        return $this->getMock(
            'Horde_Kolab_Server_Composite', array(), array(), '', false
        );
    }

    protected function getMockedComposite()
    {
        return new Horde_Kolab_Server_Composite(
            $this->getMock(
                'Horde_Kolab_Server', array(), array(), '', false
            ),
            $this->getMock(
                'Horde_Kolab_Server_Objects', array(), array(), '', false
            ),
            $this->getMock(
                'Horde_Kolab_Server_Structure', array(), array(), '', false
            ),
            $this->getMock(
                'Horde_Kolab_Server_Search', array(), array(), '', false
            ),
            $this->getMock(
                'Horde_Kolab_Server_Schema', array(), array(), '', false
            )
        );
    }
}
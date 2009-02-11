<?php
/**
 * Test the server class.
 *
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
 *  We need the unit test framework
 */
require_once 'PHPUnit/Framework.php';

require_once 'Horde/Kolab/Server.php';

/**
 * Tests for the main server class.
 *
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_ServerTest extends PHPUnit_Framework_TestCase
{
    /**
     * The base class provides no abilities for reading data. So it
     * should mainly return error. But it should be capable of
     * returning a dummy Kolab user object.
     *
     * @return NULL
     */
    public function testFetch()
    {
        $ks   = &Horde_Kolab_Server::factory('none');
        $user = $ks->fetch('test');
        $this->assertEquals(KOLAB_OBJECT_USER, get_class($user));
        $cn = $user->get(KOLAB_ATTR_CN);
        $this->assertEquals('Not implemented!', $cn->message);
    }

    /**
     * The base class returns no valid data. The DN request should
     * just return false and the search for a mail address returns the
     * provided argument.
     *
     * @return NULL
     */
    public function testDnFor()
    {
        $ks = &Horde_Kolab_Server::factory('none');
        $dn = $ks->uidForIdOrMail('test');
        $this->assertEquals(false, $dn);
        $dn = $ks->uidForMailAddress('test');
        $this->assertEquals('test', $dn);
    }

}

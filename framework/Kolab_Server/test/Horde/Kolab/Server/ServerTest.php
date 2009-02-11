<?php
/**
 * Test the server class.
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
 * We need the unit test framework
 */
require_once 'PHPUnit/Framework.php';

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Tests for the main server class.
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

/**
 * A dummy class to test the original abstract class.
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
class Horde_Kolab_Server_none extends Horde_Kolab_Server
{
    /**
     * Stub for reading object data.
     *
     * @param string $uid   The object to retrieve.
     * @param string $attrs Restrict to these attributes.
     *
     * @return array|PEAR_Error An array of attributes.
     */
    public function read($uid, $attrs = null)
    {
            return PEAR::raiseError('Not implemented!');
    }

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The type of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string|PEAR_Error The UID.
     */
    protected function _generateUid($type, $id, $info)
    {
        return $id;
    }

}

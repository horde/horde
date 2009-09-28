
<?php
/**
 * Test the LDAP driver.
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
require_once 'Autoload.php';

/**
 * Test the LDAP backend.
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
class Horde_Kolab_Server_ldapTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test handling of object classes.
     *
     * @return NULL
     */
    public function testGetObjectClasses()
    {
/*       $ldap = $this->getMock('Horde_Kolab_Server_ldap', array('read')); */
/*         $ldap->expects($this->any()) */
/*             ->method('read') */
/*             ->will($this->returnValue(array ( */
/*                                           'objectClass' => */
/*                                           array ( */
/*                                               'count' => 4, */
/*                                               0 => 'top', */
/*                                               1 => 'inetOrgPerson', */
/*                                               2 => 'kolabInetOrgPerson', */
/*                                               3 => 'hordePerson', */
/*                                           ), */
/*                                           0 => 'objectClass', */
/*                                           'count' => 1))); */

/*         $classes = $ldap->getObjectClasses('cn=Gunnar Wrobel,dc=example,dc=org'); */
/*         if ($classes instanceOf PEAR_Error) { */
/*             $this->assertEquals('', $classes->getMessage()); */
/*         } */
/*         $this->assertContains('top', $classes); */
/*         $this->assertContains('kolabinetorgperson', $classes); */
/*         $this->assertContains('hordeperson', $classes); */

/*         $ldap = $this->getMock('Horde_Kolab_Server_ldap', array('read')); */
/*         $ldap->expects($this->any()) */
/*              ->method('read') */
/*              ->will($this->returnValue(PEAR::raiseError('LDAP Error: No such object: cn=DOES NOT EXIST,dc=example,dc=org: No such object'))); */

/*         $classes = $ldap->getObjectClasses('cn=DOES NOT EXIST,dc=example,dc=org'); */
/*         $this->assertEquals('LDAP Error: No such object: cn=DOES NOT EXIST,dc=example,dc=org: No such object', */
/*                             $classes->message); */
    }

    /**
     * Test retrieving a primary mail for a mail or uid.
     *
     * @return NULL
     */
/*     public function testMailForUidOrMail() */
/*     { */
/*         $ldap = $this->getMock('Horde_Kolab_Server_ldap', array('getAttributes', */
/*                                                                 'search', 'count', */
/*                                                                 'firstEntry')); */
/*         $ldap->expects($this->any()) */
/*             ->method('_getAttributes') */
/*             ->will($this->returnValue(array ( */
/*                                           'mail' => */
/*                                           array ( */
/*                                               'count' => 1, */
/*                                               0 => 'wrobel@example.org', */
/*                                           ), */
/*                                           0 => 'mail', */
/*                                           'count' => 1))); */
/*         $ldap->expects($this->any()) */
/*             ->method('_search') */
/*             ->will($this->returnValue('cn=Gunnar Wrobel,dc=example,dc=org')); */
/*         $ldap->expects($this->any()) */
/*             ->method('_count') */
/*             ->will($this->returnValue(1)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_firstEntry') */
/*             ->will($this->returnValue(1)); */

/*         $mail = $ldap->mailForIdOrMail('wrobel'); */
/*         $this->assertEquals('wrobel@example.org', $mail); */

/*         $ldap = $this->getMock('Horde_Kolab_Server_ldap', array('_getAttributes', */
/*                                                                 '_search', */
/*                                                                 '_count', */
/*                                                                 '_firstEntry', */
/*                                                                 '_errno', */
/*                                                                 '_error')); */
/*         $ldap->expects($this->any()) */
/*              ->method('_getAttributes') */
/*              ->will($this->returnValue(false)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_search') */
/*             ->will($this->returnValue('cn=Gunnar Wrobel,dc=example,dc=org')); */
/*         $ldap->expects($this->any()) */
/*             ->method('_count') */
/*             ->will($this->returnValue(1)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_firstEntry') */
/*             ->will($this->returnValue(1)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_errno') */
/*             ->will($this->returnValue(1)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_error') */
/*             ->will($this->returnValue('cn=DOES NOT EXIST,dc=example,dc=org: No such object')); */

/*         $mail = $ldap->mailForIdOrMail('wrobel'); */
/*         $this->assertEquals('Retrieving attributes failed. Error was: cn=DOES NOT EXIST,dc=example,dc=org: No such object', */
/*                             $mail->message); */

/*         $ldap = $this->getMock('Horde_Kolab_Server_ldap', array('_getAttributes', */
/*                                                                 '_search', */
/*                                                                 '_count')); */
/*         $ldap->expects($this->any()) */
/*              ->method('_getAttributes') */
/*              ->will($this->returnValue(false)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_search') */
/*             ->will($this->returnValue('cn=Gunnar Wrobel,dc=example,dc=org')); */
/*         $ldap->expects($this->any()) */
/*             ->method('_count') */
/*             ->will($this->returnValue(4)); */

/*         $mail = $ldap->mailForIdOrMail('wrobel'); */
/*         $this->assertEquals('Found 4 results when expecting only one!', */
/*                             $mail->message); */
/*     } */

/*     /\** */
/*      * Test retrieving a DN for a mail or uid. */
/*      * */
/*      * @return NULL */
/*      *\/ */
/*     public function testDnForUidOrMail() */
/*     { */
/*         $ldap = $this->getMock('Horde_Kolab_Server_ldap', array('_getDn', */
/*                                                                 '_search', '_count', */
/*                                                                 '_firstEntry')); */
/*         $ldap->expects($this->any()) */
/*             ->method('_getDn') */
/*             ->will($this->returnValue('cn=Gunnar Wrobel,dc=example,dc=org')); */
/*         $ldap->expects($this->any()) */
/*             ->method('_search') */
/*             ->will($this->returnValue('cn=Gunnar Wrobel,dc=example,dc=org')); */
/*         $ldap->expects($this->any()) */
/*             ->method('_count') */
/*             ->will($this->returnValue(1)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_firstEntry') */
/*             ->will($this->returnValue(1)); */

/*         $dn = $ldap->uidForIdOrMail('wrobel'); */
/*         $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $dn); */

/*         $ldap = $this->getMock('Horde_Kolab_Server_ldap', array('_getDn', */
/*                                                                 '_search', */
/*                                                                 '_count', */
/*                                                                 '_firstEntry', */
/*                                                                 '_errno', */
/*                                                                 '_error')); */
/*         $ldap->expects($this->any()) */
/*              ->method('_getDn') */
/*              ->will($this->returnValue(false)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_search') */
/*             ->will($this->returnValue('cn=Gunnar Wrobel,dc=example,dc=org')); */
/*         $ldap->expects($this->any()) */
/*             ->method('_count') */
/*             ->will($this->returnValue(1)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_firstEntry') */
/*             ->will($this->returnValue(1)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_errno') */
/*             ->will($this->returnValue(1)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_error') */
/*             ->will($this->returnValue('cn=DOES NOT EXIST,dc=example,dc=org: No such object')); */

/*         $dn = $ldap->uidForIdOrMail('wrobel'); */
/*         $this->assertEquals('Retrieving DN failed. Error was: cn=DOES NOT EXIST,dc=example,dc=org: No such object', */
/*                             $dn->message); */

/*         $ldap = $this->getMock('Horde_Kolab_Server_ldap', array('_getDn', */
/*                                                                 '_search', */
/*                                                                 '_count')); */
/*         $ldap->expects($this->any()) */
/*              ->method('_getDn') */
/*              ->will($this->returnValue(false)); */
/*         $ldap->expects($this->any()) */
/*             ->method('_search') */
/*             ->will($this->returnValue('cn=Gunnar Wrobel,dc=example,dc=org')); */
/*         $ldap->expects($this->any()) */
/*             ->method('_count') */
/*             ->will($this->returnValue(4)); */

/*         $dn = $ldap->uidForIdOrMail('wrobel'); */
/*         $this->assertEquals('Found 4 results when expecting only one!', */
/*                             $dn->message); */
/*     } */

}

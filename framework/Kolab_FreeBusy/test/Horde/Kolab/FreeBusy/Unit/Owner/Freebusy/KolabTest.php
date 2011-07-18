<?php
/**
 * Test the Kolab freebusy owner.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the Kolab freebusy owner.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_Owner_Freebusy_KolabTest
extends PHPUnit_Framework_TestCase
{
    public function testGetPrimaryId()
    {
        $this->assertEquals(
            'mail@example.org', $this->_getOwner()->getPrimaryId()
        );
    }

    public function testGetMail()
    {
        $this->assertEquals(
            'mail@example.org', $this->_getOwner()->getMail()
        );
    }

    public function testGetName()
    {
        $this->assertEquals('Test Test', $this->_getOwner()->getName());
    }

    public function testByUid()
    {
        $this->assertEquals(
            'Test Test',
            $this->_getDb()->getOwner('foo@example.com')->getName()
        );
    }

    public function testByAlias()
    {
        $this->assertEquals(
            'Test Test',
            $this->_getDb()->getOwner('alias@example.com')->getName()
        );
    }

    public function testByMailWithoutDomain()
    {
        $this->assertEquals(
            'Test Test',
            $this->_getDb()->getOwner('mail', array('domain' => 'example.org'))->getName()
        );
    }

    public function testByMailWithoutDomainButUser()
    {
        $this->assertEquals(
            'Test Test',
            $this->_getDb()->getOwner(
                'mail',
                array('user' => new Horde_Kolab_FreeBusy_Stub_User())
            )->getName()
        );
    }

    /**
     * @expectedException Horde_Kolab_FreeBusy_Exception
     */
    public function testByUidWithoutDomain()
    {
        $this->assertEquals(
            'Test Test',
            $this->_getDb()->getOwner('foo', array('domain' => 'example.com'))->getName()
        );
    }

    /**
     * @expectedException Horde_Kolab_FreeBusy_Exception
     */
    public function testByAliasWithoutDomain()
    {
        $this->assertEquals(
            'Test Test',
            $this->_getDb()->getOwner('alias', array('domain' => 'example.com'))->getName()
        );
    }


    //@todo: getFreeBusy*

    private function _getDb()
    {
        return new Horde_Kolab_FreeBusy_UserDb_Kolab(
            new Horde_Kolab_FreeBusy_Stub_Server()
        );
    }

    private function _getOwner($params = array())
    {
        return $this->_getDb()->getOwner('mail@example.org', $params);
    }
}
<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @copyright  2011 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Group_LdapTest extends Horde_Group_Test_Base
{
    protected static $ldap;

    public function testCreate()
    {
        $this->_create();
    }

    /**
     * @depends testCreate
     */
    public function testExists()
    {
        $this->_exists('cn=some_none_existing_id');
    }

    /**
     * @depends testExists
     */
    public function testGetName()
    {
        $this->_getName();
    }

    /**
     * @depends testExists
     */
    public function testGetData()
    {
        $this->_getData();
    }

    /**
     * @depends testExists
     */
    public function testListAll()
    {
        $this->_listAll();
    }

    /**
     * @depends testExists
     */
    public function testSearch()
    {
        $this->_search();
    }

    /**
     * @depends testExists
     */
    public function testAddUser()
    {
        $this->_addUser();
    }

    /**
     * @depends testAddUser
     */
    public function testListUsers()
    {
        $this->_listUsers();
    }

    /**
     * @depends testAddUser
     */
    public function testListGroups()
    {
        $this->_listGroups();
    }

    /**
     * @depends testListGroups
     */
    public function testRemoveUser()
    {
        $this->_removeUser();
    }

    /**
     * @depends testExists
     */
    public function testSetData()
    {
        $this->_setData();
    }

    /**
     * @depends testExists
     */
    public function testRemove()
    {
        $this->_remove();
    }

    public static function setUpBeforeClass()
    {
        if (!extension_loaded('ldap')) {
            return;
        }
        $config = self::getConfig('GROUP_LDAP_TEST_CONFIG');
        if ($config && !empty($config['group']['ldap'])) {
            self::$ldap = new Horde_Ldap($config['group']['ldap']);
            $config['group']['ldap']['ldap'] = self::$ldap;
            self::$group = new Horde_Group_Ldap($config['group']['ldap']);
        }
    }

    public static function tearDownAfterClass()
    {
        $config = self::getConfig('GROUP_LDAP_TEST_CONFIG');
        if ($config && !empty($config['group']['ldap'])) {
            $possibleids = array('My Group', 'My Other Group', 'My Second Group', 'Not My Group');
            self::$ldap->bind($config['group']['ldap']['writedn'],
                              $config['group']['ldap']['writepw']);
            foreach ($possibleids as $id) {
                try {
                    self::$ldap->delete('cn=' . $id . ',' . $config['group']['ldap']['basedn']);
                } catch (Horde_Ldap_Exception $e) {
                }
            }
            self::$ldap = null;
        }
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        if (!self::$ldap) {
            $this->markTestSkipped('No ldap extension or no ldap configuration');
        }
    }
}

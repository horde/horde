<?php

 require_once __DIR__ . '/../Base.php';

/**
 *
 */
class Content_Test_Sql_Base extends Content_Test_Base
{
    /**
     * @static Horde_Db_Adapter_Base
     */
    static $db;

    /**
     * @static Horde_Injector
     */
    static $injector;

    /**
     * @static Horde_Db_Migration_Migrator
     */
    static $migrator;

    static $reason;

    public function testCreate()
    {
        $this->_create();
        $objects = self::$db->selectAll('SELECT * FROM rampage_objects');
        $this->assertEquals(4, count($objects));
        // If these aren't strings, then ids were taken as names.
        foreach ($objects as $object) {
            $this->assertInternalType('string', $object['object_name']);
        }

        $types = self::$db->selectAll('SELECT * FROM rampage_types');
        $this->assertEquals(2, count($types));
        foreach ($types as $type) {
            $this->assertInternalType('string', $type['type_name']);
        }
    }

    /**
     * @depends testCreate
     */
    public function testEnsureTags()
    {
        $this->_testEnsureTags();
    }

    /**
     * @depends testCreate
     */
    public function testFullTagCloudSimple()
    {
        $this->_testFullTagCloudSimple();
    }

    /**
     * @depends testCreate
     */
    public function testTagCloudByType()
    {
        $this->_testTagCloudByType();
    }

    /**
     * @depends testCreate
     */
     public function testTagCloudByUser()
     {
         $this->_testTagCloudByUser();
     }

     /**
      * @depends testCreate
      */
     public function testTagCloudByUserType()
     {
         $this->_testTagCloudByUserType();
     }

     /**
      * @depends testCreate
      */
     public function testTagCloudByTagType()
     {
         $this->_testTagCloudByTagType();
     }

     /**
      * @depends testCreate
      */
     public function testTagCloudByTagIds()
     {
         $this->_testTagCloudByTagIds();
     }

    /**
     * @depends testCreate
     */
    public function testGetRecentTags()
    {
        $this->_testGetRecentTags();
    }

    /**
     * @depends testCreate
     */
    public function testGetRecentTagsByUser()
    {
        $this->_testGetRecentTagsByUser();
    }

    /**
     * @depends testCreate
     */
    public function testGetRecentObjects()
    {
        $this->_testGetRecentObjects();
    }

    /**
     * @depends testCreate
     */
    public function testGetRecentTagsByType()
    {
        $this->_testGetRecentTagsByType();
    }

    /**
     * @depends testCreate
     */
    public function testGetRecentObjectsByUser()
    {
        $this->_testGetRecentObjectsByUser();
    }

    /**
     * @depends testCreate
     */
    public function testGetRecentObjectsByType()
    {
        $this->_testGetRecentObjectsByType();
    }

    /**
     *
     * @depends testCreate
     */
    public function testGetRecentUsers()
    {
        $this->_testGetRecentUsers();
    }

    /**
     * @depends testCreate
     */
    public function testGetRecentUsersByType()
    {
        $this->_testGetRecentUsersByType();
    }

    /**
     * @depends testCreate
     */
    public function testUntag()
    {
        $this->_testUntag();
    }

    public static function setUpBeforeClass()
    {
        self::$injector = new Horde_Injector(new Horde_Injector_TopLevel());
        self::$injector->setInstance('Horde_Db_Adapter', self::$db);

        // FIXME: get migration directory if not running from Git checkout.
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null, //$logger,
            array('migrationsPath' => __DIR__ . '/../../../migration',
                  'schemaTableName' => 'content_test_schema'));

        self::$migrator->up();
        self::$tagger = self::$injector->getInstance('Content_Tagger');
    }

    public static function tearDownAfterClass()
    {
        if (self::$migrator) {
            self::$migrator->down();
        }
        self::$db = null;
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        if (!self::$db) {
            $this->markTestSkipped(self::$reason);
        }
    }

}
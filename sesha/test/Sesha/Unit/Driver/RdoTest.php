<?php
/**
 * Test the Rdo backend driver.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Sesha
 * @subpackage UnitTests
 * @author     Ralf Lang <lang@b1-systems.de>
 * @link       http://www.horde.org/apps/sesha
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';


class Sesha_Unit_Driver_RdoTest extends Sesha_TestCase
{

    protected function setUp()
    {
        self::$db->delete("DELETE FROM sesha_categories");
        $categoryAddSql = 'INSERT INTO sesha_categories' .
               ' (category, description, priority)' .
               ' VALUES ("books", "Book inventory", "3")';

        self::$db->insert($categoryAddSql);

    }

    public function testSetup()
    {
        $driver = self::$driver;
        $this->assertInstanceOf('Sesha_Driver', $driver);
    }

    public function testCategoryExists() {
        $this->assertTrue(self::$driver->categoryExists('books'));
    }

    public function testAddCategory() {
        $category = array( 'category' => 'fish',
            'description' => 'Frutti di mare',
            'priority' => '2'
        );
        $this->assertInstanceOf('Sesha_Entity_Category',self::$driver->addCategory($category));
    }

}
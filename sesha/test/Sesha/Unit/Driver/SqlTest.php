<?php
/**
 * Test the Sql backend driver.
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
require_once dirname(__FILE__) . '/../../Autoload.php';


class Sesha_Unit_Driver_SqlTest extends Sesha_TestCase
{
    public function testSetup()
    {
        $driver = self::$driver;
        $this->assertInstanceOf('Sesha_Driver', $driver);
    }
}
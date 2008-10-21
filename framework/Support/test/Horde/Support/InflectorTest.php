<?php
/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2007-2008 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * @group      support
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2007-2008 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_InflectorTest extends PHPUnit_Framework_TestCase
{
    /**
     * Words to test
     *
     * @var array $words
     */
    public $words = array(
        'sheep' => 'sheep',
        'man' => 'men',
        'woman' => 'women',
        'user' => 'users',
        'foot' => 'feet',
        'hive' => 'hives',
        'chive' => 'chives',
        'event' => 'events',
        'task' => 'tasks',
        'preference' => 'preferences',
        'child' => 'children',
        'moose' => 'moose',
        'mouse' => 'mice',
    );

    public function setUp()
    {
        $this->inflector = new Horde_Support_Inflector;
    }

    public function testSingularizeAndPluralize()
    {
        foreach ($this->words as $singular => $plural) {
            $this->assertEquals($plural, $this->inflector->pluralize($singular));
            $this->assertEquals($singular, $this->inflector->singularize($plural));
        }
    }

    public function testCamelize()
    {
        // underscore => camelize
        $this->assertEquals('Test', $this->inflector->camelize('test'));
        $this->assertEquals('TestCase', $this->inflector->camelize('test_case'));
        $this->assertEquals('Test/Case', $this->inflector->camelize('test/case'));
        $this->assertEquals('TestCase/Name', $this->inflector->camelize('test_case/name'));

        // already camelized
        $this->assertEquals('Test', $this->inflector->camelize('Test'));
        $this->assertEquals('TestCase', $this->inflector->camelize('testCase'));
        $this->assertEquals('TestCase', $this->inflector->camelize('TestCase'));
        $this->assertEquals('Test/Case', $this->inflector->camelize('Test_Case'));
    }

    public function testCamelizeLower()
    {
        // underscore => camelize
        $this->assertEquals('test', $this->inflector->camelize('test', 'lower'));
        $this->assertEquals('testCase', $this->inflector->camelize('test_case', 'lower'));
        $this->assertEquals('test/case', $this->inflector->camelize('test/case', 'lower'));
        $this->assertEquals('testCase/name', $this->inflector->camelize('test_case/name', 'lower'));

        // already camelized
        $this->assertEquals('test', $this->inflector->camelize('Test', 'lower'));
        $this->assertEquals('testCase', $this->inflector->camelize('testCase', 'lower'));
        $this->assertEquals('testCase', $this->inflector->camelize('TestCase', 'lower'));
        $this->assertEquals('test/case', $this->inflector->camelize('Test_Case', 'lower'));
    }

}

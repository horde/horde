<?php
/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2007-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @group      support
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2007-2009 The Horde Project (http://www.horde.org/)
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

    public function testTitleize()
    {
        $this->markTestSkipped();
    }

    /**
     * data given to underscore() MUST be camelized already
     */
    public function testUnderscore()
    {
        // most common scenarios (camelize => underscore)
        $this->assertEquals('derek',            $this->inflector->underscore('Derek'));
        $this->assertEquals('dereks_test',      $this->inflector->underscore('dereksTest'));
        $this->assertEquals('dereks_test',      $this->inflector->underscore('DereksTest'));
        $this->assertEquals('dereks_test',      $this->inflector->underscore('Dereks_Test'));
        $this->assertEquals('dereks_name_test', $this->inflector->underscore('DereksName_Test'));

        // not as common (already underscore)
        $this->assertEquals('derek',       $this->inflector->underscore('derek'));
        $this->assertEquals('dereks_test', $this->inflector->underscore('dereks_test'));
    }

    public function testDasherize()
    {
        $this->assertEquals('derek',            $this->inflector->dasherize('Derek'));
        $this->assertEquals('dereks-test',      $this->inflector->dasherize('dereksTest'));
        $this->assertEquals('dereks-test',      $this->inflector->dasherize('DereksTest'));
        $this->assertEquals('dereks-test',      $this->inflector->dasherize('Dereks_Test'));
        $this->assertEquals('dereks-name-test', $this->inflector->dasherize('DereksName_Test'));
        $this->assertEquals('derek',            $this->inflector->dasherize('derek'));
        $this->assertEquals('dereks-test',      $this->inflector->dasherize('dereks_test'));
    }

    public function testHumanize()
    {
        // most common scenarios (column name => human)
        $this->assertEquals('Derek',          $this->inflector->humanize('derek'));
        $this->assertEquals('Dereks test',    $this->inflector->humanize('dereks_test'));
        $this->assertEquals('Dereks test',    $this->inflector->humanize('dereks_test_id'));

        // not as common (columns are usually underscored)
        $this->assertEquals('Derek',          $this->inflector->humanize('Derek'));
        $this->assertEquals('Dereks',         $this->inflector->humanize('Dereks'));
        $this->assertEquals('Dereks test',    $this->inflector->humanize('dereksTest'));
        $this->assertEquals('Dereks test',    $this->inflector->humanize('dereksTestId'));
        $this->assertEquals('Dereks test',    $this->inflector->humanize('DereksTest'));
        $this->assertEquals('Dereks test',    $this->inflector->humanize('Dereks_Test'));
    }

    public function testDemodularize()
    {
        $this->assertEquals('Stuff', $this->inflector->demodulize('Fax_Job_Stuff'));
        $this->assertEquals('Job',   $this->inflector->demodulize('Fax_Job'));
        $this->assertEquals('Fax',   $this->inflector->demodulize('Fax'));
    }

    /**
     * to table formatted string
     */
    public function testTableize()
    {
        // most common scenarios (class => table)
        $this->assertEquals('dereks',       $this->inflector->tableize('Derek'));
        $this->assertEquals('dereks',       $this->inflector->tableize('Dereks'));
        $this->assertEquals('dereks_tests', $this->inflector->tableize('dereksTest'));
        $this->assertEquals('dereks_tests', $this->inflector->tableize('DereksTest'));
        $this->assertEquals('dereks_tests', $this->inflector->tableize('Dereks_Test'));
        $this->assertEquals('dereks_tests', $this->inflector->tableize('Dereks/Test'));

        // not as common (already underscore)
        $this->assertEquals('dereks',       $this->inflector->tableize('derek'));
        $this->assertEquals('dereks_tests', $this->inflector->tableize('dereks_test'));
        $this->assertEquals('dereks_tests', $this->inflector->tableize('dereks/test'));
    }

    /**
     * to class formatted string
     */
    public function testClassify()
    {
        $this->assertEquals('Derek',       $this->inflector->classify('derek'));
        $this->assertEquals('DereksTest',  $this->inflector->classify('dereks_test'));

        // not as common
        $this->assertEquals('Derek',       $this->inflector->classify('Derek'));
        $this->assertEquals('Derek',       $this->inflector->classify('Dereks'));
        $this->assertEquals('DereksTest',  $this->inflector->classify('dereksTest'));
        $this->assertEquals('DereksTest',  $this->inflector->classify('DereksTest'));
        $this->assertEquals('Dereks_Test', $this->inflector->classify('Dereks_Test'));
    }

    public function testForeignKey()
    {
        $this->markTestSkipped();
    }

    public function testOrdinalize()
    {
        $this->markTestSkipped();
    }


    /*##########################################################################
    # Inflection Cache
    ##########################################################################*/

    // test setting inflection
    public function testSetCache()
    {
        $this->inflector->setCache('documents', 'singularize', 'document');
        $this->assertEquals('document', $this->inflector->getCache('documents', 'singularize'));
    }

    // test setting inflection
    public function testClearCache()
    {
        $this->inflector->setCache('documents', 'singularize', 'document');
        $this->inflector->clearCache();
        $this->assertEquals(false, $this->inflector->getCache('documents', 'singularize'));
    }

}

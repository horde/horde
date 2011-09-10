<?php
/**
 * Horde_Yaml_Node test
 *
 * @author  Mike Naberezny <mike@maintainable.com>
 * @license http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Yaml
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Yaml
 * @subpackage UnitTests
 */
class Horde_Yaml_DumperTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->dumper = new Horde_Yaml_Dumper();
    }

    // Pass-thru in Horde_Yaml

    public function testPassThruConvenienceFunction()
    {
        $value = array();

        // Disable any external dumper function
        $oldDumpFunc = Horde_Yaml::$dumpfunc;
        Horde_Yaml::$dumpfunc = false;

        $expected = $this->dumper->dump($value);
        $actual = Horde_Yaml::dump($value);

        // Restore the dumper function
        Horde_Yaml::$dumpfunc = $oldDumpFunc;

        $this->assertEquals($expected, $actual);
    }

    // Argument checking

    public function testThrowsWhenOptionsIsNotArray()
    {
        $options = 42;
        try {
            $this->dumper->dump(array(), $options);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertRegExp('/options.*array/i', $e->getMessage());
        }
    }

    public function testThrowsWhenOptionIndentIsNotAnInteger()
    {
        $options = array('indent' => 'foo');
        try {
            $this->dumper->dump(array(), $options);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertRegExp('/indent.*integer/i', $e->getMessage());
        }
    }

    public function testThrowsWhenOptionWordwrapIsNotAnInteger()
    {
        $options = array('wordwrap' => 'foo');
        try {
            $this->dumper->dump(array(), $options);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertRegExp('/wordwrap.*integer/i', $e->getMessage());
        }
    }

    // Dumping

    public function testDumpAlwaysStartsNewYamlDocument()
    {
        $dump = $this->dumper->dump(array());
        $this->assertRegExp("/^---\n/", $dump);
    }

    public function testNumericStrings()
    {
        $value = array('foo' => '73,123');
        $this->assertSame($value, Horde_Yaml::load(Horde_Yaml::dump($value)));
    }

    public function testDumpsArrayAsMap()
    {
        $value = array('foo' => 'bar');

        $expected = "---\nfoo: bar\n";
        $actual = $this->dumper->dump($value);
        $this->assertEquals($expected, $actual);
    }

    public function testDumpsTraversableAsMap()
    {
        $value = new ArrayObject(array('foo' => 'bar'));

        $expected = "---\nfoo: bar\n";
        $actual = $this->dumper->dump($value);
        $this->assertEquals($expected, $actual);
    }

    public function testMovedArray()
    {
        return $this->markTestSkipped();

        $arr = array_flip(range(1, 1000));
        $string = $this->dumper->dump($arr);
        $arr2 = Horde_Yaml::load($string);

        $this->assertEquals($arr, $arr2);
    }

    public function testMixedArray()
    {
        $arr = array('test', 'a' => 'test2', 'test3');

        $string = $this->dumper->dump($arr);
        $arr2 = Horde_Yaml::load($string);

        $this->assertEquals($arr2, $arr);
    }

    public function testNegativeKeysArray()
    {
        $this->markTestSkipped();

        $arr = array(-1 => 'test', -2 => 'test2', 0 => 'test3');

        $string = $this->dumper->dump($arr);
        $arr2 = Horde_Yaml::load($string);

        $this->assertEquals($arr, $arr2);
    }

    public function testInfinity()
    {
        $value = array('foo' => INF);

        $expected = "---\nfoo: .INF\n";
        $actual = $this->dumper->dump($value);
        $this->assertEquals($expected, $actual);
    }

    public function testNegativeInfinity()
    {
        $value = array('foo' => -INF);

        $expected = "---\nfoo: -.INF\n";
        $actual = $this->dumper->dump($value);
        $this->assertEquals($expected, $actual);
    }

    public function testNan()
    {
        $value = array('foo' => NAN);

        $expected = "---\nfoo: .NAN\n";
        $actual = $this->dumper->dump($value);
        $this->assertEquals($expected, $actual);
    }

    public function testSerializable()
    {
        $value = array('obj' => new Horde_Yaml_Test_Serializable('s'));

        $expected = "---\nobj: >\n  !php/object::Horde_Yaml_Test_Serializable\n  s\n";
        $actual = $this->dumper->dump($value);
        $this->assertEquals($expected, $actual);
    }

    public function testDumpSequence()
    {
        $value = array('foo', 'bar');

        $expected = "---\n"
                  . "- foo\n"
                  . "- bar\n";
        $actual = $this->dumper->dump($value);

        $this->assertEquals($expected, $actual);
    }

    public function testShouldWrapStringsWithCommentDelimiterInQuotes()
    {
        $value = array('foo' => 'string # this is not a comment');
        $expected = "---\n"
                    . "foo: '{$value['foo']}'\n";
        $actual = $this->dumper->dump($value);
        $this->assertEquals($expected, $actual);
        // round-trip assert
        $this->assertEquals($value, Horde_Yaml::load($actual), "Error with: >{$actual}<");
    }

    public function testShouldNotWrapStringsWithCommentDelimiterForFoldedStrings()
    {
        // stringWithHash: 'string # this is part of the string, not a comment'
        $value = array('foo' => 'string # this is not a comment but it is a long string that gets folded', 'bar' => 2);
        $expected = "---\n"
                    . "foo: >\n"
                    . "  string # this is not a comment but it is\n"
                    . "  a long string that gets folded\n"
                    . "bar: 2\n";

        $actual = $this->dumper->dump($value);
        $this->assertEquals($expected, $actual);
        // round-trip assert
        $this->assertEquals($value, Horde_Yaml::load($actual), "Error: expected: " . print_r($value, true) . ", actual: " . print_r(Horde_Yaml::load($actual), true) . ", from: >" . $actual . "<"); // fails presently due to bug in loader which causes an extra "\n" at end of string
    }

}

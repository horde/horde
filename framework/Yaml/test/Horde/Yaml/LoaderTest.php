<?php
/**
 * Horde_Yaml_Loader test
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Yaml
 * @subpackage UnitTests
 */

require_once __DIR__ . '/Autoload.php';

/**
 * @category   Horde
 * @package    Yaml
 * @subpackage UnitTests
 */
class Horde_Yaml_LoaderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Horde_Yaml::$loadfunc = 'nonexistant_callback';
    }

    // Loading: load()

    public function testLoad()
    {
        $expected = array('foo' => 'bar');
        $actual = Horde_Yaml::load('foo: bar');

        $this->assertEquals($expected, $actual);
    }

    public function testLoadUsesCallbackForParsingIfAvailable()
    {
        Horde_Yaml::$loadfunc = 'Horde_Yaml_LoaderTest_MockLoader::returnArray';

        $yaml = 'foo';
        $expected = Horde_Yaml_LoaderTest_MockLoader::returnArray($yaml);
        $actual   = Horde_Yaml::load($yaml);

        $this->assertEquals($expected, $actual);
    }

    public function testLoadThrowsWhenInputStringIsNotString()
    {
        $notString = 42;
        try {
            Horde_Yaml::load($notString);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertRegExp('/must be a string/i', $e->getMessage());
        }
    }

    public function testLoadThrowsWhenInputStringIsEmpty()
    {
        $emptyString = '';
        try {
            Horde_Yaml::load($emptyString);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertRegExp('/cannot be empty/i', $e->getMessage());
        }
    }

    public function testLoadReturnsEmptyArrayWhenStringCannotBeParsedAsYaml()
    {
        $notYaml = 'notyaml';
        $this->assertEquals(array(), Horde_Yaml::load($notYaml));
    }

    // Loading: loadFile()

    public function testLoadFile()
    {
        $parsed = Horde_Yaml::loadFile($this->fixture('basic'));
        $this->assertEquals('bar', $parsed['foo']);
    }

    public function testLoadFileThrowsWhenFilenameIsNotString()
    {
        $notString = 42;
        try {
            Horde_Yaml::loadFile($notString);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertRegExp('/must be a string/i', $e->getMessage());
        }
    }

    public function testLoadFileThrowsWhenFilenameIsEmptyString()
    {
        $emptyString = '';
        try {
            Horde_Yaml::loadFile($emptyString);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertRegExp('/cannot be empty/i', $e->getMessage());
        }
    }

    public function testLoadFileThrowsWhenFilenameCannotBeOpened()
    {
        $nonexistant = '/path/to/a/nonexistant/filename';
        try {
            Horde_Yaml::loadFile($nonexistant);
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertRegExp('/failed to open/i', $e->getMessage());
        }
    }

    // Loading: loadStream()

    public function testLoadStream()
    {
        $fp = fopen($this->fixture('basic'), 'rb');
        $parsed = Horde_Yaml::loadStream($fp);
        $this->assertEquals('bar', $parsed['foo']);
    }

    public function testLoadStreamThrowsWhenStreamIsNotResource()
    {
        $notResource = 42;
        try {
            Horde_Yaml::loadStream($notResource);
        } catch (InvalidArgumentException $e) {
            $this->assertRegExp('/stream resource/i', $e->getMessage());
        }
    }

    public function testLoadStreamThrowsWhenStreamIsResourceButNotStream()
    {
        $resourceButNotStream = xml_parser_create();
        $this->assertInternalType('resource', $resourceButNotStream);

        try {
            Horde_Yaml::loadStream($resourceButNotStream);
        } catch (InvalidArgumentException $e) {
            $this->assertRegExp('/stream resource/i', $e->getMessage());
        }
    }

    public function testLoadStreamUsesCallbackForParsingIfAvailable()
    {
        Horde_Yaml::$loadfunc = 'Horde_Yaml_LoaderTest_MockLoader::returnArray';

        $stream = fopen('php://memory', 'r');
        $expected = Horde_Yaml_LoaderTest_MockLoader::returnArray($stream);
        $actual   = Horde_Yaml::loadStream($stream);

        $this->assertEquals($expected, $actual);
    }

    // Parsing: Mappings

    public function testMappingStringValue()
    {
        $yaml = "String: Anyone's name, really.";
        $parsed = Horde_Yaml::load($yaml);
        $this->assertEquals("Anyone's name, really.", $parsed['String']);
    }

    public function testMappingIntegerValue()
    {
        $yaml = 'Int: 13';
        $parsed = Horde_Yaml::load($yaml);
        $this->assertEquals(13, $parsed['Int']);
    }

    public function testMappingIntegerZeroValue()
    {
        $yaml = 'Zero: 0';
        $parsed = Horde_Yaml::load($yaml);
        $this->assertSame(0, $parsed['Zero']);
    }

    public function testMappingFloatValue()
    {
        $yaml = 'Float: 5.34';
        $parsed = Horde_Yaml::load($yaml);
        $this->assertEquals(5.34, $parsed['Float']);
    }

    public function testMappingBooleanTrue()
    {
        $trues = array('TRUE', 'True', 'true', 'On', 'on', '+', 'YES', 'Yes', 'yes');
        foreach ($trues as $true) {
            $yaml = "True: $true";
            $parsed = Horde_Yaml::load($yaml);
            $this->assertTrue($parsed['True'], $true);
        }
    }

    public function testMappingBooleanFalse()
    {
        $falses = array('FALSE', 'False', 'false', 'Off', 'off', '-', 'NO', 'No', 'no');
        foreach ($falses as $false) {
            $yaml = "False: $false";
            $parsed = Horde_Yaml::load($yaml);
            $this->assertFalse($parsed['False'], $false);
        }
    }

    public function testMappingNullValue()
    {
        $nulls = array('NULL', 'Null', 'null', '', '~');
        foreach ($nulls as $null) {
            $yaml = "Null: $null";
            $parsed = Horde_Yaml::load($yaml);
            $this->assertNull($parsed['Null'], $null);
        }
    }

    public function testMappedValueWithFoldedBlock()
    {
        $parsed = Horde_Yaml::loadFile($this->fixture('basic'));

        $expected = "There isn't any time for your tricks!\nDo you understand?\n";
        $actual = $parsed['no time'];
        $this->assertEquals($expected, $actual);
    }

    public function testMappedValueWithMapping()
    {
        $yaml = "foo:\n"
              . "  bar: baz";
        $expected = array('foo' => array('bar' => 'baz'));
        $actual   = Horde_Yaml::load($yaml);
        $this->assertEquals($expected, $actual);
    }

    // Parsing: Types

    public function testFloatExponential()
    {
        $this->assertSame(array('e' => 10.0), Horde_Yaml::load('e: 1.0e+1'));
        $this->assertSame(array('e' => 0.1), Horde_Yaml::load('e: 1.0e-1'));
    }

    public function testInfinity()
    {
        $this->assertSame(array('i' => INF), Horde_Yaml::load('i: .inf'));
        $this->assertSame(array('i' => INF), Horde_Yaml::load('i: .Inf'));
        $this->assertSame(array('i' => INF), Horde_Yaml::load('i: .INF'));
    }

    public function testNegativeInfinity()
    {
        $this->assertSame(array('i' => -INF), Horde_Yaml::load('i: -.inf'));
        $this->assertSame(array('i' => -INF), Horde_Yaml::load('i: -.Inf'));
        $this->assertSame(array('i' => -INF), Horde_Yaml::load('i: -.INF'));
    }

    public function testNan()
    {
        // NAN !== NAN, but NAN == NAN
        $this->assertEquals(array('n' => NAN), Horde_Yaml::load('n: .nan'));
        $this->assertEquals(array('n' => NAN), Horde_Yaml::load('n: .NaN'));
        $this->assertEquals(array('n' => NAN), Horde_Yaml::load('n: .NAN'));
    }

    public function testArray()
    {
        $this->assertEquals(array('a' => array()), Horde_Yaml::load('a: []'));
        $this->assertEquals(array('a' => array('a', 'b', 'c')), Horde_Yaml::load('a: [a, b, c]'));
        $this->assertEquals(array('a' => array()), Horde_Yaml::load('a: !php/array []'));

        // ArrayObject implements ArrayAccess: OK
        $this->assertEquals(array('ao' => new ArrayObject()), Horde_Yaml::load('ao: !php/array::ArrayObject []'));
        $this->assertEquals(array('ao' => new ArrayObject(array(1, 2, 3))), Horde_Yaml::load('ao: !php/array::ArrayObject [1, 2, 3]'));

        // Horde_Yaml_Test_NotSerializable doesn't implement ArrayAccess: FAILURE
        Horde_Yaml::$allowedClasses[] = 'Horde_Yaml_Test_NotSerializable';
        try {
            Horde_Yaml::load('array: !php/array::Horde_Yaml_Test_NotSerializable []');
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertEquals('Horde_Yaml_Test_NotSerializable does not implement ArrayAccess', $e->getMessage());
        }

        // Horde_Yaml_Test_OtherClass doesn't exist: FAILURE
        Horde_Yaml::$allowedClasses[] = 'Horde_Yaml_Test_OtherClass';
        try {
            Horde_Yaml::load('array: !php/array::Horde_Yaml_Test_OtherClass []');
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertEquals('Horde_Yaml_Test_OtherClass is not defined', $e->getMessage());
        }

        // Horde_Yaml_Test_Disallowed is not whitelisted
        try {
            Horde_Yaml::load('array: !php/array::Horde_Yaml_Test_Disallowed []');
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertEquals('Horde_Yaml_Test_Disallowed is not in the list of allowed classes', $e->getMessage());
        }
    }

    public function testHash()
    {
        $this->assertEquals(array('a' => array()), Horde_Yaml::load('a: {}'));
        $this->assertEquals(array('a' => array('a', 'b', 'c')), Horde_Yaml::load('a: {0: a, 1: b, 2: c}'));

        // ArrayObject implements ArrayAccess: OK
        $this->assertEquals(array('ao' => new ArrayObject()), Horde_Yaml::load('ao: !php/hash::ArrayObject {}'));
        $this->assertEquals(
            array('ao' => new ArrayObject(array('a' => 1, 'b' => 2, 3 => 3, 4 => 'd', 'e' => 5))),
            Horde_Yaml::load('ao: !php/hash::ArrayObject {a: 1, b: 2, 3: 3, 4: d, e: 5}')
        );

        // Horde_Yaml_Test_NotSerializable doesn't implement ArrayAccess: FAILURE
        Horde_Yaml::$allowedClasses[] = 'Horde_Yaml_Test_NotSerializable';
        try {
            Horde_Yaml::load('hash: !php/hash::Horde_Yaml_Test_NotSerializable {}');
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertEquals('Horde_Yaml_Test_NotSerializable does not implement ArrayAccess', $e->getMessage());
        }

        // Horde_Yaml_Test_OtherClass doesn't exist: FAILURE
        Horde_Yaml::$allowedClasses[] = 'Horde_Yaml_Test_OtherClass';
        try {
            Horde_Yaml::load('hash: !php/hash::Horde_Yaml_Test_OtherClass {}');
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertEquals('Horde_Yaml_Test_OtherClass is not defined', $e->getMessage());
        }

        // Horde_Yaml_Test_Disallowed is not whitelisted
        try {
            Horde_Yaml::load('hash: !php/hash::Horde_Yaml_Test_Disallowed []');
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertEquals('Horde_Yaml_Test_Disallowed is not in the list of allowed classes', $e->getMessage());
        }
    }

    public function testSerializable()
    {
        Horde_Yaml::$allowedClasses[] = 'Horde_Yaml_Test_Serializable';
        $result = Horde_Yaml::load('obj: >
  !php/object::Horde_Yaml_Test_Serializable
  string');

        $this->assertInstanceOf('Horde_Yaml_Test_Serializable', $result['obj']);
        $this->assertSame('string', $result['obj']->test());

        // Horde_Yaml_Test_NotSerializable doesn't implement Serializable: FAILURE
        Horde_Yaml::$allowedClasses[] = 'Horde_Yaml_Test_NotSerializable';
        try {
            Horde_Yaml::load('o: !php/object::Horde_Yaml_Test_NotSerializable string');
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertEquals('Horde_Yaml_Test_NotSerializable does not implement Serializable', $e->getMessage());
        }

        // Horde_Yaml_Test_Disallowed is not whitelisted
        try {
            Horde_Yaml::load('o: !php/object::Horde_Yaml_Test_Disallowed string');
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertEquals('Horde_Yaml_Test_Disallowed is not in the list of allowed classes', $e->getMessage());
        }
    }

    // Parsing: Sequences

    public function testSequenceBasic()
    {
        $yaml = "- PHP Class\n"
              . "- Basic YAML Loader\n"
              . "- Very Basic YAML Dumper";
        $parsed = Horde_Yaml::load($yaml);

        $this->assertEquals("PHP Class", $parsed[0]);
        $this->assertEquals("Basic YAML Loader", $parsed[1]);
        $this->assertEquals("Very Basic YAML Dumper", $parsed[2]);
    }

    public function testSequenceOfSequence()
    {
        $yaml = "-\n"
              . "  - YAML is so easy to learn.\n"
              . "  - Your config files will never be the same.";
        $parsed = Horde_Yaml::load($yaml);

        $expected = array("YAML is so easy to learn.",
                          "Your config files will never be the same.");
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    public function testSequenceofMappings()
    {
        $yaml = "-\n"
              . "  cpu: 1.5ghz\n"
              . "  ram: 1 gig\n"
              . "  os : os x 10.4.1";
        $parsed = Horde_Yaml::load($yaml);

        $expected = array("cpu" => "1.5ghz", "ram" => "1 gig", "os" => "os x 10.4.1");
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual, 'Sequence of mappings');
    }

    public function testMappedSequence()
    {
        $yaml = "domains:\n"
              . "  - yaml.org\n"
              . "  - php.net\n";
        $parsed = Horde_Yaml::load($yaml);

        $expected = array("yaml.org", "php.net");
        $actual = $parsed['domains'];
        $this->assertEquals($expected, $actual);
    }

    public function testSequenceWithMappedValuesStartingWithCaps()
    {
        $yaml = "- program: Adium\n"
              . "  platform: OS X\n"
              . "  type: Chat Client\n";
        $parsed = Horde_Yaml::load($yaml);

        $expected = array("program" => "Adium", "platform" => "OS X", "type" => "Chat Client");
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    // Parsing: References

    public function testReferencesAssignment1()
    {
        $parsed = Horde_Yaml::loadFile($this->fixture('references'));

        $expected = array('Perl', 'Python', 'PHP', 'Ruby');
        $actual = $parsed['dynamic languages'];
        $this->assertEquals($expected, $actual);
    }

    public function testReferencesAssignment2()
    {
        $parsed = Horde_Yaml::loadFile($this->fixture('references'));

        $expected = array('C/C++', 'Java');
        $actual = $parsed['compiled languages'];
        $this->assertEquals($expected, $actual);
    }

    public function testReferenceUsage()
    {
        $parsed = Horde_Yaml::loadFile($this->fixture('references'));

        $assignment1 = array('Perl', 'Python', 'PHP', 'Ruby');
        $assignment2 = array('C/C++', 'Java');

        $expected = array($assignment1, $assignment2);
        $actual = $parsed['all languages'];

        $this->assertEquals($expected, $actual);
    }

    // Parsing: Inlines

    public function testInlinedSequence()
    {
        $yaml = '- [One, Two, Three, Four]';
        $parsed = Horde_Yaml::load($yaml);

        $expected = array("One", "Two", "Three", "Four");
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    public function testInlineSequenceWithQuotes()
    {
        $yaml = "- ['complex: string', 'another [string]']";
        $parsed = Horde_Yaml::load($yaml);

        $expected = array('complex: string', 'another [string]');
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    public function testInlineSequenceOneDeep()
    {
        $yaml = '- [One, [Two, And, Three], Four, Five]';
        $parsed = Horde_Yaml::load($yaml);

        $expected = array("One", array("Two", "And", "Three"), "Four", "Five");
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    public function testInlineSequenceOneDeepWithQuotes()
    {
        $yaml = '- [a, [\'1\', "2"], b]';
        $parsed = Horde_Yaml::load($yaml);

        $expected = array('a', array('1', '2'), 'b');
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    public function testInlineSequenceTwoDeep()
    {
        $yaml = '- [This, [Is, Getting, [Ridiculous, Guys]], Seriously, [Show, Mercy]]';
        $parsed = Horde_Yaml::load($yaml);

        $expected = array("This", array("Is", "Getting", array("Ridiculous", "Guys")),
                                            "Seriously", array("Show", "Mercy"));
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    public function testInlineSequenceWhenEmpty()
    {
        $yaml = '- []';
        $parsed = Horde_Yaml::load($yaml);

        $expected = array();
        $actual = $parsed[0];
        $this->assertEquals($actual, $expected);
    }

    public function testInlineSequenceWhenEmptyWithWhitespace()
    {
        $yaml = "- [ \t]";
        $parsed = Horde_Yaml::load($yaml);

        $expected = array();
        $actual = $parsed[0];
        $this->assertEquals($actual, $expected);
    }

    public function testInlineMapping()
    {
        $yaml = '- {name: chris, age: young, brand: lucky strike}';
        $parsed = Horde_Yaml::load($yaml);

        $expected = array("name" => "chris", "age" => "young", "brand" => "lucky strike");
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    public function testInlineMappingWhenEmpty()
    {
        $yaml = '- {}';
        $parsed = Horde_Yaml::load($yaml);

        $expected = array();
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    public function testInlineMappingWhenEmptyWithWhitespace()
    {
        $yaml = "- { \t}";
        $parsed = Horde_Yaml::load($yaml);

        $expected = array();
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    public function testInlineMappingWithQuotesInlinedInSequence()
    {
        $yaml = '- {name: "Foo, Bar\'s", age: 20}';
        $parsed = Horde_Yaml::load($yaml);

        $expected = array('name' => "Foo, Bar's", 'age' => 20);
        $actual = $parsed[0];

        $this->assertEquals($expected, $actual);
    }


    public function testInlineMappingWithQuotesInlinedInMapping()
    {
        $yaml = 'outer: { inner1: "foo bar", inner2: \'baz qux\' }';

        $expected = array('outer' => array('inner1' => "foo bar", 'inner2' => "baz qux"));
        $actual = Horde_Yaml::load($yaml);
        $this->assertEquals($expected, $actual);
    }

    public function testInlineMappingOneDeep()
    {
        $yaml = "- {name: mark, age: older than chris, brand: [marlboro, lucky strike]}";
        $parsed = Horde_Yaml::load($yaml);

        $expected = array("name" => "mark", "age" => "older than chris",
                                             "brand" => array("marlboro", "lucky strike"));
        $actual = $parsed[0];
        $this->assertEquals($expected, $actual);
    }

    // Parsing: Quotes

    public function testQuotesCanBeEscaped()
    {
        $yaml = "- one'apostrophe on line\n"
              . "- two'apostrophes' on line\n"
              . "- one\"quote on line\n"
              . "- two\"quotes\" on line\n";
        $parsed = Horde_Yaml::load($yaml);

        $this->assertEquals("one'apostrophe on line",
                            $parsed[0]);

        $this->assertEquals("two'apostrophes' on line",
                            $parsed[1]);

        $this->assertEquals('one"quote on line',
                            $parsed[2]);

        $this->assertEquals('two"quotes" on line',
                            $parsed[3]);
    }

    public function testQuotesCanBeUsedForComplexKeys()
    {
        $yaml = '"if: you\'d": like';
        $parsed = Horde_Yaml::load($yaml);

        $this->assertEquals("like", $parsed["if: you'd"]);
    }

    public function testQuotesCanBeEmptyWhenQuotes()
    {
        $yaml = 'empty: ""';
        $parsed = Horde_Yaml::load($yaml);

        $this->assertSame('', $parsed['empty']);
    }

    public function testQuotesCanBeEmptyWhenApostrophes()
    {
        $yaml = "empty: ''";
        $parsed = Horde_Yaml::load($yaml);

        $this->assertSame('', $parsed['empty']);
    }

    public function testWhitespaceBetweenQuotesIsPreserved()
    {
        $yaml = 'empty: "   "';
        $parsed = Horde_Yaml::load($yaml);

        $this->assertSame('   ', $parsed['empty']);
    }

    public function testWhitespaceBetweenApostrophesIsPreserved()
    {
        $yaml = "empty: '   '";
        $parsed = Horde_Yaml::load($yaml);

        $this->assertSame('   ', $parsed['empty']);
    }

    // Parsing: Keys

    public function testKeyAsNumeric()
    {
        // Added in Spyc .2
        $yaml = '1040: Ooo, a numeric key! # And working comments? Wow!';
        $parsed = Horde_Yaml::load($yaml);

        $this->assertEquals("Ooo, a numeric key!", $parsed[1040]);
    }

    // Tab Detection

    public function testThrowsAnExceptionWhenFirstCharacterOfLineIsTab()
    {
        try {
            Horde_Yaml::load("\tfoo: bar");
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertRegExp('/indent contains a tab/i', $e->getMessage());
        }
    }

    public function testThrowsExceptionWhenLineIndentContainsTab()
    {
        try {
            Horde_Yaml::load(" \tfoo: bar");
            $this->fail();
        } catch (Horde_Yaml_Exception $e) {
            $this->assertRegExp('/indent contains a tab/i', $e->getMessage());
        }
    }

    public function testDoesNotThrowOnAnEmptyLineWithTabsOrSpaces()
    {
        Horde_Yaml::load(" ");
        Horde_Yaml::load("\t");
        Horde_Yaml::load(" \t");
        Horde_Yaml::load("\t ");
    }

    // Comments

    public function testCommentOnEmptyLine()
    {
        $yaml = "# foo\nbar: baz";
        $expected = array('bar' => 'baz');
        $actual = Horde_Yaml::load($yaml);
        $this->assertEquals($expected, $actual);
    }

    public function testCommentAtEndOfLine()
    {
        $yaml = 'foo: bar # baz';
        $parsed = Horde_Yaml::load($yaml);

        $expected = 'bar';
        $actual = $parsed['foo'];
        $this->assertEquals($expected, $actual);
    }

    public function testDecoyCommentEmbeddedInQuotes()
    {
        $yaml = 'foo: "bar # baz"';
        $parsed = Horde_Yaml::load($yaml);

        $expected = 'bar # baz';
        $actual = $parsed['foo'];
        $this->assertEquals($expected, $actual);
    }

    public function testDecoyCommentEmbeddedInQuotesAndEndOfLineComment()
    {
        $yaml = 'foo: "bar # baz" # qux';
        $parsed = Horde_Yaml::load($yaml);

        $expected = 'bar # baz';
        $actual = $parsed['foo'];
        $this->assertEquals($expected, $actual);
    }

    public function testDecoyCommentEmbeddedInApostrophes()
    {
        $yaml = "foo: 'bar # baz'";
        $parsed = Horde_Yaml::load($yaml);

        $expected = 'bar # baz';
        $actual = $parsed['foo'];
        $this->assertEquals($expected, $actual);
    }

    public function testDecoyCommentEmbeddedInApostrophesAndEndOfLineVersion()
    {
        $yaml = "foo: 'bar # baz' # qux";
        $parsed = Horde_Yaml::load($yaml);

        $expected = 'bar # baz';
        $actual = $parsed['foo'];
        $this->assertEquals($expected, $actual);
    }

    // Misc

    public function testComplexParse()
    {
        $yaml = "databases:\n"
              . "  - name: spartan\n"
              . "    notes:\n"
              . "      - Needs to be backed up\n"
              . "      - Needs to be normalized\n"
              . "    type: mysql\n";

        $expected = array('databases' => array(array('name' => 'spartan',
                                                     'notes' => array('Needs to be backed up',
                                                                      'Needs to be normalized'),
                                                     'type' => 'mysql')));
        $actual = Horde_Yaml::load($yaml);
        $this->assertEquals($expected, $actual);
    }

    // Test Helpers

    public function fixture($name)
    {
        return __DIR__ . "/fixtures/{$name}.yml";
    }

    public function testUnfolding()
    {
        $parsed = Horde_Yaml::loadFile($this->fixture('basic'));
        $expected = "Line 1 Line 2";
        $this->assertEquals($expected, $parsed['foldedStringTest']);
    }

    public function testUnliteralizing()
    {
        $parsed = Horde_Yaml::loadFile($this->fixture('basic'));
        $expected = "Line #1\nLine #2";
        $this->assertEquals($expected, $parsed['literalStringTest']);
    }

}


/**
 * Used to test Horde_Yaml::$loadfunc callback.
 *
 * @package    Yaml
 * @subpackage UnitTests
 */
class Horde_Yaml_LoaderTest_MockLoader
{
    public static function returnArray($yaml)
    {
        return array('loaded');
    }

    public static function returnFalse($yaml)
    {
        return false;
    }

}

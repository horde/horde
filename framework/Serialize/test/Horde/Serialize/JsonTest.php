<?php
/**
 * Json serialization tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Serialize
 * @subpackage UnitTests
 */

class Horde_Serialize_JsonTest extends PHPUnit_Framework_TestCase
{
    // JSON associative arrays tests.
    public function testJsonAssociativeArray()
    {
        // array case - strict: associative array with nested associative
        // arrays
        $arr = array(
            'car1'=> array(
                'color'=> 'tan',
                'model' => 'sedan'
            ),
            'car2' => array(
                'color' => 'red',
                'model' => 'sports'
            )
        );

        $this->assertEquals(
            '{"car1":{"color":"tan","model":"sedan"},"car2":{"color":"red","model":"sports"}}',
            Horde_Serialize::serialize($arr, Horde_Serialize::JSON)
        );

        // array case - strict: associative array with nested associative
        // arrays, and some numeric keys thrown in
        // Should degrade to a numeric array.
        $arn = array(
            0 => array(
                0 => 'tan\\',
                'model\\' => 'sedan'
            ),
            1 => array(
                0 => 'red',
                'model' => 'sports'
            )
        );
        $arn_ja = '[{"0":"tan\\\\","model\\\\":"sedan"},{"0":"red","model":"sports"}]';
        $this->assertEquals(
            $arn_ja,
            Horde_Serialize::serialize($arn, Horde_Serialize::JSON)
        );

        $this->assertInternalType(
            'array',
            Horde_Serialize::unserialize($arn_ja, Horde_Serialize::JSON)
        );

        // sparse numeric assoc array: associative array numeric keys which
        // are not fully populated in a range of 0 to length-1
        // Test a sparsely populated numerically indexed associative array.
        $arrs = array(
            1 => 'one',
            2 => 'two',
            5 => 'five'
        );
        $this->assertEquals(
            '{"1":"one","2":"two","5":"five"}',
            Horde_Serialize::serialize($arrs, Horde_Serialize::JSON)
        );
    }

    // JSON empties tests.
    public function testJsonEmpties()
    {
        $obj0_j = '{}';
        $obj1_j = '{ }';

        $this->assertInternalType(
            'object',
            Horde_Serialize::unserialize($obj0_j, Horde_Serialize::JSON)
        );
        $this->assertEquals(
            '0',
            count(get_object_vars(Horde_Serialize::unserialize($obj0_j, Horde_Serialize::JSON)))
        );

        $this->assertInternalType(
            'object',
            Horde_Serialize::unserialize($obj1_j, Horde_Serialize::JSON)
        );
        $this->assertEquals(
            '0',
            count(get_object_vars(Horde_Serialize::unserialize($obj1_j, Horde_Serialize::JSON)))
        );
    }

    // JSON encode/decode tests (invalid UTF-8 input).
    public function testJsonInvalidUTF8Input()
    {
        if (version_compare(phpversion(), '5.3.0') == -1) {
            $this->markTestSkipped("skip Test requires PHP 5.3+");
        }

        $this->assertEquals(
            '"Note: To play video messages sent to email, QuickTime\u00ae 6.5 or higher is required.\n"',
            Horde_Serialize::serialize(file_get_contents('./fixtures/badutf8.txt'), Horde_Serialize::JSON)
        );
    }

    // JSON encode/decode tests.
    public function testJsonEncodeAndDecode()
    {
        $obj = new stdClass();
        $obj->a_string = '"he":llo}:{world';
        $obj->an_array = array(1, 2, 3);
        $obj->obj = new stdClass();
        $obj->obj->a_number = 123;
        $obj_j = '{"a_string":"\"he\":llo}:{world","an_array":[1,2,3],"obj":{"a_number":123}}';

        $arr = array(null, true, array(1, 2, 3), "hello\"],[world!");
        $arr_j = '[null,true,[1,2,3],"hello\"],[world!"]';

        $str1 = 'hello world';
        $str1_j = '"hello world"';
        $str1_j_ = "'hello world'";

        $str2 = "hello\t\"world\"";
        $str2_j = '"hello\\t\\"world\\""';

        $str3 = "\\\r\n\t\"/";
        $str3_j = '"\\\\\\r\\n\\t\\"\\/"';

        $str4 = 'héllö wørłd';
        $str4_j = '"h\u00e9ll\u00f6 w\u00f8r\u0142d"';
        $str4_j_ = '"héllö wørłd"';

        // type case: null
        $this->assertEquals(
            'null',
            Horde_Serialize::serialize(null, Horde_Serialize::JSON)
        );

        // type case: boolean true
        $this->assertEquals(
            'true',
            Horde_Serialize::serialize(true, Horde_Serialize::JSON)
        );

        // type case: boolean false
        $this->assertEquals(
            'false',
            Horde_Serialize::serialize(false, Horde_Serialize::JSON)
        );

        // numeric case: 1
        $this->assertEquals(
            1,
            Horde_Serialize::serialize(1, Horde_Serialize::JSON)
        );

        // numeric case: -1
        $this->assertEquals(
            -1,
            Horde_Serialize::serialize(-1, Horde_Serialize::JSON)
        );

        // numeric case: 1.0
        $this->assertEquals(
            1,
            Horde_Serialize::serialize(1.0, Horde_Serialize::JSON)
        );

        // numeric case: 1.1
        $this->assertEquals(
            1.1,
            Horde_Serialize::serialize(1.1, Horde_Serialize::JSON)
        );

        // string case: hello world
        $this->assertEquals(
            $str1_j,
            Horde_Serialize::serialize($str1, Horde_Serialize::JSON)
        );

        // string case: hello world, with tab, double-quotes
        $this->assertEquals(
            $str2_j,
            Horde_Serialize::serialize($str2, Horde_Serialize::JSON)
        );

        // string case: backslash, return, newline, tab, double-quote
        $this->assertEquals(
            $str3_j,
            Horde_Serialize::serialize($str3, Horde_Serialize::JSON)
        );

        // string case: hello world, with unicode
        $this->assertEquals(
            $str4_j,
            Horde_Serialize::serialize($str4, Horde_Serialize::JSON)
        );

        // array case: array with elements and nested arrays
        $this->assertEquals(
            '[null,true,[1,2,3],"hello\"],[world!"]',
            Horde_Serialize::serialize($arr, Horde_Serialize::JSON)
        );

        // object case: object with properties, nested object and arrays
        $this->assertEquals(
            '{"a_string":"\"he\":llo}:{world","an_array":[1,2,3],"obj":{"a_number":123}}',
            Horde_Serialize::serialize($obj, Horde_Serialize::JSON)
        );

        // type case: null
        $this->assertNull(
            Horde_Serialize::unserialize('null', Horde_Serialize::JSON)
        );

        // type case: boolean true
        $this->assertTrue(
            Horde_Serialize::unserialize('true', Horde_Serialize::JSON)
        );

        // type case: boolean false
        $this->assertFalse(
            Horde_Serialize::unserialize('false', Horde_Serialize::JSON)
        );

        // numeric case: 1
        $this->assertEquals(
            1,
            Horde_Serialize::unserialize('1', Horde_Serialize::JSON)
        );

        // numeric case: -1
        $this->assertEquals(
            -1,
            Horde_Serialize::unserialize('-1', Horde_Serialize::JSON)
        );

        // numeric case: 1.0
        $this->assertEquals(
            1.0,
            Horde_Serialize::unserialize('1.0', Horde_Serialize::JSON)
        );
        $this->assertInternalType(
            'float',
            Horde_Serialize::unserialize('1.0', Horde_Serialize::JSON)
        );

        // numeric case: 1.1
        $this->assertEquals(
            1.1,
            Horde_Serialize::unserialize('1.1', Horde_Serialize::JSON)
        );
        $this->assertInternalType(
            'float',
            Horde_Serialize::unserialize('1.1', Horde_Serialize::JSON)
        );


        // string case: hello world
        $this->assertEquals(
            $str1,
            Horde_Serialize::unserialize($str1_j, Horde_Serialize::JSON)
        );
        $this->assertEquals(
            "'" . $str1 . "'",
            Horde_Serialize::unserialize($str1_j_, Horde_Serialize::JSON)
        );

        // string case: hello world, with tab, double-quotes
        $this->assertEquals(
            $str2,
            Horde_Serialize::unserialize($str2_j, Horde_Serialize::JSON)
        );

        // string case: backslash, return, newline, tab, double-quote
        $this->assertEquals(
            $str3,
            Horde_Serialize::unserialize($str3_j, Horde_Serialize::JSON)
        );

        // string case: hello world, with unicode
        $this->assertEquals(
            $str4,
            Horde_Serialize::unserialize($str4_j, Horde_Serialize::JSON)
        );
        $this->assertEquals(
            $str4,
            Horde_Serialize::unserialize($str4_j_, Horde_Serialize::JSON)
        );

        // array case: array with elements and nested arrays
        $this->assertEquals(
            $arr,
            Horde_Serialize::unserialize($arr_j, Horde_Serialize::JSON)
        );

        // object case: object with properties, nested object and arrays
        $this->assertEquals(
            $obj,
            Horde_Serialize::unserialize($obj_j, Horde_Serialize::JSON)
        );

        // type case: null
        $this->assertNull(
            Horde_Serialize::unserialize(Horde_Serialize::serialize(null, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // type case: boolean true
        $this->assertTrue(
            Horde_Serialize::unserialize(Horde_Serialize::serialize(true, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // type case: boolean false
        $this->assertFalse(
            Horde_Serialize::unserialize(Horde_Serialize::serialize(false, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // numeric case: 1
        $this->assertEquals(
            1,
            Horde_Serialize::unserialize(Horde_Serialize::serialize(1, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // numeric case: -1
        $this->assertEquals(
            -1,
            Horde_Serialize::unserialize(Horde_Serialize::serialize(-1, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // numeric case: 1.0
        $this->assertEquals(
            1,
            Horde_Serialize::unserialize(Horde_Serialize::serialize(1.0, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // numeric case: 1.1
        $this->assertEquals(
            1.1,
            Horde_Serialize::unserialize(Horde_Serialize::serialize(1.1, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // string case: hello world
        $this->assertEquals(
            $str1,
            Horde_Serialize::unserialize(Horde_Serialize::serialize($str1, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // string case: hello world, with tab, double-quotes
        $this->assertEquals(
            $str2,
            Horde_Serialize::unserialize(Horde_Serialize::serialize($str2, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // string case: backslash, return, newline, tab, double-quote
        $this->assertEquals(
            $str3,
            Horde_Serialize::unserialize(Horde_Serialize::serialize($str3, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // string case: hello world, with unicode
        $this->assertEquals(
            $str4,
            Horde_Serialize::unserialize(Horde_Serialize::serialize($str4, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // array case: array with elements and nested arrays
        $this->assertEquals(
            $arr,
            Horde_Serialize::unserialize(Horde_Serialize::serialize($arr, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // object case: object with properties, nested object and arrays
        $this->assertEquals(
            $obj,
            Horde_Serialize::unserialize(Horde_Serialize::serialize($obj, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // type case: null
        $this->assertEquals(
            'null',
            Horde_Serialize::serialize(Horde_Serialize::unserialize('null', Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // type case: boolean true
        $this->assertEquals(
            'true',
            Horde_Serialize::serialize(Horde_Serialize::unserialize('true', Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // type case: boolean false
        $this->assertEquals(
            'false',
            Horde_Serialize::serialize(Horde_Serialize::unserialize('false', Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // numeric case: 1
        $this->assertEquals(
            '1',
            Horde_Serialize::serialize(Horde_Serialize::unserialize('1', Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // numeric case: -1
        $this->assertEquals(
            '-1',
            Horde_Serialize::serialize(Horde_Serialize::unserialize('-1', Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // numeric case: 1.0
        $this->assertEquals(
            '1.0',
            Horde_Serialize::serialize(Horde_Serialize::unserialize('1.0', Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // numeric case: 1.1
        $this->assertEquals(
            '1.1',
            Horde_Serialize::serialize(Horde_Serialize::unserialize('1.1', Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // string case: hello world
        $this->assertEquals(
            $str1_j,
            Horde_Serialize::serialize(Horde_Serialize::unserialize($str1_j, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // string case: hello world, with tab, double-quotes
        $this->assertEquals(
            $str2_j,
            Horde_Serialize::serialize(Horde_Serialize::unserialize($str2_j, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // string case: backslash, return, newline, tab, double-quote
        $this->assertEquals(
            $str3_j,
            Horde_Serialize::serialize(Horde_Serialize::unserialize($str3_j, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // string case: hello world, with unicode
        $this->assertEquals(
            $str4_j,
            Horde_Serialize::serialize(Horde_Serialize::unserialize($str4_j, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );
        $this->assertEquals(
            $str4_j,
            Horde_Serialize::serialize(Horde_Serialize::unserialize($str4_j_, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // array case: array with elements and nested arrays
        $this->assertEquals(
            $arr_j,
            Horde_Serialize::serialize(Horde_Serialize::unserialize($arr_j, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );

        // object case: object with properties, nested object and arrays
        $this->assertEquals(
            $obj_j,
            Horde_Serialize::serialize(Horde_Serialize::unserialize($obj_j, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );
    }

    // JSON nested arrays tests.
    public function testJsonNestedArrays()
    {
        $str1 = '[{"this":"that"}]';
        $str1_ob = new stdClass;
        $str1_ob->this = 'that';

        $str2 = '{"this":["that"]}';
        $str2_ob = new stdClass;
        $str2_ob->this = array('that');

        $str3 = '{"params":[{"foo":["1"],"bar":"1"}]}';
        $str3_ob = new stdClass;
        $str3_ob2 = new stdClass;
        $str3_ob2->foo = array(1);
        $str3_ob2->bar = 1;
        $str3_ob->params = array($str3_ob2);

        $str4 = '[{"foo": "bar", "baz": "winkle"}]';
        $str4_ob2 = new stdClass;
        $str4_ob2->foo = 'bar';
        $str4_ob2->baz = 'winkle';
        $str4_ob = array($str4_ob2);

        $str5 = '{"params":[{"options": {"old": [ ], "new": [{"elements": {"old": [], "new": [{"elementName": "aa", "isDefault": false, "elementRank": "0", "priceAdjust": "0", "partNumber": ""}]}}], "optionName": "aa", "isRequired": false, "optionDesc": ""}}]}';

        $str5_ob = new stdClass;
        $str5_ob->params = array(new stdClass);
        $str5_ob->params[0]->options = new stdClass;
        $str5_ob->params[0]->options->old = array();
        $str5_ob->params[0]->options->new = array(new stdClass);
        $str5_ob->params[0]->options->new[0]->elements = new stdClass;
        $str5_ob->params[0]->options->new[0]->elements->old = array();
        $str5_ob->params[0]->options->new[0]->elements->new = array(new stdClass);
        $str5_ob->params[0]->options->new[0]->elements->new[0]->elementName = 'aa';
        $str5_ob->params[0]->options->new[0]->elements->new[0]->isDefault = false;
        $str5_ob->params[0]->options->new[0]->elements->new[0]->elementRank = 0;
        $str5_ob->params[0]->options->new[0]->elements->new[0]->priceAdjust = 0;
        $str5_ob->params[0]->options->new[0]->elements->new[0]->partNumber = '';
        $str5_ob->params[0]->options->optionName = 'aa';
        $str5_ob->params[0]->options->isRequired = false;
        $str5_ob->params[0]->options->optionDesc = '';

        // simple compactly-nested array
        $this->assertEquals(
            array($str1_ob),
            Horde_Serialize::unserialize($str1, Horde_Serialize::JSON)
        );

        // simple compactly-nested array
        $this->assertEquals(
            $str2_ob,
            Horde_Serialize::unserialize($str2, Horde_Serialize::JSON)
        );

        // complex compactly nested array
        $this->assertEquals(
            $str3_ob,
            Horde_Serialize::unserialize($str3, Horde_Serialize::JSON)
        );

        // complex compactly nested array
        $this->assertEquals(
            $str4_ob,
            Horde_Serialize::unserialize($str4, Horde_Serialize::JSON)
        );

        // super complex compactly nested array
        $this->assertEquals(
            $str5_ob,
            Horde_Serialize::unserialize($str5, Horde_Serialize::JSON)
        );
    }

    // JSON objects tests.
    public function testJsonObjects()
    {
        $obj_j = '{"a_string":"\"he\":llo}:{world","an_array":[1,2,3],"obj":{"a_number":123}}';

        $obj1 = new stdClass;
        $obj1->car1 = new stdClass;
        $obj1->car1->color = 'tan';
        $obj1->car1->model = 'sedan';
        $obj1->car2 = new stdClass;
        $obj1->car2->color = 'red';
        $obj1->car2->model = 'sports';
        $obj1_j = '{"car1":{"color":"tan","model":"sedan"},"car2":{"color":"red","model":"sports"}}';

        $this->assertInternalType(
            'object',
            Horde_Serialize::unserialize($obj_j, Horde_Serialize::JSON)
        );

        // object - strict: Object with nested objects
        $this->assertEquals(
            $obj1_j,
            Horde_Serialize::serialize($obj1, Horde_Serialize::JSON)
        );

        // object case
        $this->assertEquals(
            $obj_j,
            Horde_Serialize::serialize(Horde_Serialize::unserialize($obj_j, Horde_Serialize::JSON), Horde_Serialize::JSON)
        );
    }

    // JSON spaces tests.
    public function testJsonSpaces()
    {
        $obj = new stdClass;
        $obj->a_string = "\"he\":llo}:{world";
        $obj->an_array = array(1, 2, 3);
        $obj->obj = new stdClass;
        $obj->obj->a_number = 123;

        $obj_js = '{"a_string": "\"he\":llo}:{world",
                        "an_array":[1, 2, 3],
                        "obj": {"a_number":123}}';

        // checking whether notation with spaces works
        $this->assertEquals(
            $obj,
            Horde_Serialize::unserialize($obj_js, Horde_Serialize::JSON)
        );
    }

    // JSON unquoted keys tests.
    public function testJsonUnquotedKeys()
    {
        $ob1 = new stdClass;
        $ob1->{'0'} = 'tan';
        $ob1->model = 'sedan';

        $ob2 = new stdClass;
        $ob2->{'0'} = 'red';
        $ob2->model = 'sports';

        $arn = array($ob1, $ob2);
        $arn_ja = '[{"0":"tan","model":"sedan"},{"0":"red","model":"sports"}]';

        $arrs = new stdClass;
        $arrs->{'1'} = 'one';
        $arrs->{'2'} = 'two';
        $arrs->{'5'} = 'fi"ve';
        $arrs_jo = '{"1":"one","2":"two","5":"fi\"ve"}';

        // array case - strict: associative array with unquoted keys, nested
        // associative arrays, and some numeric keys thrown in
        // ...unless the input array has some numeric indices, in which case
        // the behavior is to degrade to a regular array
        $this->assertEquals(
            $arn_ja,
            Horde_Serialize::serialize($arn, Horde_Serialize::JSON)
        );

        // sparse numeric assoc array: associative array with unquoted keys,
        // single-quoted values, numeric keys which are not fully populated in
        // a range of 0 to length-1
        // Test a sparsely populated numerically indexed associative array
        $this->assertEquals(
            $arrs_jo,
            Horde_Serialize::serialize($arrs, Horde_Serialize::JSON)
        );
    }

}

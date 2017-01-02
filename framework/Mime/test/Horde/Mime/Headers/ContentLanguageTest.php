<?php
/**
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Headers_ContentLanguage class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_Headers_ContentLanguageTest
extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider parsingOfInputProvider
     */
    public function testParsingOfInput($input, $expected_val, $expected_langs)
    {
        $ob = new Horde_Mime_Headers_ContentLanguage(null, $input);

        $this->assertEquals(
            $expected_val,
            $ob->value
        );

        $this->assertEquals(
            $expected_langs,
            $ob->langs
        );
    }

    public function parsingOfInputProvider()
    {
        return array(
            array(
                'en',
                'en',
                array('en')
            ),
            array(
                'en, de',
                'en,de',
                array('en', 'de')
            ),
            array(
                '    eN  , de      ,PT',
                'en,de,pt',
                array('en', 'de', 'pt')
            ),
            array(
                array('en', 'de'),
                'en,de',
                array('en', 'de')
            ),
            array(
                "e\0n",
                'en',
                array('en')
            )
        );
    }

}

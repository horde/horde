<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */

/**
 * Tests for the simple markup filter.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */
class Horde_Text_Filter_SimplemarkupTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider markupExamples
     */
    public function testSimplemarkup($markup, $html)
    {
        $this->assertEquals(
            $html,
            Horde_Text_Filter::filter($markup, 'simplemarkup')
        );
    }

    public function markupExamples()
    {
        return array(
            // Simple examples.
            array(
                'some *bold* text',
                'some <strong>*bold*</strong> text'
            ),
            array(
                'some _underlined_ text',
                'some <u>_underlined_</u> text'
            ),
            array(
                'some /italic/ text',
                'some <em>/italic/</em> text'
            ),

            // Edge cases.
            array(
                '*bold* at start',
                '<strong>*bold*</strong> at start'
            ),
            array(
                'at end *bold*',
                'at end <strong>*bold*</strong>'
            ),
            array(
                'full stop *bold*.',
                'full stop <strong>*bold*</strong>.'
            ),
            array(
                'some&nbsp;*bold*&nbsp;text',
                'some&nbsp;<strong>*bold*</strong>&nbsp;text'
            ),
            array(
                'some<br>*bold*<br />text more<br />*bold*<br>text',
                'some<br><strong>*bold*</strong><br />text more<br /><strong>*bold*</strong><br>text'
            ),

            // Whole phrase matching.
            array(
                '*some bold text*',
                '<strong>*some bold text*</strong>'
            ),
            array(
                ' *some bold text* ',
                ' <strong>*some bold text*</strong> '
            ),
            array(
                '&nbsp;*some bold&nbsp;text*&nbsp;',
                '&nbsp;<strong>*some bold&nbsp;text*</strong>&nbsp;'
            ),
            array(
                '<br>*some bold text*<br />',
                '<br><strong>*some bold text*</strong><br />'
            ),

            // No matching.
            array(
                'some *bold**bold* text',
                'some *bold**bold* text',
            ),
            array(
                'some *bold*bold* text',
                'some *bold*bold* text',
            ),
            array(
                'some bold*bold text',
                'some bold*bold text',
            ),
        );
    }
}

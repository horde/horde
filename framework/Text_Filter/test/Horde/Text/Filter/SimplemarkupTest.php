<?php
/**
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
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
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */
class Horde_Text_Filter_SimplemarkupTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider markupExamples
     */
    public function testSimplemarkup($markup, $output, $html)
    {
        $this->assertEquals(
            $output,
            Horde_Text_Filter::filter($markup, 'simplemarkup', array('html' => $html))
        );
    }

    public function markupExamples()
    {
        return array(
            // Simple examples.
            array(
                'some *bold* text',
                'some <strong>*bold*</strong> text',
                false,
            ),
            array(
                'some _underlined_ text',
                'some <u>_underlined_</u> text',
                false,
            ),
            array(
                'some /italic/ text',
                'some <em>/italic/</em> text',
                false,
            ),

            // Edge cases.
            array(
                '*bold* at start',
                '<strong>*bold*</strong> at start',
                false,
            ),
            array(
                'at end *bold*',
                'at end <strong>*bold*</strong>',
                false,
            ),
            array(
                'full stop *bold*.',
                'full stop <strong>*bold*</strong>.',
                false,
            ),
            array(
                'some&nbsp;*bold*&nbsp;text',
                'some&nbsp;<strong>*bold*</strong>&nbsp;text',
                true,
            ),
            array(
                'some<br>*bold*<br />text more<br />*bold*<br>text',
                'some<br><strong>*bold*</strong><br />text more<br /><strong>*bold*</strong><br>text',
                true,
            ),

            // Whole phrase matching.
            array(
                '*some bold text*',
                '<strong>*some bold text*</strong>',
                false,
            ),
            array(
                ' *some bold text* ',
                ' <strong>*some bold text*</strong> ',
                false,
            ),
            array(
                '&nbsp;*some bold&nbsp;text*&nbsp;',
                '&nbsp;<strong>*some bold&nbsp;text*</strong>&nbsp;',
                true,
            ),
            array(
                '<br>*some bold text*<br />',
                '<br><strong>*some bold text*</strong><br />',
                true,
            ),

            // No matching.
            array(
                'some *bold**bold* text',
                'some *bold**bold* text',
                false,
            ),
            array(
                'some *bold*bold* text',
                'some *bold*bold* text',
                false,
            ),
            array(
                'some bold*bold text',
                'some bold*bold text',
                false,
            ),

            // More edge cases.
            array(
                "* some bullet point\n* ...\n",
                "* some bullet point\n* ...\n",
                false,
            ),
            array(
                "* some bullet point<br>* ...<br>",
                "* some bullet point<br>* ...<br>",
                true,
            ),
            array(
                'some *bold* *text*.',
                'some <strong>*bold*</strong> <strong>*text*</strong>.',
                false,
            ),
        );
    }
}

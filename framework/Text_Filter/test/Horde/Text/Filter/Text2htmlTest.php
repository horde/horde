<?php
/**
 * Horde_Text_Filter_Text2html tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Text_Filter
 * @subpackage UnitTests
 */

class Horde_Text_Filter_Text2htmlTest extends PHPUnit_Framework_TestCase
{
    public function testText2html()
    {
        $tests = array(
            'http://www.horde.org/foo/',
            'https://jan:secret@www.horde.org/foo/',
            'mailto:jan@example.com',
            'svn+ssh://jan@svn.example.com/path/',
            '<tag>foo</tag>',
            '<http://css.maxdesign.com.au/listamatic/>',
            'http://www.horde.org/?foo=bar&baz=qux',
            'http://www.<alert>.horde.org/',
            'http://www.&#x32;.horde.org/'
        );

        $levels = array(
            Horde_Text_Filter_Text2html::PASSTHRU => array(
                'http://www.horde.org/foo/',
                'https://jan:secret@www.horde.org/foo/',
                'mailto:jan@example.com',
                'svn+ssh://jan@svn.example.com/path/',
                '<tag>foo</tag>',
                '<http://css.maxdesign.com.au/listamatic/>',
                'http://www.horde.org/?foo=bar&baz=qux',
                'http://www.<alert>.horde.org/',
                'http://www.&#x32;.horde.org/',
            ),
            Horde_Text_Filter_Text2html::SYNTAX => array(
                '<a href="http://www.horde.org/foo/" target="_blank">http://www.horde.org/foo/</a>',
                '<a href="https://jan:secret@www.horde.org/foo/" target="_blank">https://jan:secret@www.horde.org/foo/</a>',
                'mailto:<a href="mailto:jan@example.com">jan@example.com</a>',
                '<a href="svn+ssh://jan@svn.example.com/path/" target="_blank">svn+ssh://jan@svn.example.com/path/</a>',
                '&lt;tag&gt;foo&lt;/tag&gt;',
                '&lt;<a href="http://css.maxdesign.com.au/listamatic/" target="_blank">http://css.maxdesign.com.au/listamatic/</a>&gt;',
                '<a href="http://www.horde.org/?foo=bar&amp;baz=qux" target="_blank">http://www.horde.org/?foo=bar&amp;baz=qux</a>',
                '<a href="http://www" target="_blank">http://www</a>.&lt;alert&gt;.horde.org/',
                '<a href="http://www.&amp;#x32;.horde.org/" target="_blank">http://www.&amp;#x32;.horde.org/</a>'
            ),
            Horde_Text_Filter_Text2html::MICRO => array(
                '<a href="http://www.horde.org/foo/" target="_blank">http://www.horde.org/foo/</a>',
                '<a href="https://jan:secret@www.horde.org/foo/" target="_blank">https://jan:secret@www.horde.org/foo/</a>',
                'mailto:<a href="mailto:jan@example.com">jan@example.com</a>',
                '<a href="svn+ssh://jan@svn.example.com/path/" target="_blank">svn+ssh://jan@svn.example.com/path/</a>',
                '&lt;tag&gt;foo&lt;/tag&gt;',
                '&lt;<a href="http://css.maxdesign.com.au/listamatic/" target="_blank">http://css.maxdesign.com.au/listamatic/</a>&gt;',
                '<a href="http://www.horde.org/?foo=bar&amp;baz=qux" target="_blank">http://www.horde.org/?foo=bar&amp;baz=qux</a>',
                '<a href="http://www" target="_blank">http://www</a>.&lt;alert&gt;.horde.org/',
                '<a href="http://www.&amp;#x32;.horde.org/" target="_blank">http://www.&amp;#x32;.horde.org/</a>'
            ),
            Horde_Text_Filter_Text2html::MICRO_LINKURL => array(
                '<a href="http://www.horde.org/foo/" target="_blank">http://www.horde.org/foo/</a>',
                '<a href="https://jan:secret@www.horde.org/foo/" target="_blank">https://jan:secret@www.horde.org/foo/</a>',
                'mailto:jan@example.com',
                '<a href="svn+ssh://jan@svn.example.com/path/" target="_blank">svn+ssh://jan@svn.example.com/path/</a>',
                '&lt;tag&gt;foo&lt;/tag&gt;',
                '&lt;<a href="http://css.maxdesign.com.au/listamatic/" target="_blank">http://css.maxdesign.com.au/listamatic/</a>&gt;',
                '<a href="http://www.horde.org/?foo=bar&amp;baz=qux" target="_blank">http://www.horde.org/?foo=bar&amp;baz=qux</a>',
                '<a href="http://www" target="_blank">http://www</a>.&lt;alert&gt;.horde.org/',
                '<a href="http://www.&amp;#x32;.horde.org/" target="_blank">http://www.&amp;#x32;.horde.org/</a>'
            ),
            Horde_Text_Filter_Text2html::NOHTML => array(
                'http://www.horde.org/foo/',
                'https://jan:secret@www.horde.org/foo/',
                'mailto:jan@example.com',
                'svn+ssh://jan@svn.example.com/path/',
                '&lt;tag&gt;foo&lt;/tag&gt;',
                '&lt;http://css.maxdesign.com.au/listamatic/&gt;',
                'http://www.horde.org/?foo=bar&amp;baz=qux',
                'http://www.&lt;alert&gt;.horde.org/',
                'http://www.&amp;#x32;.horde.org/'
            ),
            Horde_Text_Filter_Text2html::NOHTML_NOBREAK => array(
                'http://www.horde.org/foo/',
                'https://jan:secret@www.horde.org/foo/',
                'mailto:jan@example.com',
                'svn+ssh://jan@svn.example.com/path/',
                '&lt;tag&gt;foo&lt;/tag&gt;',
                '&lt;http://css.maxdesign.com.au/listamatic/&gt;',
                'http://www.horde.org/?foo=bar&amp;baz=qux',
                'http://www.&lt;alert&gt;.horde.org/',
                'http://www.&amp;#x32;.horde.org/'
            )
        );

        foreach ($levels as $level => $results) {
            foreach ($tests as $key => $val) {
                $filter = Horde_Text_Filter::filter($val, 'text2html', array(
                    'parselevel' => $level
                ));
                $this->assertEquals($results[$key], $filter);
            }
        }
    }

}

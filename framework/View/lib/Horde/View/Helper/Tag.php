<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */

/**
 * Use these methods to generate HTML tags programmatically.
 *
 * By default, they output HTML 4.01 Strict compliant tags.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */
class Horde_View_Helper_Tag extends Horde_View_Helper_Base
{
    /**
     * Boolean HTML attributes.
     *
     * @var array
     */
    private $_booleanAttributes = array('checked', 'disabled', 'multiple', 'readonly', 'selected');

    /**
     * Returns an empty HTML tag of type $name.
     *
     * Add HTML attributes by passing an attributes hash to $options. For
     * attributes with no value (like disabled and readonly), give it a value
     * of TRUE in the $options array.
     *
     * <code>
     * $this->tag('br')
     * // => <br>
     * $this->tag('input', array('type' => 'text', 'disabled' => true))
     * // => <input type="text" disabled="disabled">
     * </code>
     *
     * @param string $name     Tag name.
     * @param string $options  Tag attributes.
     *
     * @return string  Generated HTML tag.
     */
    public function tag($name, $options = null)
    {
        return "<$name"
            . ($options ? $this->tagOptions($options) : '')
            . ' />';
    }

    /**
     * Returns an HTML block tag of type $name surrounding the $content.
     *
     * Add HTML attributes by passing an attributes hash to $options. For
     * attributes with no value (like disabled and readonly), give it a value
     * of TRUE in the $options array.
     *
     * <code>
     * $this->contentTag('p', 'Hello world!')
     * // => <p>Hello world!</p>
     * $this->contentTag('div',
     *                   $this->contentTag('p', 'Hello world!'),
     *                   array('class' => 'strong'))
     * // => <div class="strong"><p>Hello world!</p></div>
     * $this->contentTag('select', $options, array('multiple' => true))
     * // => <select multiple="multiple">...options...</select>
     * </code>
     *
     * @param string $name     Tag name.
     * @param string $content  Content to place between the tags.
     * @param array $options   Tag attributes.
     *
     * @return string  Generated HTML tags with content between.
     */
    public function contentTag($name, $content, $options = null)
    {
        $tagOptions = ($options ? $this->tagOptions($options) : '');
        return "<$name$tagOptions>$content</$name>";
    }

    /**
     * Returns a CDATA section with the given $content.
     *
     * CDATA sections are used to escape blocks of text containing characters
     * which would otherwise be recognized as markup. CDATA sections begin with
     * the string "<![CDATA[" and end with (and may not contain) the string
     * "]]>".
     *
     * <code>
     * $this->cdataSection('<hello world>');
     * // => <![CDATA[<hello world>]]>
     *
     * @param string $content  Content for inside CDATA section.
     *
     * @return string  CDATA section with content.
     */
    public function cdataSection($content)
    {
        return "<![CDATA[$content]]>";
    }

    /**
     * Escapes a value for output in a view template.
     *
     * <code>
     * <p><?php echo $this->escape($this->templateVar) ?></p>
     * </code>
     *
     * @param string $var  The output to escape.
     *
     * @return string  The escaped value.
     */
    public function escape($var)
    {
        return htmlspecialchars($var, ENT_QUOTES, $this->_view->getEncoding());
    }

    /**
     * Returns the escaped $html without affecting existing escaped entities.
     *
     * <code>
     * $this->escapeOnce('1 > 2 &amp; 3')
     * // => '1 &lt; 2 &amp; 3'
     *
     * @param string $html  HTML to be escaped.
     *
     * @return string  Escaped HTML without affecting existing escaped entities.
     */
    public function escapeOnce($html)
    {
        return $this->_fixDoubleEscape($this->escape($html));
    }

    /**
     * Converts an associative array of $options into a string of HTML
     * attributes.
     *
     * @param array $options  Key/value pairs.
     *
     * @return string  'key1="value1" key2="value2"'
     */
    public function tagOptions($options)
    {
        foreach ($options as $k => $v) {
            if ($v === null || $v === false) {
                unset($options[$k]);
            }
        }

        if (!empty($options)) {
            foreach ($options as $k => &$v) {
                if (in_array($k, $this->_booleanAttributes)) {
                    $v = $k;
                } else {
                    $v = $k . '="' . $this->escapeOnce($v) . '"';
                }
            }
            sort($options);
            return ' ' . implode(' ', $options);
        } else {
            return '';
        }
    }

    /**
     * Fix double-escaped entities, such as &amp;amp;, &amp;#123;, etc.
     *
     * @param string $escaped  Double-escaped entities.
     *
     * @return string  Entities fixed.
     */
    private function _fixDoubleEscape($escaped)
    {
        return preg_replace('/&amp;([a-z]+|(#\d+));/i', '&\\1;', $escaped);
    }
}

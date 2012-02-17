<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
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
 * View helpers for text
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */
class Horde_View_Helper_Text extends Horde_View_Helper_Base
{
    /**
     * @var array
     */
    protected $_cycles = array();

    /**
     * @var Horde_Support_Inflector
     */
    protected $_inflector;

    /**
     * Escapes a value for output in a view template.
     *
     * <code>
     * <p><?php echo $this->h($this->templateVar) ?></p>
     * </code>
     *
     * @param mixed $var  The output to escape.
     *
     * @return mixed  The escaped value.
     */
    public function h($var)
    {
        return htmlspecialchars($var, ENT_QUOTES, $this->_view->getEncoding());
    }

    /**
     * Pluralizes the $singular word unless $count is one. If $plural
     * form is not supplied, inflector will be used.
     *
     * @param integer $count    Count determines singular or plural.
     * @param string $singular  Singular form.
     * @param string $plural    Plural form (optional).
     */
    public function pluralize($count, $singular, $plural = null)
    {
        if ($count == '1') {
            $word = $singular;
        } elseif ($plural) {
            $word = $plural;
        } else {
            if (!$this->_inflector) {
                $this->_inflector = new Horde_Support_Inflector();
            }
            $word = $this->_inflector->pluralize($singular);
        }

        return "$count $word";
    }

    /**
     * Creates a Cycle object whose __toString() method cycles through elements
     * of an array every time it is called.
     *
     * This can be used for example, to alternate classes for table rows:
     *
     * <code>
     * <?php foreach($items as $item): ?>
     *   <tr class="<?php echo $this->cycle("even", "odd") ?>">
     *     <td>item</td>
     *   </tr>
     * <?php endforeach ?>
     * </code>
     *
     * You can use named cycles to allow nesting in loops.  Passing an array as
     * the last parameter with a <tt>name</tt> key will create a named cycle.
     * You can manually reset a cycle by calling resetCycle() and passing the
     * name of the cycle:
     *
     * <code>
     * <?php foreach($items as $item): ?>
     * <tr class="<?php echo $this->cycle('even', 'odd', array('name' => 'row_class')) ?>">
     *   <td>
     *     <?php foreach ($item->values as $value): ?>
     *     <span style="color:<?php echo $this->cycle('red', 'green', 'blue', array('name' => 'colors')) ?>">
     *       <?php echo $value ?>
     *     </span>
     *     <?php endforeach ?>
     *     <?php $this->resetCycle('colors') ?>
     *   </td>
     * </tr>
     * <?php endforeach ?>
     * </code>
     */
    public function cycle($firstValue)
    {
        $values = func_get_args();

        $last = end($values);
        if (is_array($last)) {
            $options = array_pop($values);
            $name = isset($options['name']) ? $options['name'] : 'default';
        } else {
            $name = 'default';
        }

        if (empty($this->_cycles[$name]) ||
            $this->_cycles[$name]->getValues() != $values) {
            $this->_cycles[$name] = new Horde_View_Helper_Text_Cycle($values);
        }

        return $this->_cycles[$name];
    }

    /**
     * Resets a cycle so that it starts from the first element the next time
     * it is called.
     *
     * Pass in $name to reset a named cycle.
     *
     * @param string $name  Name of cycle to reset.
     */
    public function resetCycle($name = 'default')
    {
        if (isset($this->_cycles[$name])) {
            $this->_cycles[$name]->reset();
        }
    }

    /**
     * Highlights a phrase where it is found in the text by surrounding it
     * like <strong class="highlight">I'm highlighted</strong>.
     *
     * The Highlighter can be customized by passing $highlighter as a string
     * containing $1 as a placeholder where the phrase is supposed to be
     * inserted.
     *
     * @param string $text         A text containing phrases to highlight.
     * @param string $phrase       A phrase to highlight in $text.
     * @param string $highlighter  A highlighting replacement.
     *
     * @return string  The highlighted text.
     */
    public function highlight($text, $phrase, $highlighter = null)
    {
        if (empty($highlighter)) {
            $highlighter = '<strong class="highlight">$1</strong>';
        }
        if (empty($phrase) || empty($text)) {
            return $text;
        }
        return preg_replace('/(' . preg_quote($phrase, '/') . ')/',
                            $highlighter,
                            $text);
    }

    /**
     * If $text is longer than $length, $text will be truncated to the length
     * of $length and the last three characters will be replaced with the
     * $truncateString.
     *
     * <code>
     * $this->truncate('Once upon a time in a world far far away', 14);
     * // => Once upon a...
     * </code>
     *
     * @param string $text            A text to truncate.
     * @param integer $length         The maximum length of the text
     * @param string $truncateString  Replacement string for the truncated
     *                                text.
     *
     * @return string  The truncated text.
     */
    public function truncate($text, $length = 30, $truncateString = '...')
    {
        if (empty($text)) {
            return $text;
        }
        $l = $length - strlen($truncateString);
        return strlen($text) > $length
            ? substr($text, 0, $l) . $truncateString
            : $text;
    }

    /**
     * Limits a string to a given maximum length in a smarter way than just
     * using substr().
     *
     * Namely, cut from the MIDDLE instead of from the end so that if we're
     * doing this on (for instance) a bunch of binder names that start off with
     * the same verbose description, and then are different only at the very
     * end, they'll still be different from one another after truncating.
     *
     * <code>
     * $str = 'The quick brown fox jumps over the lazy dog tomorrow morning.';
     * $shortStr = $this->truncateMiddle($str, 40);
     * // $shortStr == 'The quick brown fox... tomorrow morning.'
     * </code>
     *
     * @param string $str         A text to truncate.
     * @param integer $maxLength  The maximum length of the text
     * @param string $joiner      Replacement string for the truncated text.
     *
     * @return string  The truncated text.
     */
    public function truncateMiddle($str, $maxLength = 80, $joiner = '...')
    {
        if (strlen($str) <= $maxLength) {
            return $str;
        }
        $maxLength = $maxLength - strlen($joiner);
        if ($maxLength <= 0) {
            return $str;
        }
        $startPieceLength = (int) ceil($maxLength / 2);
        $endPieceLength = (int) floor($maxLength / 2);
        $trimmedString = substr($str, 0, $startPieceLength) . $joiner;
        if ($endPieceLength > 0) {
            $trimmedString .= substr($str, (-1 * $endPieceLength));
        }
        return $trimmedString;
    }

    /**
     * Inserts HTML code to allow linebreaks in a string after slashes or
     * underscores.
     *
     * @param string $str  A string to mark up with linebreak markers.
     *
     * @return string  The marked-up string.
     */
    public function makeBreakable($str)
    {
        return str_replace(
            array('/',      '_'),
            array('/<wbr>', '_<wbr>'),
            $str
        );
    }

    /**
     * Removes smart quotes.
     *
     * @see http://shiflett.org/blog/2005/oct/convert-smart-quotes-with-php
     *
     * @param string $str  A string with potential smart quotes.
     *
     * @return string  The cleaned-up string.
     */
    public function cleanSmartQuotes($str)
    {
        $search = array(
            '/\x96/',
            '/\xE2\x80\x93/',
            '/\x97/',
            '/\xE2\x80\x94/',
            '/\x91/',
            '/\xE2\x80\x98/',
            '/\x92/',
            '/\xE2\x80\x99/',
            '/\x93/',
            '/\xE2\x80\x9C/',
            '/\x94/',
            '/\xE2\x80\x9D/',
            '/\x85/',
            '/\xE2\x80\xA6/',
            '/\x95/',
            '/\xE2\x80\xA2/',
            '/\x09/',

            // The order of these is very important.
            '/\xC2\xBC/',
            '/\xBC/',
            '/\xC2\xBD/',
            '/\xBD/',
            '/\xC2\xBE/',
            '/\xBE/',
        );

        $replace = array(
            '-',
            '-',
            '--',
            '--',
            "'",
            "'",
            "'",
            "'",
            '"',
            '"',
            '"',
            '"',
            '...',
            '...',
            '*',
            '*',
            ' ',

            '1/4',
            '1/4',
            '1/2',
            '1/2',
            '3/4',
            '3/4',
        );

        return preg_replace($search, $replace, $str);
    }
}

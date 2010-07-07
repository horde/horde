<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */

/**
 * View helpers for text
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
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
     *   <p><?php echo $this->h($this->templateVar) ?></p>
     * </code>
     *
     * @param   mixed   $var The output to escape.
     * @return  mixed   The escaped value.
     */
    public function h($var)
    {
        return htmlspecialchars($var, ENT_QUOTES, $this->_view->getEncoding());
    }

    /**
     * Pluralize the $singular word unless $count is one.  If $plural
     * form is not supplied, inflector will be used.
     *
     * @param  integer      $count      Count determines singular or plural
     * @param  string       $singular   Singular form
     * @param  string|null  $plural     Plural form (optional)
     */
    public function pluralize($count, $singular, $plural = null)
    {
        if ($count == '1') {
            $word = $singular;
        } else if ($plural) {
            $word = $plural;
        } else {
            if (!$this->_inflector) { $this->_inflector = new Horde_Support_Inflector(); }
            $word = $this->_inflector->pluralize($singular);
        }

        return "$count $word";
    }

    /**
     * Creates a Cycle object whose __toString() method cycles through elements of an
     * array every time it is called. This can be used for example, to alternate
     * classes for table rows:
     *
     *   <?php foreach($items as $item): ?>
     *     <tr class="<?php echo $this->cycle("even", "odd") ?>">
     *       <td>item</td>
     *     </tr>
     *   <?php endforeach ?>
     *
     * You can use named cycles to allow nesting in loops.  Passing an array as
     * the last parameter with a <tt>name</tt> key will create a named cycle.
     * You can manually reset a cycle by calling resetCycle() and passing the
     * name of the cycle.
     *
     *   <?php foreach($items as $item): ?>
     *     <tr class="<?php echo $this->cycle("even", "odd", array('name' => "row_class")) ?>">
     *       <td>
     *         <?php foreach ($item->values as $value) ?>
     *           <span style="color:<?php echo $this->cycle("red", "green", "blue", array('name' => "colors")) ?>">
     *             value
     *           </span>
     *         <?php endforeach ?>
     *         <?php $this->resetCycle("colors") ?>
     *       </td>
     *    </tr>
     *   <?php endforeach ?>
     *
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

        if (empty($this->_cycles[$name]) || $this->_cycles[$name]->getValues() != $values) {
            $this->_cycles[$name] = new Horde_View_Helper_Text_Cycle($values);
        }
        return $this->_cycles[$name];
    }

    /**
     * Resets a cycle so that it starts from the first element the next time
     * it is called. Pass in $name to reset a named cycle.
     *
     * @param  string  $name  Name of cycle to reset (defaults to "default")
     * @return void
     */
    public function resetCycle($name = 'default')
    {
        if (isset($this->_cycles[$name])) {
            $this->_cycles[$name]->reset();
        }
    }

    /**
     * Highlights the phrase where it is found in the text by surrounding it like
     * <strong class="highlight">I'm highlighted</strong>. The Highlighter can
     * be customized by passing highlighter as a single-quoted string with $1
     * where the prhase is supposed to be inserted.
     *
     * @param   string  $text
     * @param   string  $phrase
     * @param   string  $highlighter
     */
    public function highlight($text, $phrase, $highlighter=null)
    {
        if (empty($highlighter)) {
            $highlighter='<strong class="highlight">$1</strong>';
        }
        if (empty($phrase) || empty($text)) {
            return $text;
        }
        return preg_replace("/($phrase)/", $highlighter, $text);
    }

    /**
     * If $text is longer than $length, $text will be truncated to the
     * length of $length and the last three characters will be replaced
     * with the $truncateString.
     *
     * <code>
     * $this->truncate("Once upon a time in a world far far away", 14);
     * => Once upon a...
     * </code>
     *
     * @param   string  $text
     * @param   integer $length
     * @param   string  $truncateString
     * @return  string
     */
    public function truncate($text, $length=30, $truncateString = '...')
    {
        if (empty($text)) { return $text; }
        $l = $length - strlen($truncateString);
        return strlen($text) > $length ? substr($text, 0, $l).$truncateString : $text;
    }

    /**
     * Limit a string to a given maximum length in a smarter way than just using
     * substr. Namely, cut from the MIDDLE instead of from the end so that if
     * we're doing this on (for instance) a bunch of binder names that start off
     * with the same verbose description, and then are different only at the
     * very end, they'll still be different from one another after truncating.
     *
     * <code>
     *  <?php
     *  ...
     *  $str = "The quick brown fox jumps over the lazy dog tomorrow morning.";
     *  $shortStr = truncateMiddle($str, 40);
     *  // $shortStr = "The quick brown fox... tomorrow morning."
     *  ...
     *  ?>
     * </code>
     *
     * @todo    This is not a Rails helper...
     * @param   string  $str
     * @param   int     $maxLength
     * @param   string  $joiner
     * @return  string
     */
    public function truncateMiddle($str, $maxLength=80, $joiner='...')
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
     * Allow linebreaks in a string after slashes or underscores
     *
     * @todo    This is not a Rails helper...
     * @param   string  $str
     * @return  string
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
     * Remove smart quotes
     *
     * http://shiflett.org/blog/2005/oct/convert-smart-quotes-with-php
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

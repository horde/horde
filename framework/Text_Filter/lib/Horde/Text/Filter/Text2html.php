<?php
/**
 * Turn text into HTML with varying levels of parsing.  For no html
 * whatsoever, use htmlspecialchars() instead.
 *
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Horde_Text_Filter_Text2html extends Horde_Text_Filter_Base
{
    const PASSTHRU = 0;
    const SYNTAX = 1;
    const MICRO = 2;
    const MICRO_LINKURL = 3;
    const NOHTML = 4;
    const NOHTML_NOBREAK = 5;

    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'charset' => 'ISO-8859-1',
        'class' => 'fixed',
        'emails' => false,
        'flowed' => '<blockquote>',
        'linkurls' => false,
        'text2html' => false,
        'parselevel' => 0,
        'space2html' => false
    );

    /**
     * Constructor.
     *
     * @param array $params  Parameters specific to this driver:
     * <ul>
     *  <li>charset: (string) The charset to use for htmlspecialchars()
     *               calls.</li>
     *  <li>class: (string) See Horde_Text_Filter_Linkurls::.</li>
     *  <li>emails: (array) TODO</li>
     *  <li>flowed: (string) For flowed text, the HTML blockquote tag to
     *              insert before each level.
     *  <li>linkurls: (array) TODO</li>
     *  <li>parselevel: (integer) The parselevel of the output.
     *   <ul>
     *    <li>PASSTHRU: No action. Pass-through. Included for
     *                  completeness.</li>
     *    <li>SYNTAX: Allow full html, also do line-breaks, in-lining,
     *                syntax-parsing.</li>
     *    <li>MICRO: Micro html (only line-breaks, in-line linking).</li>
     *    <li>MICRO_LINKURL: Micro html (only line-breaks, in-line linking of
     *                       URLS; no email addresses are linked).</li>
     *    <li>NOHTML: No html (all stripped, only line-breaks).</li>
     *    <li>NOHTML_NOBREAK: No html whatsoever, no line breaks added.
     *                        Included for completeness.</li>
     *   </ul>
     *  </li>
     *  <li>space2html: (array) TODO</li>
     * </ul>
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        // Use ISO-8859-1 instead of US-ASCII
        if (Horde_String::lower($this->_params['charset']) == 'us-ascii') {
            $this->_params['charset'] = 'iso-8859-1';
        }
    }

    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param mixed $text  The text before the filtering. Either a string or
     *                     a Horde_Text_Flowed object (since 1.1.0).
     *
     * @return string  The modified text.
     */
    public function preProcess($text)
    {
        if ($text instanceof Horde_Text_Flowed) {
            $text->setMaxLength(0);
            $lines = $text->toFixedArray();
            $level = 0;
            $out = $txt = '';

            foreach ($lines as $key => $val) {
                $line = ltrim($val['text'], '>');

                if (!isset($lines[$key + 1])) {
                    $out .= $this->preProcess(ltrim($txt) . $line);
                    while (--$level > 0) {
                        $out .= '</blockquote>';
                    }
                } elseif ($val['level'] > $level) {
                    $out .= $this->preProcess(ltrim($txt));
                    do {
                        $out .= $this->_params['flowed'];
                    } while (++$level != $val['level']);
                    $txt = $line;
                } elseif ($val['level'] < $level) {
                    $out .= $this->preProcess(ltrim($txt));
                    do {
                        $out .= '</blockquote>';
                    } while (--$level != $val['level']);
                    $txt = $line;
                } else {
                    $txt .= "\n" . $line;
                }
            }

            return $out;
        }

        if (!strlen($text)) {
            return '';
        }

        /* Abort out on simple cases. */
        if ($this->_params['parselevel'] == self::PASSTHRU) {
            return $text;
        }

        if ($this->_params['parselevel'] == self::NOHTML_NOBREAK) {
            return @htmlspecialchars($text, ENT_COMPAT, $this->_params['charset']);
        }

        if ($this->_params['parselevel'] < self::NOHTML) {
            $filters = array();
            if ($this->_params['linkurls']) {
                reset($this->_params['linkurls']);
                $this->_params['linkurls'][key($this->_params['linkurls'])]['encode'] = true;
                $filters = $this->_params['linkurls'];
            } else {
                $filters['linkurls'] = array(
                    'encode' => true
                );
            }

            if ($this->_params['parselevel'] < self::MICRO_LINKURL) {
                if ($this->_params['emails']) {
                    reset($this->_params['emails']);
                    $this->_params['emails'][key($this->_params['emails'])]['encode'] = true;
                    $filters += $this->_params['emails'];
                } else {
                    $filters['emails'] = array(
                        'encode' => true
                    );
                }
            }

            $text = Horde_Text_Filter::filter($text, array_keys($filters), array_values($filters));
        }

        /* For level MICRO or NOHTML, start with htmlspecialchars(). */
        $text2 = @htmlspecialchars($text, ENT_COMPAT, $this->_params['charset']);

        /* Bad charset input in may result in an empty string. Or the charset
         * may not be supported. Convert to UTF-8 for htmlspecialchars() and
         * then convert back. If we STILL don't have any output, the input
         * charset is probably incorrect. Try the popular Western charsets as
         * a last resort. */
        if (!strlen($text2)) {
            $text2 = Horde_String::convertCharset(
                @htmlspecialchars(
                    Horde_String::convertCharset($text, $this->_params['charset'], 'UTF-8'),
                    ENT_COMPAT,
                    'UTF-8'
                ),
                'UTF-8',
                $this->_params['charset']
            );

            if (!strlen($text2)) {
                foreach (array('windows-1252', 'utf-8') as $val) {
                    $text2 = Horde_String::convertCharset(
                        @htmlspecialchars($text, ENT_COMPAT, $val),
                        $val,
                        $this->_params['charset']
                    );

                    if (strlen($text2)) {
                        break;
                    }
                }
            }
        }

        $text = $text2;

        /* Do in-lining of http://xxx.xxx to link, xxx@xxx.xxx to email. */
        if ($this->_params['parselevel'] < self::NOHTML) {
            $text = Horde_Text_Filter_Linkurls::decode($text);
            if ($this->_params['parselevel'] < self::MICRO_LINKURL) {
                $text = Horde_Text_Filter_Emails::decode($text);
            }

            if ($this->_params['space2html']) {
                $params = reset($this->_params['space2html']);
                $driver = key($this->_params['space2html']);
            } else {
                $driver = 'space2html';
                $params = array();
            }

            $text = Horde_Text_Filter::filter($text, $driver, $params);
        }

        /* Do the newline ---> <br /> substitution. Everybody gets this; if
         * you don't want even that, just use htmlspecialchars(). */
        return nl2br($text);
    }

}

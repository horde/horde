<?php
/**
 * Turn text into HTML with varying levels of parsing.  For no html
 * whatsoever, use htmlspecialchars() instead.
 *
 * Parameters:
 * <pre>
 * charset - (string) The charset to use for htmlspecialchars() calls.
 * class - (string) See Horde_Text_Filter_Linkurls::.
 * emails - (array)
 * linkurls - (array)
 * parselevel - (integer) The parselevel of the output (see below).
 * space2html - (array)
 * </pre>
 *
 * <pre>
 * List of valid constants for the parse level:
 * --------------------------------------------
 * PASSTHRU        =  No action. Pass-through. Included for completeness.
 * SYNTAX          =  Allow full html, also do line-breaks, in-lining,
 *                    syntax-parsing.
 * MICRO           =  Micro html (only line-breaks, in-line linking).
 * MICRO_LINKURL   =  Micro html (only line-breaks, in-line linking of URLS;
 *                    no email addresses are linked).
 * NOHTML          =  No html (all stripped, only line-breaks)
 * NOHTML_NOBREAK  =  No html whatsoever, no line breaks added.
 *                    Included for completeness.
 * </pre>
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
        'linkurls' => false,
        'text2html' => false,
        'parselevel' => 0,
        'space2html' => false
    );

    /**
     * Constructor.
     *
     * @param array $params  Any parameters that the filter instance needs.
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
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess($text)
    {
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
        $old_error = error_reporting(0);
        $text2 = htmlspecialchars($text, ENT_COMPAT, $this->_params['charset']);

        /* Bad charset input in may result in an empty string. If so, try
         * using the default charset encoding instead. */
        if (!$text2) {
            $text2 = htmlspecialchars($text, ENT_COMPAT);
        }
        $text = $text2;
        error_reporting($old_error);

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

<?php
/**
 * Turn text into HTML with varying levels of parsing.  For no html
 * whatsoever, use htmlspecialchars() instead.
 *
 * Parameters:
 * <pre>
 * callback - (string) See Horde_Text_Filter_Linkurls::.
 * charset - (string) The charset to use for htmlspecialchars() calls.
 * class - (string) See Horde_Text_Filter_Linkurls::.
 * nofollow - (boolean) See Horde_Text_Filter_Linkurls::.
 * noprefetch - (boolean) See Horde_Text_Filter_Linkurls::.
 * parselevel - (integer) The parselevel of the output (see below).
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
        'callback' => 'Horde::externalUrl',
        'charset' => 'ISO-8859-1',
        'class' => 'fixed',
        'nofollow' => false,
        'noprefetch' => false,
        'parselevel' => 0
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
            $filters = array('linkurls' => array(
                'callback' => $this->_params['callback'],
                'nofollow' => $this->_params['nofollow'],
                'noprefetch' => $this->_params['noprefetch'],
                'encode' => true
            ));
            if ($this->_params['parselevel'] < self::MICRO_LINKURL) {
                $filters['emails'] = array('encode' => true);
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

            $text = Horde_Text_Filter::filter($text, 'space2html');
        }

        /* Do the newline ---> <br /> substitution. Everybody gets this; if
         * you don't want even that, just use htmlspecialchars(). */
        return nl2br($text);
    }

}

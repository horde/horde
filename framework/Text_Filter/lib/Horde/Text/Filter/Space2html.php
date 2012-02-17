<?php
/**
 * The space2html filter converts horizontal whitespace to HTML code.
 *
 * Parameters:
 * <pre>
 * encode     -- HTML encode the text?  Defaults to false.
 * charset    -- Charset of the text.  Defaults to ISO-8859-1.
 * encode_all -- Replace all spaces with &nbsp;?  Defaults to false.
 * </pre>
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Mathieu Arnold <mat@mat.cc>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Horde_Text_Filter_Space2html extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'charset' => 'ISO-8859-1',
        'encode' => false,
        'encode_all' => false
    );

    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess($text)
    {
        if ($this->_params['encode']) {
            $text = @htmlspecialchars($text, ENT_COMPAT, $this->_params['charset']);
        }
        return $text;
    }

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        return array(
            'replace' => array(
                "\t" => '&nbsp; &nbsp; &nbsp; &nbsp; ',
                '  ' => '&nbsp; '
            )
        );
    }

    /**
     * Executes any code necessaray after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string  The modified text.
     */
    public function postProcess($text)
    {
        $text = str_replace('  ', ' &nbsp;', $text);
        if ($this->_params['encode_all']) {
            $text = str_replace(' ', '&nbsp;', $text);
        }
        return $text;
    }

}

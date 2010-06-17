<?php
/**
 * This filter cleans up CSS output by running it through a PHP-based
 * optimizer/compressor.
 *
 * Original library (version 1.3) from http://csstidy.sourceforge.net/
 *
 * Parameters:
 * <pre>
 * level - (string) Level of compression.
 *         DEFAULT: 'highest_compression'
 * ob - (boolean) If true, return Csstidy object instead of string.
 *      DEFAULT: false
 * </pre>
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Text
 */
class Horde_Text_Filter_Csstidy extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'level' => 'highest_compression'
    );

    /**
     * Executes any code necessary after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return mixed  The modified text, or the Csstidy object if
     *                the 'ob' parameter is true.
     */
    public function postProcess($text)
    {
        /* Can't autoload since csstidy is an external package that doesn't
         * conform to Horde naming standards. */
        require_once dirname(__FILE__) . '/Csstidy/class.csstidy.php';

        $css_tidy = new csstidy();
        $css_tidy->set_cfg('preserve_css', true);
        $css_tidy->load_template($this->_params['level']);
        $css_tidy->parse($text);

        return empty($this->_params['ob'])
            ? $css_tidy->print->plain()
            : $css_tidy;
    }

}

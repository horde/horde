<?php
/**
 * This filter cleans up CSS output by running it through a PHP-based
 * optimizer/compressor.
 *
 * Original library (version 1.3) from http://csstidy.sourceforge.net/
 *
 * Parameters:
 *   - level: (string) Level of compression.
 *            DEFAULT: 'highest_compression'
 *   - ob: (boolean) If true, return Csstidy object instead of string.
 *         DEFAULT: false
 *   - preserve_css: (boolean) Set preserve_css flag in csstidy engine?
 *                   DEFAULT: true
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Text_Filter_Csstidy
 */
class Horde_Text_Filter_Csstidy extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'level' => 'highest_compression',
        'ob' => false,
        'preserve_css' => true
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
        require_once __DIR__ . '/Csstidy/class.csstidy.php';

        $css_tidy = new csstidy();
        $css_tidy->set_cfg('preserve_css', $this->_params['preserve_css']);
        $css_tidy->load_template($this->_params['level']);
        $css_tidy->parse($text);

        return empty($this->_params['ob'])
            ? $css_tidy->print->plain()
            : $css_tidy;
    }

}

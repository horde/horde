<?php
/**
 * This filter attempts to sanitize HTML by cleaning up malformed HTML tags.
 *
 * Parameters:
 * <pre>
 * body_only - (boolean) Only return the body data?
 *             DEFAULT: Return the whole HTML document
 * charset - (string) Charset of the data.
 *           DEFAULT: UTF-8
 * size - (integer) Only filter if data is below this size.
 *        DEFAULT: No default
 * </pre>
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Text_Filter
 */
class Horde_Text_Filter_Cleanhtml extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'body_only' => false,
        'charset' => 'UTF-8',
        'size' => false
    );

    /**
     * Executes any code necessary after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string  The modified text.
     */
    public function postProcess($text)
    {
        if (!Horde_Util::extensionExists('tidy') ||
            (($this->_params['size'] !== false) &&
             (strlen($text) > $this->_params['size']))) {
            return $text;
        }

        $tidy_config = array(
            'enclose-block-text' => true,
            'hide-comments' => true,
            'indent' => false,
            'numeric-entities' => true,
            'output-xhtml' => true,
            'preserve-entities' => true,
            'show-body-only' => !empty($this->_params['body_only']),
            'tab-size' => 0,
            'wrap' => 0
        );

        if (strtolower($this->_params['charset']) == 'us-ascii') {
            $tidy = @tidy_parse_string($text, $tidy_config, 'ascii');
            $tidy->cleanRepair();
            $text = tidy_get_output($tidy);
        } else {
            $tidy = @tidy_parse_string(Horde_String::convertCharset($text, $this->_params['charset'], 'UTF-8'), $tidy_config, 'utf8');
            $tidy->cleanRepair();
            $text = Horde_String::convertCharset(tidy_get_output($tidy), 'UTF-8', $this->_params['charset']);
        }

        return $text;
    }

}

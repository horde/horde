<?php
/**
 * Filters the given text based on the words found in a word list
 * file.
 *
 * Parameters:
 * <pre>
 * words_file  -- Filename containing the words to replace.
 * replacement -- The replacement string.  Defaults to "*****".
 * </pre>
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Text_Filter
 */
class Horde_Text_Filter_Words extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'replacement' => '*****'
    );

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        $regexp = array();

        if (is_readable($this->_params['words_file'])) {
            /* Read the file and iterate through the lines. */
            $lines = file($this->_params['words_file']);
            foreach ($lines as $line) {
                /* Strip whitespace and comments. */
                $line = preg_replace('|#.*$|', '', trim($line));

                /* Filter the text. */
                if (!empty($line)) {
                    $regexp["/(\b(\w*)$line\b|\b$line(\w*)\b)/i"] = $this->_getReplacement($line);
                }
            }
        }

        return array('regexp' => $regexp);
    }

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    protected function _getReplacement($line)
    {
        return $this->_params['replacement']
            ? $this->_params['replacement']
            :substr($line, 0, 1) . str_repeat('*', strlen($line) - 1);
    }

}

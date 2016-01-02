<?php
/**
 * Copyright 2004-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2004-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Text_Filter
 */

/**
 * Filters the given text based on the words found in a word list.
 *
 * Parameters:
 *   - replacement: (string) The replacement string. Defaults to "*****".
 *   - words: (array) List of words to replace. (Since 2.1.0)
 *   - words_file: (string) Filename containing the words to replace.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2004-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Text_Filter
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
        $regexp = $words = array();

        if (isset($this->_params['words_file']) &&
            is_readable($this->_params['words_file'])) {
            /* Read the file and iterate through the lines. */
            $lines = file($this->_params['words_file']);
            foreach ($lines as $line) {
                /* Strip whitespace and comments. */
                $words[] = preg_replace('|#.*$|', '', trim($line));
            }
        }

        if (isset($this->_params['words'])) {
            $words = array_merge(
                $words,
                array_map('trim', $this->_params['words'])
            );
        }

        foreach ($words as $val) {
            if (strlen($val)) {
                $regexp["/(\b(\w*)$val\b|\b$val(\w*)\b)/i"] = $this->_getReplacement($val);
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

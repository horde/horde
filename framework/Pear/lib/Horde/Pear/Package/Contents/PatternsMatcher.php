<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Pear
 */

/**
 * This class helps with matching file paths against search patterns.
 *
 * @author    Gunnar Wrobel <wrobel@pardus.de>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pear
 */
class Horde_Pear_Package_Contents_PatternsMatcher
{
    /**
     * The regular expression patterns.
     *
     * @var array
     */
    protected $_patterns = array();

    /**
     * Constructor.
     *
     * @param array $patterns  The patterns.
     */
    public function __construct($patterns)
    {
        $this->_prepare($patterns);
    }

    /**
     * Prepares the list of patterns from the input.
     *
     * @param string $patterns  The patterns.
     */
    protected function _prepare($patterns)
    {
        foreach ($patterns as $pattern) {
            $this->_patterns[] = $this->_getRegExpableSearchString(
                str_replace('//', '/', strtr($pattern, '\\', '/'))
            );
        }
    }

    /**
     * Does the given path match one of the regular expression patterns?
     *
     * @param string $path    The file path.
     *
     * @return boolean  True if one of the pattern matches.
     */
    public function matches($path)
    {
        foreach ($this->_patterns as $match) {
            if (preg_match('/' . $match . '/', $path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Converts $s into a string that can be used with preg_match.
     *
     * @param string $s  String with wildcards ? and *.
     *
     * @return string  Search string converted * to .*, ? to ., etc.
     */
    protected function _getRegExpableSearchString($s)
    {
        if ($s[0] == DIRECTORY_SEPARATOR) {
            $pre = '^';
        } else {
            $pre = '.*';
        }

        $x = strtr(
            $s,
            array(
                '?' => '.',
                '*' => '.*',
                '.' => '\\.',
                '\\' => '\\\\',
                '/' => '\\/',
                '-' => '\\-'
            )
        );

        if (substr($s, strlen($s) - 1) == DIRECTORY_SEPARATOR) {
            $post = '.*';
        } else {
            $post = '$';
        }

        return $pre . $x . $post;
    }
}
<?php
/**
 * Horde_Pear_Package_Contents_Ignore_Patterns:: ignores files based on a
 * pattern list.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Horde_Pear_Package_Contents_Ignore_Patterns:: ignores files based on a
 * pattern list.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Contents_Ignore_Patterns
implements Horde_Pear_Package_Contents_Ignore
{
    /**
     * The regular expressions for ignored files.
     *
     * @var array
     */
    private $_ignore = array();

    /**
     * The root position of the repository.
     *
     * @var string
     */
    private $_root;

    /**
     * Constructor.
     *
     * @param array $patterns The ignore patterns.
     * @param string $root    The root position for the files that should be
     *                        checked.
     */
    public function __construct($patterns, $root)
    {
        $this->_root = $root;
        $this->_prepare($patterns);
    }

    /**
     * Prepare the list of ignores from the input.
     *
     * @param string $patterns The ignore patterns.
     *
     * @return NULL
     */
    private function _prepare($patterns)
    {
        foreach ($patterns as $pattern) {
            $this->_ignore[] = $this->_getRegExpableSearchString(
                str_replace('//', '/', strtr($pattern, '\\', '/'))
            );
        }
    }


    /**
     * Tell whether to ignore the element.
     *
     * @param SplFileInfo $element The element to check.
     *
     * @return bool True if the element should be ignored, false otherwise.
     */
    public function isIgnored(SplFileInfo $element)
    {
        return $this->_matches(
            $this->_ignore,
            substr($element->getPathname(), strlen($this->_root))
        );
    }

    /**
     * Does the given path match one of the regular expression patterns?
     *
     * @param array  $matches The regular expression patterns.
     * @param string $path    The file path.
     *
     * @return NULL
     */
    private function _matches($matches, $path)
    {
        foreach ($matches as $match) {
            preg_match('/' . $match.'/', $path, $find);
            if (count($find)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Converts $s into a string that can be used with preg_match.
     *
     * @param string $s String with wildcards ? and *
     *
     * @return string Converts * to .*, ? to ., etc.
     */
    private function _getRegExpableSearchString($s)
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
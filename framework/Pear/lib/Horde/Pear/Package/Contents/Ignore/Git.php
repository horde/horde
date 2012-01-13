<?php
/**
 * Horde_Pear_Package_Contents_Ignore_Git:: indicates which files in a content
 * listing should be ignored based on the contents from a .gitignore file.
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
 * Horde_Pear_Package_Contents_Ignore_Git:: indicates which files in a content
 * listing should be ignored based on the contents from a .gitignore file.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Pear_Package_Contents_Ignore_Git
implements Horde_Pear_Package_Contents_Ignore
{
    /**
     * The regular expressions for ignored files.
     *
     * @var array
     */
    private $_ignore = array();

    /**
     * The regular expressions for files to exclude from ignoring.
     *
     * @var array
     */
    private $_include = array();

    /**
     * The root position of the repository.
     *
     * @var string
     */
    private $_root;

    /**
     * Constructor.
     *
     * @param string $gitignore The gitignore information

     * @param string $root      The root position for the files that should be
     *                          checked.
     */
    public function __construct($gitignore, $root)
    {
        $this->_root = $root;
        $this->_prepare($gitignore);
    }

    /**
     * Prepare the list of ignores and includes from the gitignore input.
     *
     * @param string $gitignore The content of the .gitignore file.
     *
     * @return NULL
     */
    private function _prepare($gitignore)
    {
        foreach (explode("\n", $gitignore) as $line) {
            $line = strtr($line, ' ', '');
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '!') === 0) {
                $this->_include[] = $this->_getRegExpableSearchString(
                    substr($line, 1)
                );
            } else {
                $this->_ignore[] = $this->_getRegExpableSearchString($line);
            }
        }
    }

    /**
     * Return the list of ignored patterns.
     *
     * @return array The list of patterns.
     */
    public function getIgnores()
    {
        return $this->_ignore;
    }

    /**
     * Return the list of included patterns.
     *
     * @return array The list of patterns.
     */
    public function getIncludes()
    {
        return $this->_include;
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
        $rooted_path = substr($element->getPathname(), strlen($this->_root));
        if ($this->_matches($this->_ignore, $rooted_path)
            && !$this->_matches($this->_include, $rooted_path)) {
            return true;
        }
        return false;
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
     * Converts $s into a string that can be used with preg_match
     *
     * @param string $s string with wildcards ? and *
     *
     * @return string converts * to .*, ? to ., etc.
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
                '*' => '[^\/]*',
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
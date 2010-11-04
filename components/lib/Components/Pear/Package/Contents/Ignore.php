<?php
/**
 * Components_Pear_Package_Contents_Ignore:: indicates which files in
 * a content listing should be ignored.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Pear_Package_Contents_Ignore:: indicates which files in
 * a content listing should be ignored.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Pear_Package_Contents_Ignore
{
    /**
     * The regular expressions for files to ignore.
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
     * @param string $root      The root position for the files that should be checked.
     */
    public function __construct($gitignore, $root)
    {
        $this->_root = $root;
        $this->_prepare($gitignore);
    }

    /**
     * Tell whether to ignore a file or a directory
     * allows * and ? wildcards
     *
     * @param string $file   just the file name of the file or directory,
     *                          in the case of directories this is the last dir
     * @param string $path   the full path
     * @param bool   $return value to return if regexp matches.  Set this to
     *                            false to include only matches, true to exclude
     *                            all matches
     *
     * @return bool  true if $path should be ignored, false if it should not
     * @access private
     */
    public function checkIgnore($file, $path, $return = 1)
    {
        if (!$return) {
            // This class is not about identifying included files.
            return !$return;
        }

        $rooted_path = substr($path, strlen($this->_root));
        if ($this->_matches($this->_ignore, $rooted_path)
            && !$this->_matches($this->_include, $rooted_path)) {
            return $return;
        }
        return !$return;
    }

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
     * Prepare the list of ignores and include from the gitignore input.
     *
     * @param string $gitignore
     *
     * @return NULL
     */
    private function _prepare($gitignore)
    {
        foreach (split("\n", $gitignore) as $line) {
            $line = strtr($line, ' ', '');
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '!') === 0) {
                $this->_include[] = $this->_getRegExpableSearchString(substr($line, 1));
            } else {
                $this->_ignore[] = $this->_getRegExpableSearchString($line);
            }
        }
    }

    /**
     * Converts $s into a string that can be used with preg_match
     *
     * @param string $s string with wildcards ? and *
     *
     * @return string converts * to .*, ? to ., etc.
     * @access private
     */
    private function _getRegExpableSearchString($s)
    {
        if ($s[0] == DIRECTORY_SEPARATOR) {
            $pre = '^';
        } else {
            $pre = '.*';
        }

        $x = strtr($s, array('?' => '.', '*' => '[^\/]*', '.' => '\\.', '\\' => '\\\\',
                             '/' => '\\/', '-' => '\\-'));

        if (substr($s, strlen($s) - 1) == DIRECTORY_SEPARATOR) {
            $post = '.*';
        } else {
            $post = '$';
        }

        return $pre . $x . $post;
    }
}
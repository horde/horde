<?php
/**
 * Convert the PEAR package version to the Horde version scheme.
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
 * Convert the PEAR package version to the Horde version scheme.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Components_Helper_Version
{
    /**
     * Validates and normalizes a version to be a valid PEAR version.
     *
     * @param string $version  A version string.
     *
     * @return string  The normalized version string.
     *
     * @throws Components_Exception on invalid version string.
     */
    static public function validatePear($version)
    {
        if (!preg_match('/^(\d+\.\d+\.\d+)(-git|alpha\d*|beta\d*|RC\d+)$/', $version, $match)) {
            throw new Components_Exception('Invalid version number ' . $version);
        }
        if ($match[2] == '-git') {
            $match[2] = '';
        }
        return $match[1] . $match[2];
    }

    /**
     * Convert the PEAR package version number to Horde style.
     *
     * @param string $version The PEAR package version.
     *
     * @return string The Horde style version.
     *
     * @throws Components_Exception on invalid version string.
     */
    static public function pearToHorde($version)
    {
        preg_match('/([.\d]+)(.*)/', $version, $matches);
        if (!empty($matches[2])) {
            if ($matches[2] == '-git') {
                $post = $matches[2];
            } else {
                $post = '-' . strtoupper($matches[2]);
            }
        } else {
            $post = '';
        }
        $vcomp = explode('.', $matches[1]);
        if (count($vcomp) != 3) {
            throw new Components_Exception('A version number must have 3 parts.');
        }
        if ($vcomp[2] === '0') {
            $main = $vcomp[0] . '.' . $vcomp[1];
        } else {
            $main = $matches[1];
        }
        return $main . $post;
    }

    /**
     * Convert the PEAR package version number to a descriptive tag used on
     * bugs.horde.org.
     *
     * @param string $version The PEAR package version.
     *
     * @return string The description for bugs.horde.org.
     *
     * @throws Components_Exception on invalid version string.
     */
    static public function pearToTicketDescription($version)
    {
        preg_match('/([.\d]+)(.*)/', $version, $matches);
        if (!empty($matches[2]) && !preg_match('/^pl\d/', $matches[2])) {
            if (preg_match('/^RC(\d+)/', $matches[2], $postmatch)) {
                $post = ' Release Candidate ' . $postmatch[1];
            } else if (preg_match('/^alpha(\d+)/', $matches[2], $postmatch)) {
                $post = ' Alpha';
            } else if (preg_match('/^beta(\d+)/', $matches[2], $postmatch)) {
                $post = ' Beta';
            } else {
                $post = '';
            }
        } else {
            $post = ' Final';
        }
        $vcomp = explode('.', $matches[1]);
        if (count($vcomp) != 3) {
            throw new Components_Exception('A version number must have 3 parts.');
        }
        if ($vcomp[2] === '0') {
            $main = $vcomp[0] . '.' . $vcomp[1];
        } else {
            $main = $matches[1];
        }
        return $main . $post;
    }

    /**
     * Convert the PEAR package version number to Horde style and take the
     * branch name into account.
     *
     * @param string $version The PEAR package version.
     * @param string $branch  The Horde branch name.
     *
     * @return string The Horde style version.
     */
    static public function pearToHordeWithBranch($version, $branch)
    {
        if (empty($branch)) {
            return self::pearToHorde($version);
        } else {
            return $branch . ' (' . self::pearToHorde($version) . ')';
        }
    }

    /**
     * Increments the last part of a version number by one.
     *
     * @param string $version  A version number.
     *
     * @return string  The incremented version number.
     *
     * @throws Components_Exception on invalid version string.
     */
    static public function nextVersion($version)
    {
        if (!preg_match('/^(\d+\.\d+\.)(\d+).*$/', $version, $match)) {
            throw new Components_Exception('Invalid version number ' . $version);
        }
        return $match[1] . $match[2]++ . '-git';
    }
}
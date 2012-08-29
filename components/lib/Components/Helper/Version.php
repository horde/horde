<?php
/**
 * Convert the PEAR package version to the Horde version scheme.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Convert the PEAR package version to the Horde version scheme.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
        if (!preg_match('/^(\d+\.\d+\.\d+)(-git|alpha\d*|beta\d*|RC\d+)?$/', $version, $match)) {
            throw new Components_Exception('Invalid version number ' . $version);
        }
        if (!isset($match[2]) || ($match[2] == '-git')) {
            $match[2] = '';
        }
        return $match[1] . $match[2];
    }

    /**
     * Validates the version and release stability tuple.
     *
     * @param string $version   A version string.
     * @param string $stability Release stability information.
     *
     * @throws Components_Exception on invalid version string.
     */
    static public function validateReleaseStability($version, $stability)
    {
        preg_match('/^(\d+\.\d+\.\d+)(alpha|beta|RC|dev)?\d*$/', $version, $match);
        if (!isset($match[2]) && $stability != 'stable') {
            throw new Components_Exception(
                sprintf(
                    'Stable version "%s" marked with invalid release stability "%s"!',
                    $version,
                    $stability
                )
            );
        }
        $requires = array(
            'alpha' => 'alpha',
            'beta' => 'beta',
            'RC' => 'beta',
            'dev' => 'devel'
        );
        foreach ($requires as $m => $s) {
            if (isset($match[2]) && $match[2] == $m && $stability != $s) {
                throw new Components_Exception(
                    sprintf(
                        '%s version "%s" marked with invalid release stability "%s"!',
                        $s,
                        $version,
                        $stability
                    )
                );
            }
        }
    }

    /**
     * Validates the version and api stability tuple.
     *
     * @param string $version   A version string.
     * @param string $stability Api stability information.
     *
     * @throws Components_Exception on invalid version string.
     */
    static public function validateApiStability($version, $stability)
    {
        preg_match('/^(\d+\.\d+\.\d+)(alpha|beta|RC|dev)?\d*$/', $version, $match);
        if (!isset($match[2]) && $stability != 'stable') {
            throw new Components_Exception(
                sprintf(
                    'Stable version "%s" marked with invalid api stability "%s"!',
                    $version,
                    $stability
                )
            );
        }
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
                $post = ' Alpha ' . $postmatch[1];
            } else if (preg_match('/^beta(\d+)/', $matches[2], $postmatch)) {
                $post = ' Beta ' . $postmatch[1];
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
        return $matches[1] . $post;
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
            return $version;
        }
        return $branch . ' (' . $version . ')';
    }

    /**
     * Increments the last part of a version number by one.
     *
     * Also attaches -git suffix and increments only if the old version is a
     * stable version.
     *
     * @param string $version  A version number.
     *
     * @return string  The incremented version number.
     *
     * @throws Components_Exception on invalid version string.
     */
    static public function nextVersion($version)
    {
        if (!preg_match('/^(\d+\.\d+\.)(\d+)(alpha|beta|RC|dev)?\d*$/', $version, $match)) {
            throw new Components_Exception('Invalid version number ' . $version);
        }
        if (empty($match[3])) {
            $match[2]++;
        }
        return $match[1] . $match[2] . '-git';
    }

    /**
     * Increments the last part of a version number by one.
     *
     * Only increments if the old version is a stable version. Increments the
     * release state suffix instead otherwise.
     *
     * @param string $version  A version number.
     *
     * @return string  The incremented version number.
     *
     * @throws Components_Exception on invalid version string.
     */
    static public function nextPearVersion($version)
    {
        if (!preg_match('/^(\d+\.\d+\.)(\d+)(alpha|beta|RC|dev)?(\d*)$/', $version, $match)) {
            throw new Components_Exception('Invalid version number ' . $version);
        }
        if (empty($match[3])) {
            $match[2]++;
            $match[3] = '';
        } elseif (empty($match[4])) {
            $match[4] = '';
        } else {
            $match[4]++;
        }
        return $match[1] . $match[2] . $match[3] . $match[4];
    }
}

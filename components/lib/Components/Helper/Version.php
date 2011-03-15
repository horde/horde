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
     * Convert the PEAR package version number to Horde style.
     *
     * @param string $version The PEAR package version.
     *
     * @return string The Horde style version.
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
        if ($vcomp[2] === '0') {
            $main = $vcomp[0] . '.' . $vcomp[1];
        } else {
            $main = $matches[1];
        }
        return $main . $post;
    }
}
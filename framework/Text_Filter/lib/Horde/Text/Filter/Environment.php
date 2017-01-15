<?php
/**
 * Replaces occurences of %VAR% with VAR, if VAR exists in the webserver's
 * environment.  Ignores all text after a '#' character (shell-style
 * comments).
 *
 * Copyright 2004-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Horde_Text_Filter_Environment extends Horde_Text_Filter_Base
{
    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        $regexp = array(
            '/^#.*$\n/m' => '',
            '/^([^#]*)#.*$/m' => '$1'
        );

        $regexp_callback = array(
            '/%([A-Za-z_]+)%/' => array($this, 'regexCallback')
        );

        return array(
            'regexp' => $regexp,
            'regexp_callback' => $regexp_callback
        );
    }

    /**
     * Preg callback.
     *
     * @param array $matches  preg_replace_callback() matches.
     *
     * @return string  The replacement string.
     */
    public function regexCallback($matches)
    {
        return getenv($matches[1]);
    }

}

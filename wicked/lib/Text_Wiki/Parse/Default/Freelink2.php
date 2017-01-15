<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category Horde
 * @package  Wicked
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

require_once 'Text/Wiki/Parse/Default/Freelink.php';

/**
 * Parses for wiki freelink text.
 *
 * @category Horde
 * @package  Wicked
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, versi */
class Text_Wiki_Parse_Freelink2 extends Text_Wiki_Parse_Freelink
{
    /**
     * Constructor.
     *
     * @param Text_Wiki $obj  The calling "parent" Text_Wiki object.
     */
    public function __construct($obj)
    {
        parent::__construct($obj);
        if ($this->getConf('utf-8')) {
            $any = '\p{L}';
        } else {
            $any = '\xc0-\xff';
        }
        $this->regex =
            '/' .                                                   // START regex
            "\\(\\(" .                                               // double open-parens
            "(" .                                                   // START freelink page patter
            "[-A-Za-z0-9 _+\\/.,;:!?'\"\\[\\]\\{\\}&".$any."]+" . // 1 or more of just about any character
            ")" .                                                   // END  freelink page pattern
            "(" .                                                   // START display-name
            "\|" .                                                   // a pipe to start the display name
            "[-A-Za-z0-9 _+\\/.,;:!?'\"\\[\\]\\{\\}&".$any."]+" . // 1 or more of just about any character
            ")?" .                                                   // END display-name pattern 0 or 1
            "(" .                                                   // START pattern for named anchors
            "\#" .                                                   // a hash mark
            "[A-Za-z]" .                                           // 1 alpha
            "[-A-Za-z0-9_:.]*" .                                   // 0 or more alpha, digit, underscore
            ")?" .                                                   // END named anchors pattern 0 or 1
            "()\\)\\)" .                                           // double close-parens
            '/'.($this->getConf('utf-8') ? 'u' : '');              // END regex
    }
}

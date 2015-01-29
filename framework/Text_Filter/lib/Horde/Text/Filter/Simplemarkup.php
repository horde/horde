<?php
/**
 * Highlights simple markup as used in emails or usenet postings.
 *
 * Copyright 2004-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */

/**
 * Highlights simple markup as used in emails or usenet postings.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2004-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Text_Filter
 */
class Horde_Text_Filter_Simplemarkup extends Horde_Text_Filter_Base
{
    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        $startOfLine = '((?:^|<br(?:\s*/)?>)(?:\s|&nbsp;)*)';
        $endOfLine = '((?:\s|&nbsp;)*(?:$|<br|\.))';
        $startOfWord = '(^|\s|&nbsp;|<br(?:\s*/)?>)';
        $endOfWord = '($|\s|&nbsp;|<br|\.)';

        return array('regexp' => array(
            // Bold.
            '!' . $startOfLine . '(\*[^*]+\*)' . $endOfLine .
            '|' . $startOfWord . '(\*[^*\s]+\*)' . $endOfWord . '!i'
            => '$1$4<strong>$2$5</strong>$3$6',

            // Underline.
            '!' . $startOfLine . '(_[^_]+_)' . $endOfLine .
            '|' . $startOfWord . '(_[^_\s]+_)' . $endOfWord . '!i'
            => '$1$4<u>$2$5</u>$3$6',

            // Italic.
            '!' . $startOfLine . '(/[^/]+/)' . $endOfLine .
            '|' . $startOfWord . '(/[^/\s]+/)' . $endOfWord . '!i'
            => '$1$4<em>$2$5</em>$3$6',
        ));
    }

}

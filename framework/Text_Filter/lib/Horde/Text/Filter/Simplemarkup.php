<?php
/**
 * Highlights simple markup as used in emails or usenet postings.
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

/**
 * Highlights simple markup as used in emails or usenet postings.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2004-2017 Horde LLC
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
        if (!isset($this->_params['html'])) {
            $linebreak = '\n|<br(?:\s*/)?>';
            $whitespace = '\s|&nbsp;';
        } elseif ($this->_params['html']) {
            $linebreak = '<br(?:\s*/)?>';
            $whitespace = '&nbsp;';
        } else {
            $linebreak = '\n';
            $whitespace = '\s';
        }
        $startOfLine = '((?:^|' . $linebreak . ')(?:' . $whitespace . ')*)';
        $endOfLine = '(?=(?:' . $whitespace . ')*(?:$|\.|' . $linebreak . '))';
        $startOfWord = '(^|' . $whitespace . '|' . $linebreak . ')';
        $endOfWord = '(?=$|\.|' . $whitespace . '|' . $linebreak . ')';

        return array('regexp' => array(
            // Bold.
            '#' . $startOfLine . '(\*(?:[^*](?!$|' . $linebreak . '))+\*)' . $endOfLine .
            '|' . $startOfWord . '(\*[^*\s]+\*)' . $endOfWord . '#i'
            => '$1$3<strong>$2$4</strong>',

            // Underline.
            '#' . $startOfLine . '(_(?:[^*](?!$|' . $linebreak . '))+_)' . $endOfLine .
            '|' . $startOfWord . '(_[^_\s]+_)' . $endOfWord . '#i'
            => '$1$3<u>$2$4</u>',

            // Italic.
            '#' . $startOfLine . '(/(?:[^*](?!$|' . $linebreak . '))+/)' . $endOfLine .
            '|' . $startOfWord . '(/[^/\s]+/)' . $endOfWord . '#i'
            => '$1$3<em>$2$4</em>',
        ));
    }

}

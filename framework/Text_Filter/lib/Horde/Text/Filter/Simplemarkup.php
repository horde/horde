<?php
/**
 * Highlights simple markup as used in emails or usenet postings.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
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
        return array('regexp' => array(
            // Bold.
            '/(^|\s|&nbsp;|<br \/>)(\*[^*\s]+\*)(\s|&nbsp;|<br|\.)/i' => '\1<strong>\2</strong>\3',

            // Underline.
            '/(^|\s|&nbsp;|<br \/>)(_[^_\s]+_)(\s|&nbsp;|<br|\.)/i' => '\1<u>\2</u>\3',

            // Italic.
            ';(^|\s|&nbsp\;|<br />)(/[^/\s]+/)(\s|&nbsp\;|<br|\.);i' => '\1<em>\2</em>\3')
        );
    }

}

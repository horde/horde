<?php
/**
 * Highlights simple markup as used in emails or usenet postings.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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

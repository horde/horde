<?php
/**
 * Parses a RFC 2919 List-Id field.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

/**
 * RFC 2919 List-Id parser.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Parse_Listid extends Horde_Mail_Rfc822
{
    /**
     * Parse a List-ID.
     *
     * @param string $id  The string to parse.
     *
     * @return mixed  False on parse error or object with the following
     *                properties:
     */
    public function parseListId($id)
    {
        $this->_data = $id;
        $this->_datalen = strlen($id);
        $this->_params['validate'] = true;
        $this->_ptr = 0;

        $phrase = '';
        $this->_rfc822ParsePhrase($phrase);

        if ($this->_curr(true) != '<') {
            return false;
        }

        $this->_rfc822ParseDotAtom($listid);

        if ($this->_curr(true) != '>') {
            return false;
        }

        $ob = new stdclass;
        if (strlen($phrase)) {
            $ob->phrase = Horde_Mime::decode($phrase, 'UTF-8');
        }
        $ob->id = $listid;

        return $ob;
    }

}

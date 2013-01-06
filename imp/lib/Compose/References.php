<?php
/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  IMP
 */

/**
 * Class to parse the Reference header (RFC 5322 [3.6.4]).
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  IMP
 */
class IMP_Compose_References extends Horde_Mail_Rfc822
{
    /**
     * List of references.
     *
     * @var array
     */
    public $references = array();

    /**
     * Parse a references header.
     *
     * @param string $value  Header value.
     */
    public function parse($value)
    {
        if (!strlen($value)) {
            return;
        }

        $this->_data = $value;
        $this->_datalen = strlen($value);
        $this->_params['validate'] = true;
        $this->_ptr = 0;

        $this->_rfc822SkipLwsp();

        while ($this->_curr() !== false) {
            try {
                $this->references[] = '<' . trim(strval($this->_parseAngleAddr())) . '>';
            } catch (Horde_Mail_Exception $e) {}

            // Some mailers incorrectly insert commas between reference items
            if ($this->_curr() == ',') {
                $this->_rfc822SkipLwsp(true);
            }
        }
    }

}

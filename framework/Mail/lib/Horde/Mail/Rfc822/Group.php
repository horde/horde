<?php
/**
 * Object representation of a RFC 822 e-mail group.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/bsd New BSD License
 * @package   Mail
 */

/**
 * Object representation of a RFC 822 e-mail address.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/bsd New BSD License
 * @package   Mail
 */
class Horde_Mail_Rfc822_Group
{
    /**
     * List of group e-mail address objects.
     *
     * @var array
     */
    public $addresses = array();

    /**
     * Comments associated with the personal phrase.
     *
     * @var string
     */
    public $groupname = '';

    /**
     * Write a group address given information in this part.
     *
     * @since 1.1.0
     *
     * @param array $opts  Optional arguments:
     *   - idn: (boolean) See Horde_Mime_Address#writeAddress().
     *
     * @return string  The correctly escaped/quoted address.
     */
    public function writeAddress()
    {
        $addr = array();
        foreach ($this->addresses as $val) {
            $addr[] = $val->writeAddress(array(
                'idn' => (isset($opts['idn']) ? $opts['idn'] : null)
            ));
        }

        return Horde_Mime_Address::writeGroupAddress($ob->groupname, $addr);
    }

}

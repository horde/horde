<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 */

/**
 * This class represents the Date header value (RFC 5322).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 * @since     2.5.0
 */
class Horde_Mime_Headers_Date
extends Horde_Mime_Headers_Element_Single
{
    /**
     * Generate a 'Date' header for the current time.
     *
     * @return Horde_Mime_Headers_Date  Date header object.
     */
    public static function create()
    {
        return new self(null, date('r'));
    }

    /**
     */
    public function __construct($name, $value)
    {
        parent::__construct('Date', $value);
    }

    /**
     */
    public static function getHandles()
    {
        return array(
            // Mail: RFC 5322
            'date'
        );
    }

}

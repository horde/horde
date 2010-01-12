<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */
class Horde_Http_Response_Mock extends Horde_Http_Response_Base
{
    public function getBody()
    {
        return $this->body;
    }
}

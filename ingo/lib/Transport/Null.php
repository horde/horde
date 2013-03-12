<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Brent J. Nordquist <bjn@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Ingo_Transport_Null implements a null API -- useful for just testing the UI
 * and storage.
 *
 * @author   Brent J. Nordquist <bjn@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Transport_Null extends Ingo_Transport_Base
{
    /**
     * Constructor.
     */
    public function __construct(array $params = array())
    {
        $this->_supportShares = true;
        parent::__construct($params);
    }
}

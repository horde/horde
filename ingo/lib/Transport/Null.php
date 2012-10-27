<?php
/**
 * Ingo_Transport_Null implements a null api -- useful for just testing
 * the UI and storage.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Brent J. Nordquist <bjn@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

class Ingo_Transport_Null extends Ingo_Transport
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

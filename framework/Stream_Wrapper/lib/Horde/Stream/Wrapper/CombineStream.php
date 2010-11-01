<?php
/**
 * Provides access to the Combine stream wrapper.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Support
 */

/**
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Support
 */
interface Horde_Stream_Wrapper_CombineStream
{
    /**
     * Return a reference to the data.
     *
     * @return array
     */
    public function getData();
}
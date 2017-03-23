<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   James Pepin <james@bluestatedigital.com>
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Controller
 */

/**
 * Interface for an object that houses a collection of pre/post filters.
 *
 * @author    James Pepin <james@bluestatedigital.com>
 * @author    Bob McKee <bob@bluestatedigital.com>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Controller
 */
interface Horde_Controller_FilterCollection
{
    /**
     */
    public function addPreFilter(Horde_Controller_PreFilter $filter);

    /**
     */
    public function addPostFilter(Horde_Controller_PostFilter $filter);
}

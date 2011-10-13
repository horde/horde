<?php
/**
 * Interface for an object that houses a collection of pre/post filters.
 *
 * @category Horde
 * @package  Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @license  http://www.horde.org/licenses/bsd BSD
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

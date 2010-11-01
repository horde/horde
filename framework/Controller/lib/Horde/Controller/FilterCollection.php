<?php
/**
 * Interface for an object that houses a collection of pre/post filters.
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
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

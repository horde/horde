<?php
/**
 * Interface for object post-creation filters.
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Horde_Injector
 */
interface Horde_Injector_Filter
{
    /**
     * @param Horde_Injector $injector  The active Horde_Injector
     * @param object $instance          The new instance to filter
     *
     * @return void
     */
    public function filter(Horde_Injector $injector, $instance);
}

<?php
/**
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Injector
 */

/**
 * Describes a binding class that is able to create concrete object instances.
 *
 * @author    Bob Mckee <bmckee@bywires.com>
 * @author    James Pepin <james@jamespepin.com>
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Injector
 */
interface Horde_Injector_Binder
{
    /**
     * Create an instance.
     *
     * @param Horde_Injector $injector  The injector should provide all
     *                                  required dependencies for creating the
     *                                  instance.
     *
     * @return mixed  The concrete instance.
     */
    public function create(Horde_Injector $injector);

    /**
     * Determine if one binder equals another binder
     *
     * @param Horde_Injector_Binder $binder  The binder to compare against
     *                                       $this.
     *
     * @return boolean  True if equal, false if not equal.
     */
    public function equals(Horde_Injector_Binder $binder);

}

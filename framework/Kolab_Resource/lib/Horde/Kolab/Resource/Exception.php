<?php
/**
 * This class provides the standard error class for Kolab_Resource exceptions.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Resource
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Resource
 */

/**
 * This class provides the standard error class for Kolab_Resource exceptions.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Resource
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Resource
 */
class Horde_Kolab_Resource_Exception extends Horde_Exception_Wrapped
{
    /**
     * Constants to define the error type.
     */
    const SYSTEM      = 1;
    const NO_FREEBUSY = 2;
}

<?php
/**
 * A Kolab maintainer.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides methods to deal with maintainer
 * entries for Kolab.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Object_Kolab_Maintainer extends Horde_Kolab_Server_Object_Kolab_Adminrole
{

    /**
     * The group the UID must be member of so that this object really
     * matches this class type. This may not include the root UID.
     *
     * @var array
     */
    public $required_group = array(self::ATTRIBUTE_CN => 'maintainer',
                                      Horde_Kolab_Server_Object_Kolabgroupofnames::ATTRIBUTE_VISIBILITY => false);

}

<?php
/**
 * Representation of a Kolab distribution list.
 *
 * $Horde: framework/Kolab_Server/lib/Horde/Kolab/Server/Object/distlist.php,v 1.2 2009/01/06 17:49:26 jan Exp $
 *
 * PHP version 4
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

require_once 'Horde/Kolab/Server/Object/group.php';

/**
 * This class provides methods to deal with distribution lists for Kolab.
 *
 * $Horde: framework/Kolab_Server/lib/Horde/Kolab/Server/Object/distlist.php,v 1.2 2009/01/06 17:49:26 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Object_distlist extends Horde_Kolab_Server_Object_group {

    /**
     * The LDAP filter to retrieve this object type
     *
     * @var string
     */
    var $filter = '(&(objectClass=kolabGroupOfNames)(mail=*))';


    /**
     * The attributes required when creating an object of this class.
     *
     * @var array
     */
    var $_required_attributes = array(
        KOLAB_ATTR_MAIL,
    );
};

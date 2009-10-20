<?php
/**
 * An interface marking object class search operations.
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
 * An interface marking object class search operations.
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
abstract class Horde_Kolab_Server_Object_Search_Base implements Horde_Kolab_Server_Object_Search
{
    /**
     * A link to the composite server handler.
     *
     * @var Horde_Kolab_Server_Composite
     */
    private $_composite;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Composite $composite A link to the composite
     *                                                server handler.
     */
    public function __construct(Horde_Kolab_Server_Composite $composite)
    {
        $this->_composite = $composite;
    }
    
    /**
     * Identify the GUID(s) of the result entry(s).
     *
     * @param array $result The LDAP search result.
     *
     * @return boolean|array The GUID(s) or false if there was no result.
     */
    protected function guidFromResult($result)
    {
        if (empty($result)) {
            return false;
        }
        return array_keys($result);
    }
}
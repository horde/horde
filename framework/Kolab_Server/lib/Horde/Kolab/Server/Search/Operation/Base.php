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
abstract class Horde_Kolab_Server_Search_Operation_Base
implements Horde_Kolab_Server_Search_Operation_Interface
{
    /**
     * A link to the server structure.
     *
     * @var Horde_Kolab_Server_Structure_Interface
     */
    private $_structure;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Structure_Interface $structure A link to the
     *                                                          server
     *                                                          structure.
     */
    public function __construct(
        Horde_Kolab_Server_Structure_Interface $structure
    ) {
        $this->_structure = $structure;
    }

    /**
     * Return the reference to the server structure.
     *
     * @return Horde_Kolab_Server_Structure_Interface
     */
    public function getStructure()
    {
        return $this->_structure;
    }
    
    /**
     * Identify the GUID(s) of the result entry(s).
     *
     * @param array $result The LDAP search result.
     *
     * @return boolean|array The GUID(s) or false if there was no result.
     */
    protected function guidFromResult(
        Horde_Kolab_Server_Result_Interface $result
    ) {
        return array_keys($result->asArray());
    }
}
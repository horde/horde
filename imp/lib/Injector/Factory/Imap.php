<?php
/**
 * A Horde_Injector:: based IMP_Imap:: factory.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */

/**
 * A Horde_Injector:: based IMP_Imap:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */
class IMP_Injector_Factory_Imap
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the IMP_Imap:: instance.
     *
     * @param string $id  The server ID.
     *
     * @return IMP_Imap  The singleton instance.
     * @throws IMP_Exception
     */
    public function create($id = null)
    {
        if (is_null($id)) {
            $id = isset($_SESSION['imp'])
                ? $_SESSION['imp']['server_key']
                : 'default';
        }

        if (!isset($this->_instances[$id])) {
            $this->_instances[$id] = new IMP_Imap($id);
        }

        return $this->_instances[$id];
    }

}

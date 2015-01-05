<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A factory for creating the storage object to use for compose attachments.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_ComposeAtc extends Horde_Core_Factory_Base
{
    /**
     * The class to use for attachment storage.
     *
     * @var string
     */
    public $classAtc = 'IMP_Compose_Attachment_Storage_Temp';

    /**
     * The class to use for linked storage.
     *
     * @var string
     */
    public $classLinked = 'IMP_Compose_Attachment_Storage_VfsLinked';

    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the requested attachment storage instance.
     *
     * @param string $user  User.
     * @param string $id    Attachment identifier.
     * @param string $type  Either 'atc' or 'linked'. If null, will
     *                      auto-determine.
     *
     * @return IMP_Compose_Attachment_Storage  Storage object.
     * @throws IMP_Exception
     */
    public function create($user = null, $id = null, $type = null)
    {
        global $conf;

        if (($type == 'linked') ||
            (is_null($type) && !empty($conf['compose']['link_attachments']))) {
            $classname = empty($conf['compose']['link_attach_threshold'])
                ? $this->classLinked
                : 'IMP_Compose_Attachment_Storage_AutoDetermine';
        } else {
            $classname = $this->classAtc;
        }

        if (is_null($user)) {
            $user = $GLOBALS['registry']->getAuth();
        }

        if (is_null($id)) {
            return new $classname($user);
        }

        $sig = hash(
            (PHP_MINOR_VERSION >= 4) ? 'fnv132' : 'sha1',
            implode('|', array($user, $id))
        );

        if (!isset($this->_instances[$sig])) {
            $this->_instances[$sig] = new $classname($user, $id);
        }

        return $this->_instances[$sig];
    }

}

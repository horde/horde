<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based IMP_Compose factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Compose
extends Horde_Core_Factory_Base
implements Horde_Shutdown_Task
{
    /** Storage key for compose objects. */
    const STORAGE_KEY = 'compose_ob/';

    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     */
    public function __construct(Horde_Injector $injector)
    {
        parent::__construct($injector);

        Horde_Shutdown::add($this);
    }

    /**
     * Return the IMP_Compose:: instance.
     *
     * @param string $cacheid  The cache ID string.
     *
     * @return IMP_Compose  The singleton compose instance.
     * @throws IMP_Exception
     */
    public function create($cacheid = null)
    {
        global $session;

        if (empty($cacheid)) {
            $cacheid = strval(new Horde_Support_Randomid());
        } elseif (!isset($this->_instances[$cacheid])) {
            $this->_instances[$cacheid] = $session->get('imp', self::STORAGE_KEY . $cacheid);
        }

        if (empty($this->_instances[$cacheid])) {
            $this->_instances[$cacheid] = new IMP_Compose($cacheid);
        }

        return $this->_instances[$cacheid];
    }

    /**
     * Tasks to perform on shutdown.
     */
    public function shutdown()
    {
        global $session;

        foreach ($this->_instances as $key => $val) {
            switch ($val->changed) {
            case 'changed':
                $session->set('imp', self::STORAGE_KEY . $key, $val);
                break;

            case 'deleted':
                $session->remove('imp', self::STORAGE_KEY . $key);
                break;
            }
        }
    }

    /**
     * Return a list of all compose objects currently stored in the session.
     *
     * @return array  List of IMP_Compose objects.
     */
    public function getAllObs()
    {
        global $session;

        return array_filter(
            $session->get('imp', self::STORAGE_KEY, $session::TYPE_ARRAY)
        );
    }

}

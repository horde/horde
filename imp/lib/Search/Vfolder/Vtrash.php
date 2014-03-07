<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Data structure for storing the virtual trash.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Search_Vfolder_Vtrash extends IMP_Search_Vfolder_Builtin
{
    /**
     * Display this virtual folder in the preferences screen?
     *
     * @var boolean
     */
    public $prefDisplay = false;

    /**
     * Initialization tasks.
     */
    protected function _init()
    {
        $this->_id = 'vtrash';
        $this->_label = _("Virtual Trash");

        $this->add(new IMP_Search_Element_Flag(
            Horde_Imap_Client::FLAG_DELETED,
            true
        ));
    }

    /**
     * Get object properties.
     * Only create mailbox list on demand.
     *
     * @see __get()
     */
    public function __get($name)
    {
        global $injector;

        switch ($name) {
        case 'mboxes':
            $iterator = new IMP_Ftree_IteratorFilter(
                $injector->getInstance('IMP_Ftree')
            );
            $iterator->add($iterator::CONTAINERS);

            return array_map('strval', iterator_to_array($iterator, false));
        }

        return parent::__get($name);
    }

}

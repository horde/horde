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
 * Base definition for built-in filters.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
abstract class IMP_Search_Filter_Builtin extends IMP_Search_Filter
{
    /**
     */
    protected $_canEdit = false;

    /**
     */
    protected $_nosave = array('c', 'i', 'l');

    /**
     * Constructor.
     *
     * The 'add', 'id', 'label', and 'mboxes' parameters are ignored.
     */
    public function __construct(array $opts = array())
    {
        $this->enabled = empty($opts['disable']);

        $this->_init();
    }

    /**
     */
    abstract protected function _init();

    /**
     */
    public function unserialize($data)
    {
        parent::unserialize($data);
        $this->_init();
    }

}

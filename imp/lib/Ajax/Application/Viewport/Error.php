<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Viewport error object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Viewport_Error
{
    /**
     * View object.
     *
     * @var IMP_Mailbox
     */
    private $_mbox;

    /**
     * Constructor.
     *
     * @param IMP_Mailbox $mbox  Viewport view.
     */
    public function __construct(IMP_Mailbox $mbox)
    {
        $this->_mbox = $mbox;
    }

    /**
     * Prepare the object used by the ViewPort javascript class.
     *
     * @return object  The ViewPort object.
     */
    public function toObject()
    {
        $ob = new stdClass;
        $ob->cacheid = strval(new Horde_Support_Randomid());
        $ob->label = $this->_mbox->label;
        $ob->view = $this->_mbox->form_to;

        return $ob;
    }

}

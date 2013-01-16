<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Extends base Indices object by incorporating base mailbox information.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Indices_Mailbox extends IMP_Indices
{
    /**
     * The BUIDs list.
     *
     * @var IMP_Indices
     */
    public $buids;

    /**
     * Base mailbox name.
     *
     * @var IMP_Mailbox
     */
    public $mailbox;

    /**
     * Constructor.
     *
     * @param Horde_Variables  Variables object. These GET/POST parameters are
     *                         reserved in IMP:
     *   - buid: (string) BUID [Browser UID].
     *   - mailbox: (string) Base64url encoded mailbox.
     *   - muid: (string) MUID [Mailbox + UID].
     *   - uid: (string) UID [Actual mail UID].
     */
    public function __construct()
    {
        if (func_num_args() == 1) {
            $args = func_get_args();

            if ($args[0] instanceof Horde_Variables) {
                if (isset($args[0]->mailbox)) {
                    $this->mailbox = IMP_Mailbox::formFrom($args[0]->mailbox);

                    if (isset($args[0]->buid)) {
                        $this->buids = new IMP_Indices($this->mailbox, $args[0]->buid);
                        parent::__construct($this->mailbox->fromBuids($this->buids));
                    } elseif (isset($vars->uid)) {
                        parent::__construct($this->mailbox, $args[0]->uid);
                    }
                }

                if (isset($args[0]->muid)) {
                    parent::__construct($args[0]->muid);
                }
            }
        }

        if (!isset($this->buids)) {
            $this->buids = new IMP_Indices();
        }

        if (!isset($this->mailbox)) {
            $this->mailbox = IMP_Mailbox::get('INBOX');
        }
    }

    /**
     */
    public function joinIndices()
    {
        $ob = new IMP_Indices($this);
        $ob->add($this->buids);
        return $ob;
    }

}

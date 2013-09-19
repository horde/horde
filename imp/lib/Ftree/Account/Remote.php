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
 * Implementation of the account object for a remote server.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_Account_Remote extends IMP_Ftree_Account_Imap
{
    /**
     */
    public function __construct($id = null)
    {
        if (is_null($id)) {
            throw new InvalidArgumentException('Constructor requires an account ID.');
        }

        parent::__construct($id);
    }

    /**
     */
    public function getList($query = null)
    {
        global $injector;

        $out = array();

        if ($this->imp_imap->init) {
            $remote = $injector->getInstance('IMP_Remote');
            $raccount = $remote[strval($this)];
            if (!is_integer($query)) {
                $query = $remote->getMailboxById($query) ?: self::INIT;
            }

            foreach (parent::getList($query) as $val) {
                $out[] = array_filter(array(
                    'a' => $val['a'] | IMP_Ftree::ELT_REMOTE_MBOX,
                    'p' => isset($val['p']) ? $raccount->mailbox($val['p']) : strval($raccount),
                    'v' => $raccount->mailbox($val['v'])
                ));
            }
        }

        return $out;
    }

}

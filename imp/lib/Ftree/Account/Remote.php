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
    /* Remote account key. */
    const REMOTE_KEY = "remote\0";

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

        $init = $this->imp_imap->init;

        $remote = $injector->getInstance('IMP_Remote');
        $raccount = $remote[strval($this)];
        if (!is_integer($query)) {
            $query = $remote->getMailboxById($query) ?: self::INIT;
        }

        if ($query & self::INIT) {
            $out[] = array(
                'a' => IMP_Ftree::ELT_REMOTE | IMP_Ftree::ELT_NOSELECT | IMP_Ftree::ELT_NONIMAP,
                'v' => self::REMOTE_KEY
            );

            $mask = $init ? IMP_Ftree::ELT_REMOTE_AUTH : 0;
            $out[] = array(
                'a' => $mask | IMP_Ftree::ELT_REMOTE | IMP_Ftree::ELT_IS_SUBSCRIBED | IMP_Ftree::ELT_NONIMAP,
                'p' => self::REMOTE_KEY,
                'v' => strval($this)
            );
        }

        if ($init) {
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

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
class IMP_Imap_Tree_Account_Remote extends IMP_Imap_Tree_Account_Imap
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
        $out = array();

        if ($this->imp_imap->init) {
            foreach (parent::getList($query) as $val) {
                $out[] = array_filter(array(
                    'a' => $val['a'] | IMP_Imap_Tree::ELT_REMOTE_MBOX,
                    'p' => isset($val['p']) ? $val['p'] : null,
                    'v' => $this->_id . "\0" . $val['v']
                ));
            }
        }

        return $out;
    }

}

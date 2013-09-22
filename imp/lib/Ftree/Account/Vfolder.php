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
 * Implementation of the account object for Virtual Folders.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_Account_Vfolder extends IMP_Ftree_Account
{
    /* Virtual folder key. */
    const VFOLDER_KEY = "vfolder\0";

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

        $imp_search = $injector->getInstance('IMP_Search');
        $out = array();

        if ($imp_search[strval($this)]->enabled) {
            $out[] = array(
                'a' => IMP_Ftree::ELT_VFOLDER | IMP_Ftree::ELT_NOSELECT | IMP_Ftree::ELT_NONIMAP,
                'v' => self::VFOLDER_KEY
            );
            $out[] = array(
                'a' => IMP_Ftree::ELT_VFOLDER | IMP_Ftree::ELT_IS_SUBSCRIBED | IMP_Ftree::ELT_NONIMAP,
                'p' => self::VFOLDER_KEY,
                'v' => strval($this)
            );
        }

        return $out;
    }

}

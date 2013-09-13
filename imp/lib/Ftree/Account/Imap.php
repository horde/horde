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
 * Implementation of the account object for an IMAP server.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_Account_Imap extends IMP_Ftree_Account
{
    /**
     */
    public function getList($query = null)
    {
        global $prefs;

        $out = $searches = array();
        $unsub = false;

        if (is_integer($query)) {
            $ns = $this->imp_imap->getNamespaceList();

            if ($query & self::INIT) {
                /* Add namespace elements. */
                if ($prefs->getValue('tree_view')) {
                    foreach ($ns as $val) {
                        $type = null;

                        switch ($val['type']) {
                        case Horde_Imap_Client::NS_OTHER:
                            $type = IMP_Ftree::OTHER_KEY;
                            break;

                        case Horde_Imap_Client::NS_SHARED:
                            $type = IMP_Ftree::SHARED_KEY;
                            break;
                        }

                        if (!is_null($type)) {
                            $out[$type] = array(
                                'a' => IMP_Ftree::ELT_NOSELECT | IMP_Ftree::ELT_NAMESPACE | IMP_Ftree::ELT_NONIMAP,
                                'v' => $type
                            );
                        }
                    }
                }

                $searches[] = 'INBOX';
            } elseif ($query & self::UNSUB) {
                $unsub = true;
            }

            foreach (array_keys($ns) as $val) {
                $searches[] = $val . '*';
            }
        } else {
            $searches[] = $query;
            $unsub = true;
        }

        $res = $this->imp_imap->listMailboxes($searches, $unsub ? Horde_Imap_Client::MBOX_UNSUBSCRIBED : Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS, array(
            'attributes' => true,
            'delimiter' => true,
            'sort' => true
        ));

        foreach ($res as $val) {
            $key = strval($val['mailbox']);
            if (isset($out[$key]) ||
                in_array('\nonexistent', $val['attributes'])) {
                continue;
            }

            /* Break apart the name via the delimiter and go step by
             * step through the name to make sure all subfolders exist
             * in the tree. */
            $parts = explode($val['delimiter'], $key);
            $parent = null;

            for ($i = 1, $p_count = count($parts); $i <= $p_count; ++$i) {
                $part = implode($val['delimiter'], array_slice($parts, 0, $i));

                if (!isset($out[$part])) {
                    if ($p_count == $i) {
                        $attr = 0;

                        if (!$unsub ||
                            in_array('\subscribed', $val['attributes'])) {
                            $attr |= IMP_Ftree::ELT_IS_SUBSCRIBED;
                        }

                        if (in_array('\noselect', $val['attributes'])) {
                            $attr |= IMP_Ftree::ELT_NOSELECT;
                        }

                        if (in_array('\noinferiors', $val['attributes'])) {
                            $attr |= IMP_Ftree::ELT_NOINFERIORS;
                        }
                    } else {
                        $attr = IMP_Ftree::ELT_NOSELECT;
                    }

                    $out[$part] = array(
                        'a' => $attr,
                        'v' => $part
                    );
                    if (!is_null($parent)) {
                        $out[$part]['p'] = $parent;
                    }
                }

                $parent = $part;
            }
        }

        if (is_integer($query) &&
            ($query & self::INIT) &&
            ($query & self::UNSUB)) {
            $out = array_merge($out, $this->getList(self::UNSUB));
        }

        return $out;
    }

}

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
 *
 * @property-read IMP_Imap $imp_imap  IMP IMAP object.
 */
class IMP_Ftree_Account_Imap extends IMP_Ftree_Account
{
    /* Defines used with namespace display. */
    const OTHER_KEY = "other\0";
    const SHARED_KEY = "shared\0";

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'imp_imap':
            return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create($this->_id == IMP_Ftree::BASE_ELT ? null : $this->_id);
        }
    }

    /**
     */
    public function getList($query = null)
    {
        global $prefs;

        $imp_imap = $this->imp_imap;
        $out = $searches = array();
        $unsub = false;

        if (is_integer($query)) {
            $ns = $imp_imap->getNamespaceList();

            if ($query & self::INIT) {
                /* Add namespace elements. */
                if ($prefs->getValue('tree_view')) {
                    foreach ($ns as $val) {
                        $type = null;

                        switch ($val['type']) {
                        case Horde_Imap_Client::NS_OTHER:
                            $attr = IMP_Ftree::ELT_NAMESPACE_OTHER;
                            $type = self::OTHER_KEY;
                            break;

                        case Horde_Imap_Client::NS_SHARED:
                            $attr = IMP_Ftree::ELT_NAMESPACE_SHARED;
                            $type = self::SHARED_KEY;
                            break;
                        }

                        if (!is_null($type)) {
                            $out[$type] = array(
                                'a' => $attr | IMP_Ftree::ELT_NOSELECT | IMP_Ftree::ELT_NONIMAP,
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

        $res = $imp_imap->listMailboxes($searches, $unsub ? Horde_Imap_Client::MBOX_UNSUBSCRIBED : Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS, array(
            'attributes' => true,
            'delimiter' => true,
            'sort' => true
        ));

        foreach ($res as $val) {
            if (in_array('\nonexistent', $val['attributes'])) {
                continue;
            }

            $mbox = strval($val['mailbox']);
            $ns_info = $imp_imap->getNamespace($mbox);
            $parent = null;

            /* Break apart the name via the delimiter and go step by
             * step through the name to make sure all subfolders exist
             * in the tree. */
            if (strlen($val['delimiter'])) {
                /* Strip personal namespace. */
                if (!empty($ns_info['name']) &&
                    (strpos($mbox, $ns_info['name']) === 0)) {
                    $parts = explode($val['delimiter'], substr($mbox, strlen($ns_info['name'])));
                    $parts[0] = $ns_info['name'] . $parts[0];
                } else {
                    $parts = explode($val['delimiter'], $mbox);
                }

                switch ($ns_info['type']) {
                case Horde_Imap_Client::NS_OTHER:
                case Horde_Imap_Client::NS_SHARED:
                    if ($prefs->getValue('tree_view')) {
                        $parent = $ns_info['type']
                            ? self::OTHER_KEY
                            : self::SHARED_KEY;
                    }
                    break;
                }
            } else {
                $parts = array($mbox);
            }

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

    /**
     */
    public function delete(IMP_Ftree_Element $elt)
    {
        return ($elt->inbox || $elt->namespace)
            ? 0
            : self::DELETE_ELEMENT;
    }

}

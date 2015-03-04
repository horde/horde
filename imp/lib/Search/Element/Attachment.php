<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * The attachment search query.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Search_Element_Attachment
extends IMP_Search_Element
implements IMP_Search_Element_Callback
{
    /**
     * Constructor.
     *
     * @param boolean $not  If true, do a 'NOT' search of $text.
     */
    public function __construct($not = false)
    {
        /* Data element: (integer) Do a NOT search? */
        $this->_data = intval(!empty($not));
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        /* Filtering takes place in searchCallback(). */
        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        return $this->_data
            ? _("messages without attachment(s)")
            : _("messages with attachment(s)");
    }

    /**
     */
    public function searchCallback(IMP_Mailbox $mbox, array $ids)
    {
        $fetch_query = new Horde_Imap_Client_Fetch_Query();
        $fetch_query->structure();

        $fetch_res = $mbox->imp_imap->fetch($mbox, $fetch_query, array(
            'ids' => $mbox->imp_imap->getIdsOb($ids)
        ));

        $out = array();

        foreach ($ids as $v) {
            if (isset($fetch_res[$v])) {
                $atc = false;
                foreach ($fetch_res[$v]->getStructure()->partIterator() as $val) {
                    if (IMP_Mime_Attachment::isAttachment($val)) {
                        $atc = true;
                        break;
                    }
                }

                if (($this->_data && $atc) || (!$this->_data && !$atc)) {
                    continue;
                }
            }

            $out[] = $v;
        }

        return $out;
    }

}

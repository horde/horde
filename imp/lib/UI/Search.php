<?php
/**
 * The IMP_UI_Search:: class is designed to provide a place to store common
 * code shared among IMP's various UI views for the search page.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_UI_Search
{
    /* Defines used to determine what kind of field query we are dealing
     * with. */
    const HEADER = 1;
    const BODY = 2;
    const DATE = 3;
    const TEXT = 4;
    const SIZE = 5;

    /* Defines used to identify the flag input. */
    const FLAG_NOT = 0;
    const FLAG_HAS = 1;

    /**
     * Return the base search fields.
     *
     * @return array  The base search fields.
     */
    public function searchFields()
    {
        return array(
            'from' => array(
                'label' => _("From"),
                'type' => self::HEADER,
                'not' => true
            ),
            'to' => array(
                'label' => _("To"),
                'type' => self::HEADER,
                'not' => true
            ),
            'cc' => array(
                'label' => _("Cc"),
                'type' => self::HEADER,
                'not' => true
            ),
            'bcc' => array(
                'label' => _("Bcc"),
                'type' => self::HEADER,
                'not' => true
            ),
            'subject' => array(
                'label' => _("Subject"),
                'type' => self::HEADER,
                'not' => true
            ),
            'body' => array(
               'label' => _("Body"),
               'type' => self::BODY,
                'not' => true
            ),
            'text' => array(
               'label' => _("Entire Message"),
               'type' => self::TEXT,
                'not' => true
            ),
            'date_on' => array(
                'label' => _("Date ="),
                'type' => self::DATE,
                'not' => true
            ),
            'date_until' => array(
                'label' => _("Date <"),
                'type' => self::DATE,
                'not' => true
            ),
            'date_since' => array(
                'label' => _("Date >="),
                'type' => self::DATE,
                'not' => true
            ),
            // Displayed in KB, but stored internally in bytes
            'size_smaller' => array(
                'label' => _("Size (KB) <"),
                'type' => self::SIZE,
                'not' => false
            ),
            // Displayed in KB, but stored internally in bytes
            'size_larger' => array(
                'label' => _("Size (KB) >"),
                'type' => self::SIZE,
                'not' => false
            ),
        );
    }

    /**
     * Return the base flag fields.
     *
     * @return array  The base flag fields.
     */
    public function flagFields()
    {
        return array(
            'seen' => array(
                'flag' => '\\seen',
                'label' => _("Seen messages"),
                'type' => self::FLAG_HAS
            ),
            'unseen' => array(
                'flag' => '\\seen',
                'label' => _("Unseen messages"),
                'type' => self::FLAG_NOT
            ),
            'answered' => array(
                'flag' => '\\answered',
                'label' => _("Answered messages"),
                'type' => self::FLAG_HAS
            ),
            'unanswered' => array(
                'flag' => '\\answered',
                'label' => _("Unanswered messages"),
                'type' => self::FLAG_NOT
            ),
            'flagged' => array(
                'flag' => '\\flagged',
                'label' => _("Flagged messages"),
                'type' => self::FLAG_HAS
            ),
            'unflagged' => array(
                'flag' => '\\flagged',
                'label' => _("Unflagged messages"),
                'type' => self::FLAG_NOT
            ),
            'deleted' => array(
                'flag' => '\\deleted',
                'label' => _("Deleted messages"),
                'type' => self::FLAG_HAS
            ),
            'undeleted' => array(
                'flag' => '\\deleted',
                'label' => _("Undeleted messages"),
                'type' => self::FLAG_NOT
            ),
        );
    }

    /**
     * Creates a search query.
     *
     * @param array $uiinfo  A UI info array (see imp/search.php).
     *
     * @return object  A search object (Horde_Imap_Client_Search_Query).
     */
    public function createQuery($search)
    {
        $query = new Horde_Imap_Client_Search_Query();

        $search_array = array();
        $search_fields = $this->searchFields();
        $flag_fields = $this->flagFields();

        foreach ($search['field'] as $key => $val) {
            $ob = new Horde_Imap_Client_Search_Query();

            if (isset($flag_fields[$val])) {
                $ob->flag($flag_fields[$val]['flag'], (bool)$flag_fields[$val]['type']);
                $search_array[] = $ob;
            } else {
                switch ($search_fields[$val]['type']) {
                case self::HEADER:
                    if (!empty($search['text'][$key])) {
                        $ob->headerText($val, $search['text'][$key], $search['text_not'][$key]);
                        $search_array[] = $ob;
                    }
                    break;

                case self::BODY:
                case self::TEXT:
                    if (!empty($search['text'][$key])) {
                        $ob->text($search['text'][$key], $search_fields[$val]['type'] == self::BODY, $search['text_not'][$key]);
                        $search_array[] = $ob;
                    }
                    break;

                case self::DATE:
                    if (!empty($search['date'][$key]['day']) &&
                        !empty($search['date'][$key]['month']) &&
                        !empty($search['date'][$key]['year'])) {
                        $date = new Horde_Date($search['date']);
                        $ob->dateSearch($date, ($val == 'date_on') ? Horde_Imap_Client_Search_Query::DATE_ON : (($val == 'date_until') ? Horde_Imap_Client_Search_Query::DATE_BEFORE : Horde_Imap_Client_Search_Query::DATE_SINCE));
                        $search_array[] = $ob;
                    }
                    break;

                case self::SIZE:
                    if (!empty($search['text'][$key])) {
                        $ob->size(intval($search['text'][$key]), $val == 'size_larger');
                        $search_array[] = $ob;
                    }
                    break;
                }
            }
        }

        /* Search match. */
        if ($search['match'] == 'and') {
            $query->andSearch($search_array);
        } elseif ($search['match'] == 'or') {
            $query->orSearch($search_array);
        }

        return $query;
    }

    /**
     *
     */
    public function processBasicSearch($mbox, $criteria, $text, $not, $flag)
    {
        $search_query = array(
            'field' => array(),
            'match' => 'and',
            'text' => array(),
            'text_not' => array()
        );

        if ($criteria) {
            $search_query['field'][] = $criteria;
            $search_query['text'][] = $text;
            $search_query['text_not'][] = $not;
        }

        if ($flag) {
            $search_query['field'][] = $flag;
            $search_query['text'][] = $search_query['text_not'][] = null;
        }

        /* Set the search in the IMP session. */
        return $GLOBALS['imp_search']->createSearchQuery($this->createQuery($search_query), array($mbox), array(), _("Search Results"));
    }

}

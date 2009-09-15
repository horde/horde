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
    /**
     * Creates a search query.
     *
     * @param array $search  The list of search criteria.
     *
     * @return object  A search object (Horde_Imap_Client_Search_Query).
     */
    public function createQuery($search)
    {
        $query = new Horde_Imap_Client_Search_Query();

        $search_array = array();
        $search_fields = $GLOBALS['imp_search']->searchFields();
        $flag_fields = $GLOBALS['imp_search']->flagFields();
        $imp_flags = IMP_Imap_Flags::singleton();

        foreach ($search as $rule) {
            $ob = new Horde_Imap_Client_Search_Query();

            if (isset($flag_fields[$rule->t])) {
                $val = $imp_flags->parseFormId($rule->t);
                $ob->flag($val['flag'], $val['set']);
                $search_array[] = $ob;
            } else {
                /* Ignore unknown types. */
                switch ($search_fields[$rule->t]['type']) {
                case 'header':
                    if (!empty($rule->v)) {
                        $ob->headerText($rule->t, $rule->v, !empty($rule->n));
                        $search_array[] = $ob;
                    }
                    break;

                case 'body':
                case 'text':
                    if (!empty($rule->v)) {
                        $ob->text($rule->c, $search_fields[$rule->t]['type'] == 'body', !empty($rule->n));
                        $search_array[] = $ob;
                    }
                    break;

                case 'date':
                    if (!empty($rule->v)) {
                        $date = new Horde_Date(array('year' => $rule->v->y, 'month' => $rule->v->m + 1, 'mday' => $rule->v->d));
                        $ob->dateSearch($date, ($rule->t == 'date_on') ? Horde_Imap_Client_Search_Query::DATE_ON : (($rule->t == 'date_until') ? Horde_Imap_Client_Search_Query::DATE_BEFORE : Horde_Imap_Client_Search_Query::DATE_SINCE));
                        $search_array[] = $ob;
                    }
                    break;

                case 'size':
                    if (!empty($rule->v)) {
                        $ob->size(intval($rule->v), $rule->t == 'size_larger');
                        $search_array[] = $ob;
                    }
                    break;
                }
            }
        }

        $query->andSearch($search_array);

        return $query;
    }

    /**
     * Create a search query from input gathered from the basic search script
     * (imp/search-basic.php).
     *
     * @param string $mbox      The mailbox to search.
     * @param string $criteria  The criteria to search.
     * @param string $text      The criteria text.
     * @param boolean $not      Should the criteria search be a not search?
     * @param string $flag      A flag to search for.
     *
     * @return string  The search query ID.
     */
    public function processBasicSearch($mbox, $criteria, $text, $not, $flag)
    {
        $c_list = array();

        if ($criteria) {
            $search_fields = $GLOBALS['imp_search']->searchFields();
            $tmp = new stdClass;
            $tmp->t = $criteria;
            $tmp->v = ($search_fields[$criteria]['type'] == 'size')
                ? floatval($text) * 1024
                : $text;
            if ($search_fields[$criteria]['not']) {
                $tmp->n = (bool)$not;
            }
            $c_list[] = $tmp;
        }

        if ($flag) {
            $tmp = new stdClass;
            $tmp->t = $flag;
            $c_list[] = $tmp;
        }

        /* Set the search in the IMP session. */
        return $GLOBALS['imp_search']->createSearchQuery($this->createQuery($c_list), array($mbox), $c_list, _("Search Results"), IMP_Search::MBOX_PREFIX . IMP_Search::BASIC_SEARCH);
    }

}

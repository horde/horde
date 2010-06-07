<?php
/**
 * $Horde: trean/lib/Forms/Search.php,v 1.1 2007/02/04 22:52:45 chuck Exp $
 *
 * @package Trean
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/**
 * @package Trean
 */
class SearchForm extends Horde_Form {

    function SearchForm(&$vars)
    {
        parent::Horde_Form($vars, _("Search Bookmarks"), 'Search_Bookmarks');

        $this->setButtons(_("Search"));
        $this->addVariable(_("Title"), 'title', 'text', false);
        $this->addVariable(_("Description"), 'description', 'text', false);
        $this->addVariable(_("URL"), 'url', 'text', false);
        $this->addVariable(_("Combine"), 'combine', 'enum', false, false, null, array(array('OR' => _("OR"), 'AND' => _("AND"))));
        $this->addVariable(_("Match"), 'op', 'enum', false, false, null, array(array('LIKE' => _("Any Part of the field"), '=' => _("Whole Field"))));
    }

}

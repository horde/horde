<?php
/**
 * This class is the search form that will be responsible for finding
 * everything.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @package Sesha
 */
class SearchForm extends Horde_Form {

    /**
     * Basic constructor for the SearchForm.
     *
     * @param  Horde_Variables $vars  The default variables to use.
     */
    function SearchForm(&$vars)
    {
        parent::Horde_Form($vars, _("Search The Inventory"));

        $this->appendButtons(_("Search"));
        $this->addHidden('', 'actionId', 'text', true);

        $this->addVariable(_("Search these properties"), 'location', 'multienum', true,
            false, null, array(array(
                SESHA_SEARCH_ID            => _("Stock ID"),
                SESHA_SEARCH_NAME          => _("Item Name"),
                SESHA_SEARCH_NOTE          => _("Item Note"),
                SESHA_SEARCH_PROPERTY      => _("Property Value"))));
        $this->addVariable(_("For this value"), 'criteria', 'text', true);
    }

}

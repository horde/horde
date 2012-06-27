<?php
/**
 * This class is the search form that will be responsible for finding
 * everything.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 * Copyright 2004-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @package Sesha
 */
class Sesha_Form_Search extends Horde_Form {

    /**
     * Basic constructor for the SearchForm.
     *
     * @param  Horde_Variables $vars  The default variables to use.
     */
    public function __construct($vars)
    {
        parent::__construct($vars, _("Search The Inventory"));

        $this->appendButtons(_("Search"));
        $this->addHidden('', 'actionId', 'text', true);

        $this->addVariable(_("Search these properties"), 'location', 'multienum', true,
            false, null, array(array(
                Sesha::SEARCH_ID            => _("Stock ID"),
                Sesha::SEARCH_NAME          => _("Item Name"),
                Sesha::SEARCH_NOTE          => _("Item Note"),
                Sesha::SEARCH_PROPERTY      => _("Property Value"))));
        $this->addVariable(_("For this value"), 'criteria', 'text', true);
    }

}

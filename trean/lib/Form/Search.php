<?php
/**
 * @package Trean
 */
class Trean_Form_Search extends Horde_Form
{
    public function __construct($vars)
    {
        parent::__construct($vars, _("Search Bookmarks"), 'Search_Bookmarks');

        $this->setButtons(_("Search"));
        $this->addVariable(_("Search for"), 'q', 'text', false);
    }
}

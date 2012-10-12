<?php
/**
 * This class provides a data structure for storing a stored filter.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Search_Filter extends IMP_Search_Query
{
    /**
     * Display this filter in the preferences screen?
     *
     * @var boolean
     */
    public $prefDisplay = true;

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'querytext':
            $text = array();

            foreach ($this->_criteria as $elt) {
                $text[] = $elt->queryText();
                if (!($elt instanceof IMP_Search_Element_Or)) {
                    $text[] = _("and");
                }
            }
            array_pop($text);

            return sprintf(_("Search %s"), implode(' ', $text));
        }

        return parent::__get($name);
    }

    /**
     * Creates a query object from this filter.
     *
     * @param array $mboxes  The list of mailboxes to apply the filter to.
     * @param string $id     The query ID to use.
     *
     * @return IMP_Search_Query  A query object.
     */
    public function toQuery(array $mboxes, $id = null)
    {
        return new IMP_Search_Query(array(
            'add' => $this->_criteria,
            'id' => $id,
            'label' => $this->label,
            'mboxes' => $mboxes
        ));
    }

}

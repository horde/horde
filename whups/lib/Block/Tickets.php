<?php
/**
 * Base class for blocks that display a summary of tickets.
 */
class Whups_Block_Tickets extends Horde_Core_Block
{
    /**
     * Is this block enabled?
     *
     * @var boolean
     */
    public $enabled = false;

    /**
     * Returns the parameters needed by block.
     *
     * @return array  The block's parameters.
     */
    protected function _params()
    {
        $all = array_flip(Whups::getSearchResultColumns());
        unset($all['id']);
        return array('columns' => array(
            'type' => 'multienum',
            'name' => _("Columns"),
            'default' => array_values(Whups::getSearchResultColumns('block')),
            'values' => $all,
        ));
    }

    /**
     */
    protected function _content()
    {
    }

    /**
     * Generates a table with ticket information.
     *
     * @param array $tickets  A list of tickets.
     * @param string $tableClass  The DOM ID to use for the generated table.
     *
     * @return string  Table HTML code.
     */
    protected function _table($tickets, $tableId = null)
    {
        if (!$tableId) {
            $tableId = get_class($this);
        }

        $sortby = $GLOBALS['prefs']->getValue('sortby');
        $sortdirclass = ' class="' . ($GLOBALS['prefs']->getValue('sortdir') ? 'sortup' : 'sortdown') . '"';
        $html = '<thead><tr><th' . ($sortby == 'id' ? $sortdirclass : '') . '>' . _("Id") . '</th>';
        foreach (Whups::getSearchResultColumns('block', $this->_params['columns']) as $name => $column) {
            if ($column == 'id') {
                continue;
            }
            $html .= '<th' . ($sortby == $column ? $sortdirclass : '') . '>' . $name . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        Whups::sortTickets($tickets);
        foreach ($tickets as $ticket) {
            foreach (Whups::getSearchResultColumns('block', $this->_params['columns']) as $column) {
                $thevalue = Whups::formatColumn($ticket, $column);
                $sortval = '';
                if ($column == 'timestamp' || $column == 'due' ||
                    substr($column, 0, 5) == 'date_') {
                    $sortval = (strlen($ticket[$column]) ? ' sortval="' . $ticket[$column] . '"' : '');
                }

                $html .= '<td' . $sortval . '>' . (strlen($thevalue) ? $thevalue : '&nbsp;') . '</td>';
            }
            $html .= '</tr>';
        }

        $GLOBALS['page_output']->addScriptFile('tables.js', 'horde');

        return '<table id="' . htmlspecialchars($tableId) . '" cellspacing="0" class="tickets striped sortable">' . $html . '</tbody></table>';
    }

}

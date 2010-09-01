<?php

$block_name = _("Finances Results");

/**
 * @package Horde_Block
 */
class Horde_Block_fima_summary extends Horde_Block {

    var $_app = 'fima';

    function _title()
    {
        global $registry;

        $label = !empty($this->_params['block_title'])
            ? $this->_params['block_title']
            : $registry->get('name');
        return Horde::link(Horde::url($registry->getInitialPage(), true))
            . htmlspecialchars($label) . '</a>';
    }

    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';
        $ledgers = array();
        foreach (Fima::listLedgers() as $id => $ledger) {
            $ledgers[$id] = $ledger->get('name');
        }

        return array('block_title' => array(
                         'type' => 'text',
                         'name' => _("Block title"),
                         'default' => $GLOBALS['registry']->get('name')),
                     'show_ledger' => array(
                         'type' => 'enum',
                         'name' => _("Show summary of this ledger"),
                         'default' => $GLOBALS['registry']->getAuth(),
                         'values' => $ledgers),
                     'show_months' => array(
                         'type' => 'enum',
                         'name' => _("Number of months to display"),
                         'default' => '3',
                         'values' => array(
                             '1' => '1',
                             '2' => '2',
                             '3' => '3',
                             '4' => '4',
                             '5' => '5',
                             '6' => '6',
                         )));
    }

    function _content()
    {
        global $registry, $prefs;
        require_once dirname(__FILE__) . '/../base.php';

        $now = time();
        $html = '';
        
        /* Get account types and posting types. */
        $accounttypes = Fima::getAccountTypes();
        
        /* Params. */
        $showmonths = $this->_params['show_months'];
        $datefmt = Fima::convertDateToPeriodFormat($GLOBALS['prefs']->getValue('date_format'));
        $period_start = mktime(0, 0, 0, date('n') - $showmonths + 1, 1);
        $period_end = mktime(0, 0, 0);
        
        /* Rows. */
        $rows = array(FIMA_ACCOUNTTYPE_INCOME => $accounttypes[FIMA_ACCOUNTTYPE_INCOME],
                      FIMA_ACCOUNTTYPE_EXPENSE => $accounttypes[FIMA_ACCOUNTTYPE_EXPENSE],
                      '__result__' => _("Total Result"),
                      '__resultasset__' => _("Asset Result"));
        
        /* Columns. */
        $cols = array();
        $coldummy = array();
        
        for ($period = $period_start; $period <= $period_end; $period = strtotime('+1 month', $period)) {
            $colId = strftime('%Y%m', $period);
            $cols[$colId] = strftime($datefmt, $period);
            $coldummy[$colId] = 0;
        }
        
        /* Initialize matrix. */
        $data = array('__header__' => array('__header__' => ''));
        foreach ($cols as $colId => $col) {
            $data['__header__'][$colId] = $col;
        }
        foreach ($rows as $rowId => $row) {
            $data[$rowId] = array('__header__' => $row) + $coldummy;
        }
        
        /* Results. */
        $filters = array();
        $filters[] = array('account_type', FIMA_ACCOUNTTYPE_ASSET, '<>');
        $filters[] = array('type', FIMA_POSTINGTYPE_ACTUAL);
        $filters[] = array('date', (int)$period_start, '>=');
        $filters[] = array('date', (int)$period_end, '<=');
        $result = Fima::getResults(array('date_month', 'account_type'), $filters);
        if (is_a($result, 'PEAR_Error')) {
            return '<em>' . _("Error when retrieving results.") . '</em>';
        }
        foreach ($result as $rowId => $row) {
            foreach ($row as $colId => $value) {
                $data[$rowId][$colId] = $value;
                $data['__result__'][$colId] += $value;
            }
        }
        
        /* Asset Results. */
        $filters = array();
        $filters[] = array('account_type', FIMA_ACCOUNTTYPE_ASSET, '<>');
        $filters[] = array('type', FIMA_POSTINGTYPE_ACTUAL);
        $filters[] = array('date', (int)$period_start, '<');
        $result = Fima::getResults(array('type'), $filters);
        if (is_a($result, 'PEAR_Error')) {
            return '<em>' . _("Error when retrieving results.") . '</em>';
        }
        $assetresult = 0;
        foreach ($result as $rowId => $row) {
            foreach ($row as $colId => $value) {
                $assetresult += $value;
            }
        }
        foreach ($data['__resultasset__'] as $colId => $col) {
            if (preg_match('/__header.*__/', $colId)) {
                continue;
            }
            $assetresult += $data['__result__'][$colId];
            $data['__resultasset__'][$colId] = $assetresult;
        }
        
        /* Output. */
        foreach ($data as $rowId => $row) {
            $html .= '<tr class="' . (preg_match('/__result(.*)?__/', $rowId) ? 'result' : 'item') . '">';
            foreach ($row as $colId => $value) {
                if ($rowId === '__header__') {
                    $html .= '<th class="item">' . htmlspecialchars($value) . '</th>';
                } elseif ($colId === '__header__') {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                } else {
                    $html .= '<td class="' . (($value < 0) ? 'negative' : 'positive') . ' amount">' . Fima::convertValueToAmount($value) . '</td>';
                }
            }
            $html .= '</tr>';
        }

        if (empty($html)) {
            return '<em>' . _("No results to display") . '</em>';
        }

        return '<table cellspacing="0" class="reportTable" width="100%">'
            . $html . '</table>';
    }

}

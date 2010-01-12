<?php
/**
 * Fima_Report_Analysis.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Fima_Report */
require_once FIMA_BASE . '/lib/Report.php';

/*
 * Fima_Report_Analysis class.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_Report_Analysis extends Fima_Report {

    /*
     * Constructs a new Analysis Report.
     */
    function Fima_Report_Analysis($params = array())
    {
        $this->_params = $params;
    }

    /*
     * Executes the report.
     *
     * @return mixed   True or PEAR Error
     */
    function _execute()
    {
        /* Get account types, posting types and accounts. */
        $accounttypes = Fima::getAccountTypes();
        $postingtypes = Fima::getPostingTypes();
        $accounts = Fima::listAccounts();
        $accountIndex = array();
        foreach ($accounts as $accountId => $account) {
            $accountIndex[$account['number']] = $accountId;
        }
        $groups = array(FIMA_ACCOUNTTYPE_INCOME, FIMA_ACCOUNTTYPE_EXPENSE);

        /* Params. */
        if (($display = $this->getParam('display')) === null) {
            return PEAR::raiseError(_("No display type"));
        }
        $posting_account = $this->getParam('posting_account');
        $period_start = $this->getParam('period_start');
        $period_end   = $this->getParam('period_end');
        $reference_start = $this->getParam('reference_start');
        $reference_end   = $this->getParam('reference_end');
        $subaccounts = $this->getParam('subaccounts');
        $nullrows = $this->getParam('nullrows');
        $graph = $this->getParam('graph');
        $sortby = $this->getParam('sortby');
        $sortdir = $this->getParam('sortdir');

        /* Rows. */
        // accounts (dynamically)

        /* Columns. */
        $cols = explode('_', $display);

        $displaypostingtypes = array();
        $displayreference = false;
        $displaydiffa = false;
        $displaydiffp = false;

        $colheaders = array('__header__' => _("Account"));
        $coldummy = array();
        foreach ($cols as $colPos => $colId) {
            if (isset($postingtypes[$colId])) {
                $displaypostingtypes[] = $colId;
                $colheaders[$colId] = $postingtypes[$colId];
            } elseif ($colId == 'reference') {
                $displayreference = true;
                $colheaders[$colId] = _("Reference");
            } elseif ($colId == 'difference') {
                $displaydiffa = $colPos;
                $colheaders[$colId] = _("Difference");
            } elseif ($colId == '%') {
                $displaydiffp = $colPos;
                $colheaders[$colId] = _("Diff. (%)");
            }
            $coldummy[$colId] = 0;
        }

        /* Initialize matrix. */
        $data = array();
        $data['__headersort__'] = $colheaders;
        $datagroups = array(FIMA_ACCOUNTTYPE_INCOME => array(), FIMA_ACCOUNTTYPE_EXPENSE => array());
        // add parent accounts
        if ($posting_account) {
            foreach ($posting_account as $accountId => $account) {
                if ($accounts[$account]['parent_id']) {
                    $posting_account[] = $accounts[$account]['parent_id'];
                }
            }
        }
        foreach ($accounts as $accountId => $account) {
            if ($posting_account) {
                if (!in_array($account['account_id'], $posting_account)) {
                    continue;
                }
            }
            if ($account['type'] == FIMA_ACCOUNTTYPE_ASSET) {
                continue;
            }
            if ($account['parent_id'] === null || !isset($accounts[$account['parent_id']])) {
                $datagroups[$account['type']][$account['number']] = array('__header__' => $account['label']) + $coldummy;
                if ($subaccounts) {
                    $datagroups[$account['type']][$account['number']]['__subaccounts__'] = array();
                }
            } elseif ($subaccounts) {
                $datagroups[$account['type']][$accounts[$account['parent_id']]['number']]['__subaccounts__'][$account['number']] = array('__header__' => ' '.$account['label']) + $coldummy;
            }
        }
        foreach ($datagroups as $datagroupId => $datagroup) {
            $datagroups[$datagroupId]['__result' . $datagroupId . '__'] = array('__header__' => sprintf(_("%s Result"), $accounttypes[$datagroupId])) + $coldummy;
            $data += $datagroups[$datagroupId];
        }

        /* Results. */
        foreach ($groups as $group) {
            $filters = array();
            if ($posting_account) {
                $filters[] = array('account', $posting_account);
            }
            $filters[] = array('account_type', $group);
            $filters[] = array('type', $displaypostingtypes);
            if ($period_start !== null) {
                $filters[] = array('date', (int)$period_start, '>=');
            }
            if ($period_end !== null) {
                $filters[] = array('date', (int)$period_end, '<=');
            }

            $result = Fima::getResults(array('type', $subaccounts ? 'account_number' : 'account_parent'), $filters);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            foreach ($result as $rowId => $row) {
                foreach ($row as $colId => $value) {
                    if (isset($data[$rowId])) {
                        $data[$rowId][$colId] += $value;
                    } elseif (($parentId = $accounts[$accountIndex[$rowId]]['parent_id']) !== null) {
                        if (!$graph) {
                            $data[$accounts[$parentId]['number']][$colId] += $value;
                        }
                        $data[$accounts[$parentId]['number']]['__subaccounts__'][$rowId][$colId] += $value;
                    }
                    $data['__result' . $group . '__'][$colId] += $value;
                }
            }
        }

        /* Reference. */
        if ($displayreference) {
            foreach ($groups as $group) {
                $groupresult = array();

                $filters = array();
                if ($posting_account) {
                    $filters[] = array('account', $posting_account);
                }
                $filters[] = array('account_type', $group);
                $filters[] = array('type', $displaypostingtypes);
                if (($reference_start = $this->getParam('reference_start')) !== null) {
                    $filters[] = array('date', (int)$reference_start, '>=');
                }
                if (($reference_end = $this->getParam('reference_end')) !== null) {
                    $filters[] = array('date', (int)$reference_end, '<=');
                }

                $result = Fima::getResults(array('type', $subaccounts ? 'account_number' : 'account_parent'), $filters);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
                foreach ($result as $rowId => $row) {
                    foreach ($row as $colId => $value) {
                        $colId = 'reference';
                        if (isset($data[$rowId])) {
                            $data[$rowId][$colId] += $value;
                        } elseif (($parentId = $accounts[$accountIndex[$rowId]]['parent_id']) !== null) {
                            $data[$accounts[$parentId]['number']][$colId] += $value;
                            $data[$accounts[$parentId]['number']]['__subaccounts__'][$rowId][$colId] += $value;
                        }
                        $data['__result' . $group . '__'][$colId] += $value;
                    }
                }
            }
        }

        /* Totals. */
        $data['__resulttotal__'] = array('__header__' => _("Total Result")) + $coldummy;
        foreach ($cols as $colId) {
            foreach ($groups as $groupId => $group) {
                $data['__resulttotal__'][$colId] += $data['__result' . $group . '__'][$colId];
            }
        }

        /* Difference. */
        if ($displaydiffa > 1 || $displaydiffp > 1) {
            $cola1 = $cols[$displaydiffa - 2];
            $cola2 = $cols[$displaydiffa - 1];
            $colp1 = $cols[$displaydiffp - 2];
            $colp2 = $cols[$displaydiffp - 1];
            foreach ($data as $rowId => $row) {
                if (preg_match('/__header.*__/', $rowId)) {
                    continue;
                }
                if ($displaydiffa > 1) {
                    $data[$rowId]['difference'] = $data[$rowId][$cola1] - $data[$rowId][$cola2];
                }
                if ($displaydiffp > 1) {
                    if ($data[$rowId][$colp1] != 0) {
                        $data[$rowId]['%'] = $data[$rowId][$colp2] / abs($data[$rowId][$colp1]) * 100;
                    } else {
                        $data[$rowId]['%'] = null;
                    }
                }
            }
        }

        /* Null Rows. */
        if (!$nullrows) {
            foreach ($data as $rowId => $row) {
                if (preg_match('/__(header|result).*__/', $rowId)) {
                    continue;
                }
                $isnullrow = true;
                foreach ($row as $colId => $value) {
                    if (preg_match('/__(header).*__/', $colId) || $colId == '__resultasset__') {
                        continue;
                    }
                    if ($value != 0) {
                        $isnullrow = false;
                    }
                }
                if (!$nullrows && $isnullrow) {
                    unset($data[$rowId]);
                }
            }
        }

        /* Sorting. */
        if ($sortby === null || !isset($colheaders[$sortby])) {
            $sortby = $this->setParam('sortby', '__header__');
        }
        if ($sortdir === null) {
            $sortdir = $this->setParam('sortdir', FIMA_SORT_ASCEND);
        }
        $x = -1;
        $sortIndex = array();
        foreach ($data as $rowId => $row) {
            if (preg_match('/__(header|result).*__/', $rowId)) {
                $x++;
                $sortIndex[$x] = array($rowId => $row[$sortby]);
                $x++;
            } else {
                if (!isset($sortIndex[$x])) {
                    $sortIndex[$x] = array();
                }
                $sortIndex[$x][$rowId] = $row[$sortby];
            }
        }

        foreach ($sortIndex as $indexId => $indexGroup) {
            if (count($indexGroup) > 0) {
                if ($sortdir) {
                    arsort($indexGroup);
                } else {
                    asort($indexGroup);
                }
            }
            foreach ($indexGroup as $rowId => $index) {
                $this->_data[$rowId] = $data[$rowId];
                if (isset($data[$rowId]['__subaccounts__'])) {
                    if (count($data[$rowId]['__subaccounts__']) > 0) {
                        $subSortIndex = array();
                        foreach ($data[$rowId]['__subaccounts__'] as $subId => $sub) {
                            $subSortIndex[$subId] = $sub[$sortby];
                        }
                        if ($sortdir) {
                            arsort($subSortIndex);
                        } else {
                            asort($subSortIndex);
                        }

                        foreach ($subSortIndex as $subId => $sub) {
                            $this->_data[$subId] = $data[$rowId]['__subaccounts__'][$subId];
                        }
                    }
                }
                unset($this->_data[$rowId]['__subaccounts__']);
            }
        }

        return true;
    }

    /*
     * Output the graph.
     *
     * @return mixed   True or PEAR Error
     */
    function _getGraph()
    {
        /* Data. */
        $display = explode('_', $this->getParam('display'));

        $labels = array();
        $data = array();
        $ix = 0;
        foreach ($this->_data as $rowId => $row) {
            if (preg_match('/__header.*__/', $rowId)) {
                foreach ($row as $colId => $value) {
                    $labels[$colId] = $value;
                }
            } elseif (preg_match('/__(result).*__/', $rowId)) {
                $ix++;
            } else {
                $labels[$rowId] = isset($row['__header__']) ? $row['__header__'] : $rowId;
                if (!isset($data[$ix])) {
                    $data[$ix] = array();
                }
                $data[$ix][$rowId] = $row[$display[0]];
            }
        }

        // grouping
        $sum = array();
        #for ($i = 0; $i < count($data); $i++) {
        foreach ($data as $i => $d) {
            $sum[$i] = array_sum($data[$i]);
            if ($sum[$i] >= 0) {
                arsort($data[$i]);
            } else {
                asort($data[$i]);
            }
            $topdata = array_slice($data[$i], 0, 5, true);
            $data[$i]['__rest__'] = 0;
            $data[$i]['__blank__'] = ($sum[$i] == 0) ? 0 : $sum[$i] / abs($sum[$i]);
            foreach ($data[$i] as $key => $value) {
                if ((!isset($topdata[$key]) || $value == 0) && $key != '__rest__' && $key != '__blank__') {
                    $data[$i]['__rest__'] += $value;
                    unset($data[$i][$key]);
                }
            }
            if ($data[$i]['__rest__'] == 0) {
                unset($data[$i]['__rest__']);
            }
            $sum[$i] = abs($sum[$i]);
        }
        $labels['__rest__'] = _("Rest");
        $labels['__blank__'] = _("Difference");

        // diff
        $max = max($sum);
        #for ($i = 0; $i < count($sum); $i++) {
        foreach ($sum as $i => $s) {
            if ($max != $sum[$i]) {
                $data[$i]['__blank__'] *= $max - $sum[$i];
            } else {
                unset($data[$i]['__blank__']);
            }
        }

        $this->data = $data;

        /* Additional params. */
        $this->setParam('graph', 'Pie');
        $this->setParam('labels', $labels);
        $this->setParam('subtitle', $labels[($display[0] == 'reference') ? $display[1] : $display[0]]);
        $this->setParam('marker', true);

        return true;
    }

}

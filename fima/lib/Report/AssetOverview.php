<?php
/**
 * Fima_Report_AssetOverview.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Fima_Report */
require_once FIMA_BASE . '/lib/Report.php';

/*
 * Fima_Report_AssetOverview class.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_Report_AssetOverview extends Fima_Report {

    /*
     * Constructs a new AssetOverview Report.
     */
    function Fima_Report_AssetOverview($params = array())
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
        $accounts = Fima::listAccounts(array(array('type', FIMA_ACCOUNTTYPE_ASSET)));
        $accountIndex = array();
        foreach ($accounts as $accountId => $account) {
            $accountIndex[$account['number']] = $accountId;
        }

        /* Params. */
        if (($display = $this->getParam('display')) === null) {
            return PEAR::raiseError(_("No display type"));
        }
        $display = explode('_', $display);
        $period_start = ($display[0] == 'reference') ? $this->getParam('reference_start') : $this->getParam('period_start');
        $period_end   = ($display[0] == 'reference') ? $this->getParam('reference_end')   : $this->getParam('period_end');
        $display = ($display[0] == 'reference') ? $display[1] : $display[0];
        $cumulate = $this->getParam('cumulate');
        $nullrows = $this->getParam('nullrows');
        $subaccounts = $this->getParam('subaccounts');
        $graph = $this->getParam('graph');
        $yearly = $this->getParam('yearly');
        $datefmt = $yearly ? '%Y' : Fima::convertDateToPeriodFormat($GLOBALS['prefs']->getValue('date_format'));
        $sortby = $this->getParam('sortby');
        $sortdir = $this->getParam('sortdir');

        /* Rows. */
        // accounts (dynamically)

        /* Columns. */
        $cols = array();
        $colheaders = array('__header__' => _("Asset"));
        $coldummy = array();
        for ($period = $period_start; $period <= $period_end; $period = strtotime($yearly ? '+1 year' : '+1 month', $period)) {
            $colId = strftime($yearly ? '%Y' : '%Y%m', $period);
            $cols[] = $colId;
            $colheaders[$colId] = strftime($datefmt, $period);
            $coldummy[$colId] = 0;
        }
        $colheaders['__result__'] = _("Total Result");
        $coldummy['__result__'] = 0;

        /* Initialize matrix. */
        $data = array();
        $data['__headersort__'] = $colheaders;
        foreach ($accounts as $accountId => $account) {
            if ($account['parent_id'] === null || !isset($accounts[$account['parent_id']])) {
                $data[$account['number']] = array('__header__' => $account['label']) + $coldummy;
                if ($subaccounts) {
                    $data[$account['number']]['__subaccounts__'] = array();
                }
            } elseif ($subaccounts) {
                $data[$accounts[$account['parent_id']]['number']]['__subaccounts__'][$account['number']] = array('__header__' => ' '.$account['label']) + $coldummy;
            }
        }
        $data['__result__'] = array('__header__' => _("Asset Result")) + $coldummy;

        /* Initialize asset results. */
        if ($cumulate) {
            $assetresults = Fima::getAssetResults($display, $period_start - 1);
            $period = strftime($yearly ? '%Y' : '%Y%m', $period_start);
            foreach ($assetresults as $assetresult) {
                if (isset($data[$accounts[$assetresult['account_id']]['number']])) {
                    $data[$accounts[$assetresult['account_id']]['number']][$period] += $assetresult['account_result'];
                    $data[$accounts[$assetresult['account_id']]['number']]['__result__'] += $assetresult['account_result'];
                } elseif (($parentId = $accounts[$assetresult['account_id']]['parent_id']) !== null) {
                    $data[$accounts[$parentId]['number']][$period] += $assetresult['account_result'];
                    $data[$accounts[$parentId]['number']]['__result__'] += $assetresult['account_result'];
                    if ($subaccounts) {
                        $data[$accounts[$parentId]['number']]['__subaccounts__'][$accounts[$assetresult['account_id']]['number']][$period] += $assetresult['account_result'];
                        $data[$accounts[$parentId]['number']]['__subaccounts__'][$accounts[$assetresult['account_id']]['number']]['__result__'] += $assetresult['account_result'];
                    }
                }
                $data['__result__'][$period] += $assetresult['account_result'];
                $data['__result__']['__result__'] += $assetresult['account_result'];
            }
        }

        /* Results. */
        $filters = array();
        $filters[] = array('type', $display);
        if ($period_start !== null) {
            $filters[] = array('date', (int)$period_start, '>=');
        }
        if ($period_end !== null) {
            $filters[] = array('date', (int)$period_end, '<=');
        }

        // asset accounts
        $result = Fima::getResults(array($yearly ? 'date_year' : 'date_month', $subaccounts ? 'asset_number' : 'asset_parent'), $filters);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        // asset accounts as posting account
        $filters[] = array('account_type', FIMA_ACCOUNTTYPE_ASSET);
        $result2 = Fima::getResults(array($yearly ? 'date_year' : 'date_month', $subaccounts ? 'account_number' : 'account_parent'), $filters);
        if (is_a($result2, 'PEAR_Error')) {
            return $result2;
        }
        foreach ($result2 as $rowId => $row) {
            if (!isset($result[$rowId])) {
                $result[$rowId] = array();
            }
            foreach ($row as $colId => $value) {
                if (!isset($result[$rowId][$colId])) {
                    $result[$rowId][$colId] = 0;
                }
                $result[$rowId][$colId] -= $value;
            }
        }

        foreach ($result as $rowId => $row) {
            foreach ($row as $colId => $value) {
                if (isset($data[$rowId])) {
                    $data[$rowId][$colId] += $value;
                    $data[$rowId]['__result__'] += $value;
                } elseif (($parentId = $accounts[$accountIndex[$rowId]]['parent_id']) !== null) {
                    $data[$accounts[$parentId]['number']][$colId] += $value;
                    $data[$accounts[$parentId]['number']]['__result__'] += $value;
                    if ($subaccounts) {
                        $data[$accounts[$parentId]['number']]['__subaccounts__'][$rowId][$colId] += $value;
                        $data[$accounts[$parentId]['number']]['__subaccounts__'][$rowId]['__result__'] += $value;
                    }
                }
                $data['__result__'][$colId] += $value;
                $data['__result__']['__result__'] += $value;
            }
        }

        /* Null Rows and Cumulate. */
        if (!$nullrows || $cumulate) {
            foreach ($data as $rowId => $row) {
                if (preg_match('/__(header).*__/', $rowId)) {
                    continue;
                }
                $cumulatevalue = 0;
                $isnullrow = true;
                foreach ($row as $colId => $value) {
                    if (preg_match('/__(header|result).*__/', $colId)) {
                        continue;
                    }
                    if ($colId == '__subaccounts__') {
                        if (count($value) > 0) {
                            foreach ($value as $subRowId => $subRow) {
                                $subcumulatevalue = 0;
                                $subisnullrow = true;
                                foreach ($subRow as $subColId => $subValue) {
                                    if (preg_match('/__(header|result).*__/', $subColId)) {
                                        continue;
                                    }
                                    if ($cumulate) {
                                        $data[$rowId]['__subaccounts__'][$subRowId][$subColId] += $subcumulatevalue;
                                        $subcumulatevalue = $data[$rowId]['__subaccounts__'][$subRowId][$subColId];
                                    }
                                    if ($data[$rowId]['__subaccounts__'][$subRowId][$subColId] != 0) {
                                        $subisnullrow = false;
                                    }
                                }
                                if (!$nullrows && $subisnullrow) {
                                    unset($data[$rowId]['__subaccounts__'][$subRowId]);
                                }
                            }
                        }
                    } else {
                        if ($cumulate) {
                            $data[$rowId][$colId] += $cumulatevalue;
                            $cumulatevalue = $data[$rowId][$colId];
                        }
                        if ($data[$rowId][$colId] != 0) {
                            $isnullrow = false;
                        }
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
        $display = ($display[0] == 'reference') ? $display[1] : $display[0];
        $postingtypes = Fima::getPostingTypes();

        $labels = array();
        $data = $this->_data;
        $sum = array();
        foreach ($data as $rowId => $row) {
            if (preg_match('/__header.*__/', $rowId)) {
                foreach ($row as $colId => $value) {
                    if (!(preg_match('/__(header|result).*__/', $colId) && $colId != '__resulttotal__')) {
                        $labels[$colId] = $value;
                    }
                }
            }
            if (preg_match('/__(header|result).*__/', $rowId)) {
                unset($data[$rowId]);
            } else {
                $labels[$rowId] = isset($row['__header__']) ? $row['__header__'] : $rowId;
                if ($data[$rowId]['__result__'] != 0) {
                    $sum[$rowId] = $data[$rowId]['__result__'];
                }
                unset($data[$rowId]['__header__']);
                unset($data[$rowId]['__result__']);
            }
        }

        // grouping
        asort($sum);
        $topdata = array_slice($sum, 0, 5, true) + array_slice($sum, -5, 5, true);
        foreach ($data as $rowId => $row) {
            if (!isset($topdata[$rowId])) {
                unset($data[$rowId]);
            }
        }

        $this->data = $data;

        /* Additional params. */
        $this->setParam('graph', 'Line');
        $this->setParam('labels', $labels);
        $this->setParam('subtitle', $postingtypes[$display]);
        $this->setParam('invert', true);

        return true;
    }

}

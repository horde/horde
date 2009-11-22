<?php
/**
 * Fima_Report_PeriodOverview.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Fima_Report */
require_once FIMA_BASE . '/lib/Report.php';

/*
 * Fima_Report_PeriodOverview class.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_Report_PeriodOverview extends Fima_Report {

    /*
     * Constructs a new PeriodOverview Report.
     */
    function Fima_Report_PeriodOverview($params = array())
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
        /* Get account types. */
        $accounttypes = Fima::getAccountTypes();

        /* Params. */
        if (($display = $this->getParam('display')) === null) {
            return PEAR::raiseError(_("No display type"));
        }
        $display = explode('_', $display);
        $posting_account = $this->getParam('posting_account');
        $period_start = ($display[0] == 'reference') ? $this->getParam('reference_start') : $this->getParam('period_start');
        $period_end   = ($display[0] == 'reference') ? $this->getParam('reference_end')   : $this->getParam('period_end');
        $display = ($display[0] == 'reference') ? $display[1] : $display[0];
        $cumulate = $this->getParam('cumulate');
        $nullrows = $this->getParam('nullrows');
        $yearly = $this->getParam('yearly');
        $graph = $this->getParam('graph');
        $datefmt = $yearly ? '%Y' : Fima::convertDateToPeriodFormat($GLOBALS['prefs']->getValue('date_format'));
        $sortby = $this->getParam('sortby');
        $sortdir = $this->getParam('sortdir');

        /* Rows. */
        $rows = array();
        for ($period = $period_start; $period <= $period_end; $period = strtotime($yearly ? '+1 year' : '+1 month', $period)) {
            $rows[strftime($yearly ? '%Y' : '%Y%m', $period)] = strftime($datefmt, $period);
        }

        /* Columns. */
        $cols = array(FIMA_ACCOUNTTYPE_INCOME, FIMA_ACCOUNTTYPE_EXPENSE);
        $colheaders = array('__header__' => _("Period"));
        $coldummy = array();
        $groups = array('0' => "%s", '1' => "e.o. %s", 'total' => "Total %s");
        foreach ($groups as $groupId => $group) {
            foreach ($cols as $colPos => $colId) {
                $coldummy[$colId . $groupId] = 0;
                $colheaders[$colId . $groupId] = sprintf($group, $accounttypes[$colId]);
            }
            $coldummy['__result' . $groupId . '__'] = 0;
            $colheaders['__result' . $groupId . '__'] = sprintf($group, _("Result"));
        }
        $colheaders['__resultasset__'] = _("Asset Result");
        $coldummy['__resultasset__'] = 0;

        /* Initialize matrix. */
        $data = array();
        $data['__headersort__'] = $colheaders;
        foreach ($rows as $rowId => $rowLabel) {
            $data[$rowId] = array('__header__' => $rowLabel) + $coldummy;
        }

        /* Results. */
        $groups = array('0', '1');
        $total = array('__header__' => _("Total Result")) + $coldummy;
        foreach ($groups as $group) {
            $filters = array();
            if ($posting_account) {
                $filters[] = array('account', $posting_account);
            }
            $filters[] = array('account_type', $cols);
            $filters[] = array('type', $display);
            $filters[] = array('eo', $group);
            if ($period_start !== null) {
                $filters[] = array('date', (int)$period_start, '>=');
            }
            if ($period_end !== null) {
                $filters[] = array('date', (int)$period_end, '<=');
            }

            $result = Fima::getResults(array('account_type', $yearly ? 'date_year' : 'date_month'), $filters);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            foreach ($result as $rowId => $row) {
                foreach ($row as $colId => $value) {
                    $data[$rowId][$colId . $group] = $value;
                    $data[$rowId]['__result' . $group . '__'] += $value;
                    $total[$colId . $group] += $value;
                    $total['__result' . $group . '__'] += $value;
                }
            }
        }

        /* Totals. */
        $data['__resulttotal__'] = $total;
        foreach ($data as $rowId => $row) {
            if (preg_match('/__header.*__/', $rowId)) {
                continue;
            }
            foreach ($cols as $colId) {
                $data[$rowId][$colId . 'total'] = $data[$rowId][$colId . '0'] + $data[$rowId][$colId . '1'];
                
            }
            $data[$rowId]['__resulttotal__'] = $data[$rowId]['__result0__'] + $data[$rowId]['__result1__'];
        }
        
         /* Asset Results. */
        $filters = array();
        $filters[] = array('account_type', FIMA_ACCOUNTTYPE_ASSET, '<>');
        $filters[] = array('type', $display);
        if ($period_start !== null) {
            $filters[] = array('date', (int)$period_start, '<');
        }

        $result = Fima::getResults(array('type'), $filters);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        $assetresult = 0;
        foreach ($result as $rowId => $row) {
            foreach ($row as $colId => $value) {
                $assetresult += $value;
            }
        }
        
        foreach ($data as $rowId => $row) {
            if (preg_match('/__header.*__/', $rowId)) {
                continue;
            }
            if (!preg_match('/__result.*__/', $rowId)) {
                $assetresult += $data[$rowId]['__resulttotal__'];
            }
            $data[$rowId]['__resultasset__'] = $assetresult;
        }

        /* Null Rows and Cumulate. */
        if (!$nullrows || $cumulate) {
            $cumulatevalue = $coldummy;
            foreach ($data as $rowId => $row) {
                if (preg_match('/__(header|result).*__/', $rowId)) {
                    continue;
                }
                $isnullrow = true;
                foreach ($row as $colId => $value) {
                    if (preg_match('/__(header).*__/', $colId) || $colId == '__resultasset__') {
                        continue;
                    }
                    if ($cumulate) {
                        $data[$rowId][$colId] += $cumulatevalue[$colId];
                        $cumulatevalue[$colId] = $data[$rowId][$colId];
                    }
                    if ($data[$rowId][$colId] != 0) {
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
        if ($graph) {
            $sortby = '__header__';
            $sortdir = FIMA_SORT_ASCEND;
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
                foreach ($row as $colId => $value) {
                    if (preg_match('/__(header|result).*__/', $colId) && $colId != '__resulttotal__') {
                        unset($data[$rowId][$colId]);
                    }
                }
            }
        }
        $this->data = $data;

        /* Additional params. */
        $this->setParam('graph', 'Line');
        $this->setParam('labels', $labels);
        $this->setParam('subtitle', $postingtypes[$display]);
        
        return true;
    }

}

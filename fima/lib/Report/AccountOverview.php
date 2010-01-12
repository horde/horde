<?php
/**
 * Fima_Report_AccountOverview.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Fima_Report */
require_once FIMA_BASE . '/lib/Report.php';

/*
 * Fima_Report_AccountOverview class.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_Report_AccountOverview extends Fima_Report {

    /*
     * Constructs a new AccountOverview Report.
     */
    function Fima_Report_AccountOverview($params = array())
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
        /* Get posting types. */
        $postingtypes = Fima::getPostingTypes();

        /* Params. */
        if (($display = $this->getParam('display')) === null) {
            return PEAR::raiseError(_("No display type"));
        }
        $posting_account = $this->getParam('posting_account');
        $period_start = $this->getParam('period_start');
        $period_end   = $this->getParam('period_end');
        $reference_start = $this->getParam('reference_start');
        $reference_end   = $this->getParam('reference_end');
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
        $cols = explode('_', $display);

        $displaypostingtypes = array();
        $displayreference = false;
        $displaydiffa = false;
        $displaydiffp = false;

        $colheaders = array('__header__' => _("Period"));
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
        foreach ($rows as $rowId => $rowLabel) {
            $data[$rowId] = array('__header__' => $rowLabel) + $coldummy;
        }

        /* Results. */
        $total = array('__header__' => _("Total Result")) + $coldummy;
        $filters = array();
        if ($posting_account) {
            $filters[] = array('account', $posting_account);
        }
        $filters[] = array('account_type', FIMA_ACCOUNTTYPE_ASSET, '<>');
        $filters[] = array('type', $cols);
        if ($period_start !== null) {
            $filters[] = array('date', (int)$period_start, '>=');
        }
        if ($period_end !== null) {
            $filters[] = array('date', (int)$period_end, '<=');
        }
        $result = Fima::getResults(array('type', $yearly ? 'date_year' : 'date_month'), $filters);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        foreach ($result as $rowId => $row) {
            foreach ($row as $colId => $value) {
                $data[$rowId][$colId] = $value;
                $total[$colId] += $value;
            }
        }
        $data['__result__'] = $total;

        /* Reference. */
        if ($displayreference) {
            $filters = array();
            if ($posting_account) {
                $filters[] = array('account', $posting_account);
            }
            $filters[] = array('account_type', $rows);
            $filters[] = array('type', $displaypostingtypes[0]);
            if ($reference_start !== null) {
                $filters[] = array('date', (int)$reference_start, '>=');
            }
            if ($reference_end !== null) {
                $filters[] = array('date', (int)$reference_end, '<=');
            }

            $result = Fima::getResults(array('type', $yearly ? 'date_year' : 'date_month'), $filters);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            foreach ($result as $rowId => $row) {
                foreach ($row as $colId => $value) {
                    $colId = 'reference';
                    $data[$rowId][$colId] = $value;
                    $data['__result__'][$colId] += $value;
                }
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

        return true;
    }

}

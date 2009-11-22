<?php
/**
 * Fima_Report_GeneralOverview.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Fima_Report */
require_once FIMA_BASE . '/lib/Report.php';

/*
 * Fima_Report_GeneralOverview class.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_Report_GeneralOverview extends Fima_Report {

    /*
     * Constructs a new GeneralOverview Report.
     */
    function Fima_Report_GeneralOverview($params = array())
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
        /* Get account types and posting types. */
        $accounttypes = Fima::getAccountTypes();
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

        /* Rows. */
        $rows = array(FIMA_ACCOUNTTYPE_INCOME, FIMA_ACCOUNTTYPE_EXPENSE);
        $groups = array('0' => "%s", '1' => "e.o. %s", 'total' => "Total %s");
        $rowheaders = array();
        foreach ($groups as $groupId => $group) {
            foreach ($rows as $rowPos => $rowId) {
                $rowheaders[$rowId . $groupId] = sprintf(_($group), $accounttypes[$rowId]);
            }
            $rowheaders['__result' . $groupId . '__'] = sprintf(_($group), _("Result"));
        }
        $rowheaders['__resultasset__'] = _("Asset Result");

        /* Columns. */
        $cols = explode('_', $display);

        $displaypostingtypes = array();
        $displayreference = false;
        $displaydiffa = false;
        $displaydiffp = false;

        $colheaders = array('__header__' => _("Type"));
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
        $data['__header__'] = $colheaders;
        foreach ($rowheaders as $rowId => $rowheader) {
            $data[$rowId] = array('__header__' => $rowheader) + $coldummy;;
        }
        $assetresult = array('__header__' => _("Asset Result")) + $coldummy;

        /* Results. */
        $groups = array('0', '1');
        foreach ($groups as $group) {
            $filters = array();
            if ($posting_account) {
                $filters[] = array('account', $posting_account);
            }
            $filters[] = array('account_type', $rows);
            $filters[] = array('type', $displaypostingtypes);
            $filters[] = array('eo', $group);
            if ($period_start !== null) {
                $filters[] = array('date', (int)$period_start, '>=');
            }
            if ($period_end !== null) {
                $filters[] = array('date', (int)$period_end, '<=');
            }

            $result = Fima::getResults(array('type', 'account_type'), $filters);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            foreach ($result as $rowId => $row) {
                foreach ($row as $colId => $value) {
                    $data[$rowId . $group][$colId] = $value;
                    $data['__result' . $group . '__'][$colId] += $value;
                }
            }
        }

        // asset results
        $filters = array();
        $filters[] = array('account_type', FIMA_ACCOUNTTYPE_ASSET, '<>');
        $filters[] = array('type', $displaypostingtypes);
        if ($period_end !== null) {
            $filters[] = array('date', (int)$period_end, '<');
        }

        $result = Fima::getResults('type', $filters);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        foreach ($result as $rowId => $row) {
            foreach ($row as $colId => $value) {
                $data['__resultasset__'][$colId] = $value;
            }
        }

        /* Reference. */
        if ($displayreference) {
            $groups = array('0', '1');
            foreach ($groups as $group) {
                $filters = array();
                if ($posting_account) {
                    $filters[] = array('account', $posting_account);
                }
                $filters[] = array('account_type', $rows);
                $filters[] = array('type', $displaypostingtypes[0]);
                $filters[] = array('eo', $group);
                if ($reference_start !== null) {
                    $filters[] = array('date', (int)$reference_start, '>=');
                }
                if ($reference_end !== null) {
                    $filters[] = array('date', (int)$reference_end, '<=');
                }

                $result = Fima::getResults(array('type', 'account_type'), $filters);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
                foreach ($result as $rowId => $row) {
                    foreach ($row as $colId => $value) {
                        $colId = 'reference';
                        $data[$rowId . $group][$colId] = $value;
                        $data['__result' . $group . '__'][$colId] += $value;
                    }
                }
            }

            // asset results
            $filters = array();
            $filters[] = array('account_type', FIMA_ACCOUNTTYPE_ASSET, '<>');
            $filters[] = array('type', $displaypostingtypes[0]);
            if ($reference_end !== null) {
                $filters[] = array('date', (int)$reference_end, '<');
            }

            $result = Fima::getResults('type', $filters);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            foreach ($result as $rowId => $row) {
                foreach ($row as $colId => $value) {
                    $data['__resultasset__']['reference'] = $value;
                }
            }
        }

        /* Totals. */
        foreach ($cols as $colId) {
            foreach ($rows as $rowId) {
                $data[$rowId . 'total'][$colId] = $data[$rowId . '0'][$colId] + $data[$rowId . '1'][$colId];
            }
            $data['__resulttotal__'][$colId] = $data['__result0__'][$colId] + $data['__result1__'][$colId];
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

        $this->_data = $data;

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
        $labels = array();
        $data = $this->_data;
        foreach ($data as $rowId => $row) {
            if (preg_match('/__header.*__/', $rowId)) {
                foreach ($row as $colId => $value) {
                    if (!preg_match('/__(header|result).*__/', $colId)) {
                        $labels[$colId] = $value;
                    }
                }
            }
            if (preg_match('/__(header|result).*__/', $rowId) && $rowId != '__resulttotal__') {
                unset($data[$rowId]);
            } else {
                $labels[$rowId] = isset($row['__header__']) ? $row['__header__'] : $rowId;
                unset($data[$rowId]['__header__']);
            }
        }
        $this->data = $data;

        /* Additional params. */
        $this->setParam('graph', 'Bar');
        $this->setParam('stacked', 'false');
        $this->setParam('labels', $labels);

        return true;
    }

}

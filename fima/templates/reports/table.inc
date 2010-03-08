<?php
if (!isset($report)) {
return;
}

$data = $report->getData();

$reporturl = $report->getParam('url');
$sortby = $report->getParam('sortby');
$sortdir = $report->getParam('sortdir');
$sorturl = Horde_Util::addParameter($reporturl, 'sortdir', ($sortdir) ? 0 : 1);
$sortImg = ($sortdir) ? 'za.png' : 'az.png';
$sortImgText = ($sortdir) ? '\/' : '/\\';

?>
<br />
<table class="reportTable" cellspacing="0">
<?php
foreach ($data as $rowId => $row) {
    echo '<tr class="' . (preg_match('/__result(.*)?__/', $rowId) ? 'result' : 'item') . '">';
    foreach ($row as $colId => $value) {
        if ($rowId === '__headersort__') {
            $sortText = sprintf(_("Sort by %s"), $value);
            echo '<th class="' . (($sortby == $colId) ? 'selected' : 'item') . ' sortable" onclick="document.location.href=' . addslashes(Horde_Util::addParameter(($sortby == $colId) ? $sorturl : $reporturl, 'sortby',  $colId)) . '">';
            if ($sortby == $colId) {
                echo Horde::link(Horde_Util::addParameter($sorturl, 'sortby', $colId), $sortText, null, null, null, $sortText) . Horde::img($sortImg, $sortImgText) . '</a>';
            }
            echo Horde::widget(Horde_Util::addParameter(($sortby == $colId) ? $sorturl : $reporturl, 'sortby', $colId), $sortText, 'widget', null, null, $value);
            echo '</th>';
        } elseif ($rowId === '__header__') {
            echo '<th class="item">' . htmlspecialchars($value) . '</th>';
        } elseif ($colId === '__header__') {
            echo '<td>' . ($value{0} === ' ' ? '&nbsp; &nbsp; ' : '') . htmlspecialchars($value) . '</td>';
        } elseif ($value === null) {
            echo '<td></td>';
        } else {
            echo '<td class="' . (($value < 0) ? 'negative' : 'positive') . ' amount">' . Fima::convertValueToAmount($value) . '</td>';
        }
    }
    echo "</tr>\n";
}
?>
</table>


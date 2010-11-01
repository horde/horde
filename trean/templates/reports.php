<?php
$total = 0;
$counts = array();
$report = $trean_shares->groupBookmarks('status');
foreach ($report as $row) {
    $status = $row['status'];
    $count = (int)$row['count'];

    $s = substr($status, 0, 1);
    if (in_array($s, array(1, 2, 3, 4, 5))) {
        $s .= 'xx';
        if (!isset($counts[$s])) {
            $counts[$s] = $count;
        } else {
            $counts[$s] += $count;
        }
    }

    if ($status == '') {
        if (!isset($counts['unknown'])) {
            $counts['unknown'] = $count;
        } else {
            $counts['unknown'] += $count;
        }
    } else {
        $counts[$status] = $count;
    }

    $total += $count;
}
?>

<h1 class="header"><?php echo _("HTTP Status") ?></h1>

<?php if (isset($counts['1xx'])): ?>
<div class="reportheader"><?php echo Horde::img('http/1xx.png') . ' ' . Horde::link(Horde_Util::addParameter(Horde::selfUrl(true), 'drilldown', '1xx')) . sprintf(_("1xx Response Codes (%s)"), $counts['1xx']) ?></a></div>
<?php if (isset($counts['100'])) echo '<div class="report">' . $counts['100'] . ' - 100 ' . Trean::HTTPStatus('100') . '</div>' ?>
<?php if (isset($counts['101'])) echo '<div class="report">' . $counts['101'] . ' - 101 ' . Trean::HTTPStatus('101') . '</div>' ?>
<?php endif; ?>

<?php if (isset($counts['2xx'])): ?>
<div class="reportheader"><?php echo Horde::img('http/2xx.png') . ' ' . Horde::link(Horde_Util::addParameter(Horde::selfUrl(true), 'drilldown', '2xx')) . sprintf(_("2xx Response Codes (%s)"), $counts['2xx']) ?></a></div>
<?php if (isset($counts['200'])) echo '<div class="report">' . $counts['200'] . ' - 200 ' . Trean::HTTPStatus('200') . '</div>' ?>
<?php if (isset($counts['201'])) echo '<div class="report">' . $counts['201'] . ' - 201 ' . Trean::HTTPStatus('201') . '</div>' ?>
<?php if (isset($counts['202'])) echo '<div class="report">' . $counts['202'] . ' - 202 ' . Trean::HTTPStatus('202') . '</div>' ?>
<?php if (isset($counts['203'])) echo '<div class="report">' . $counts['203'] . ' - 203 ' . Trean::HTTPStatus('203') . '</div>' ?>
<?php if (isset($counts['204'])) echo '<div class="report">' . $counts['204'] . ' - 204 ' . Trean::HTTPStatus('204') . '</div>' ?>
<?php if (isset($counts['205'])) echo '<div class="report">' . $counts['205'] . ' - 205 ' . Trean::HTTPStatus('205') . '</div>' ?>
<?php if (isset($counts['206'])) echo '<div class="report">' . $counts['206'] . ' - 206 ' . Trean::HTTPStatus('206') . '</div>' ?>
<?php endif; ?>

<?php if (isset($counts['3xx'])): ?>
<div class="reportheader"><?php echo Horde::img('http/3xx.png') . ' ' . Horde::link(Horde_Util::addParameter(Horde::selfUrl(true), 'drilldown', '3xx')) . sprintf(_("3xx Response Codes (%s)"), $counts['3xx']) ?></a></div>
<?php if (isset($counts['300'])) echo '<div class="report">' . $counts['300'] . ' - 300 ' . Trean::HTTPStatus('300') . '</div>' ?>
<?php if (isset($counts['301'])) echo '<div class="report">' . $counts['301'] . ' - 301 ' . Trean::HTTPStatus('301') . '</div>' ?>
<?php if (isset($counts['302'])) echo '<div class="report">' . $counts['302'] . ' - 302 ' . Trean::HTTPStatus('302') . '</div>' ?>
<?php if (isset($counts['303'])) echo '<div class="report">' . $counts['303'] . ' - 303 ' . Trean::HTTPStatus('303') . '</div>' ?>
<?php if (isset($counts['304'])) echo '<div class="report">' . $counts['304'] . ' - 304 ' . Trean::HTTPStatus('304') . '</div>' ?>
<?php if (isset($counts['305'])) echo '<div class="report">' . $counts['305'] . ' - 305 ' . Trean::HTTPStatus('305') . '</div>' ?>
<?php if (isset($counts['307'])) echo '<div class="report">' . $counts['307'] . ' - 307 ' . Trean::HTTPStatus('307') . '</div>' ?>
<?php endif; ?>

<?php if (isset($counts['4xx'])): ?>
<div class="reportheader"><?php echo Horde::img('http/4xx.png') . ' ' . Horde::link(Horde_Util::addParameter(Horde::selfUrl(true), 'drilldown', '4xx')) . sprintf(_("4xx Response Codes (%s)"), $counts['4xx']) ?></a></div>
<?php if (isset($counts['400'])) echo '<div class="report">' . $counts['400'] . ' - 400 ' . Trean::HTTPStatus('400') . '</div>' ?>
<?php if (isset($counts['401'])) echo '<div class="report">' . $counts['401'] . ' - 401 ' . Trean::HTTPStatus('401') . '</div>' ?>
<?php if (isset($counts['402'])) echo '<div class="report">' . $counts['402'] . ' - 402 ' . Trean::HTTPStatus('402') . '</div>' ?>
<?php if (isset($counts['403'])) echo '<div class="report">' . $counts['403'] . ' - 403 ' . Trean::HTTPStatus('403') . '</div>' ?>
<?php if (isset($counts['404'])) echo '<div class="report">' . $counts['404'] . ' - 404 ' . Trean::HTTPStatus('404') . '</div>' ?>
<?php if (isset($counts['405'])) echo '<div class="report">' . $counts['405'] . ' - 405 ' . Trean::HTTPStatus('405') . '</div>' ?>
<?php if (isset($counts['406'])) echo '<div class="report">' . $counts['406'] . ' - 406 ' . Trean::HTTPStatus('406') . '</div>' ?>
<?php if (isset($counts['407'])) echo '<div class="report">' . $counts['407'] . ' - 407 ' . Trean::HTTPStatus('407') . '</div>' ?>
<?php if (isset($counts['408'])) echo '<div class="report">' . $counts['408'] . ' - 408 ' . Trean::HTTPStatus('408') . '</div>' ?>
<?php if (isset($counts['409'])) echo '<div class="report">' . $counts['409'] . ' - 409 ' . Trean::HTTPStatus('409') . '</div>' ?>
<?php if (isset($counts['410'])) echo '<div class="report">' . $counts['410'] . ' - 410 ' . Trean::HTTPStatus('410') . '</div>' ?>
<?php if (isset($counts['411'])) echo '<div class="report">' . $counts['411'] . ' - 411 ' . Trean::HTTPStatus('411') . '</div>' ?>
<?php if (isset($counts['412'])) echo '<div class="report">' . $counts['412'] . ' - 412 ' . Trean::HTTPStatus('412') . '</div>' ?>
<?php if (isset($counts['413'])) echo '<div class="report">' . $counts['413'] . ' - 413 ' . Trean::HTTPStatus('413') . '</div>' ?>
<?php if (isset($counts['414'])) echo '<div class="report">' . $counts['414'] . ' - 414 ' . Trean::HTTPStatus('414') . '</div>' ?>
<?php if (isset($counts['415'])) echo '<div class="report">' . $counts['415'] . ' - 415 ' . Trean::HTTPStatus('415') . '</div>' ?>
<?php if (isset($counts['416'])) echo '<div class="report">' . $counts['416'] . ' - 416 ' . Trean::HTTPStatus('416') . '</div>' ?>
<?php if (isset($counts['417'])) echo '<div class="report">' . $counts['417'] . ' - 417 ' . Trean::HTTPStatus('417') . '</div>' ?>
<?php endif; ?>

<?php if (isset($counts['5xx'])): ?>
<div class="reportheader"><?php echo Horde::img('http/5xx.png') . ' ' . Horde::link(Horde_Util::addParameter(Horde::selfUrl(true), 'drilldown', '5xx')) . sprintf(_("5xx Response Codes (%s)"), $counts['5xx']) ?></a></div>
<?php if (isset($counts['500'])) echo '<div class="report">' . $counts['500'] . ' - 500 ' . Trean::HTTPStatus('500') . '</div>' ?>
<?php if (isset($counts['501'])) echo '<div class="report">' . $counts['501'] . ' - 501 ' . Trean::HTTPStatus('501') . '</div>' ?>
<?php if (isset($counts['502'])) echo '<div class="report">' . $counts['502'] . ' - 502 ' . Trean::HTTPStatus('502') . '</div>' ?>
<?php if (isset($counts['503'])) echo '<div class="report">' . $counts['503'] . ' - 503 ' . Trean::HTTPStatus('503') . '</div>' ?>
<?php if (isset($counts['504'])) echo '<div class="report">' . $counts['504'] . ' - 504 ' . Trean::HTTPStatus('504') . '</div>' ?>
<?php if (isset($counts['505'])) echo '<div class="report">' . $counts['505'] . ' - 505 ' . Trean::HTTPStatus('505') . '</div>' ?>
<?php endif; ?>

<?php if (isset($counts['error'])): ?>
<div class="reportheader"><?php echo Horde::img('http/error.png') . ' ' . Horde::link(Horde_Util::addParameter(Horde::selfUrl(true), 'drilldown', 'error')) . sprintf(_("DNS Failure or Other Error (%s)"), $counts['error']) ?></a></div>
<?php endif; ?>

<?php if (isset($counts['unknown'])): ?>
<div class="reportheader"><?php printf(_("Unknown (%s)"), $counts['unknown']) ?></div>
<?php endif; ?>

<div class="reportheader"> <?php echo _("Total") ?></div>
<div class="report"><?php printf(_("%s Bookmarks"), $total) ?></div>

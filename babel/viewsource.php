<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Babel
 */

require_once dirname(__FILE__) . '/lib/base.php';

$app   = Horde_Util::getFormData('module');
$sfile = Horde_Util::getFormData('file');
$sline = Horde_Util::getFormData('line');

if ($app == 'horde') {
    $srcfile = realpath(sprintf("%s/%s", HORDE_BASE, $sfile));
} else {
    $srcfile = realpath(sprintf("%s/%s/%s", HORDE_BASE, $app, $sfile));
}

if (empty($srcfile)) {
    throw new Horde_Exception(_("Missing filename!"));
}

$rpath = realpath(HORDE_BASE);
if (!preg_match(";$rpath;", $srcfile)) {
    throw new Horde_Exception(sprintf(_("Access denied to %s"), $srcfile));
}

// Get File content
$src = file_get_contents($srcfile);

/* Render the page. */
require BABEL_TEMPLATES . '/common-header.inc';

Translate_Display::header(sprintf(_("View source: %s"), str_replace(realpath(HORDE_BASE) . '/', '', $srcfile)));

printCode($src, $sline, 10);

require $registry->get('templates', 'horde') . '/common-footer.inc';

function printCode($code, $sline = 1, $sdiff = 10) {
    if (!is_array($code)) $code = explode("\n", $code);

    $count_lines = count($code);
    $r = '';

    $from = $sline - $sdiff;
    $to   = $sline + $sdiff;

    foreach ($code as $line => $code_line) {

	if ($from && $line < $from) {
	    continue;
	}

	if ($to && $line > $to) {
	    break;
	}

	$r1 = ($line + 1);

	if (ereg("<\?(php)?[^[:graph:]]", $code_line)) {
	    $r2 = highlight_string($code_line, 1)."<br />";
	} else {
	    $r2 = ereg_replace("(&lt;\?php&nbsp;)+", "", highlight_string("<?php ".$code_line, 1))."<br />";
	}

	if ($r1 == $sline) {
	    $r .= sprintf('<tr><td align="right" class="control"><b>%s&nbsp;</b></td><td class="item0">%s</td></tr>', $r1, $r2);
	} else {
	    $r .= sprintf('<tr><td align="right" class="control">%s&nbsp;</td><td>%s</td></tr>', $r1, $r2);
	}
    }

    $r = '<table width="100%" cellspacing=0>' . $r . '</table>';

    echo "<div class=\"code\">".$r."</div>";
}

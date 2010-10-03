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

class Translate_Display {

    function header($msg, $msg2 = '') {
	global $cnt_i, $registry;
	$select_img = Horde::img('alerts/message.png');
	print sprintf('<table cellspacing=0 cellpadding=0 width=100%%><tr><td class="header">%s</td><td align="right" class="header">%s</td><td  class="header" width="20">%s</td></tr></table>', $msg, $msg2, $select_img);
	flush();
    }

    function warning($msg, $bold = true) {
	global $cnt_i, $registry;
	$item = ($cnt_i++ % 2);
	$select_img = Horde::img('alerts/warning.png');
	if ($bold) {
	    print sprintf('<table cellspacing=0 cellpadding=0 width=100%%><tr><td class="item%d" style="color: #ff0000">%s</td><td  class="item%1$d" width="20">%s</td></tr></table>', $item, $msg, $select_img);
	} else {
	    print sprintf('<table cellspacing=0 cellpadding=0 width=100%%><tr><td class="item%d small" style="color: #ff0000">%s</td><td  class="item%1$d" width="20">%s</td></tr></table>', $item, $msg, $select_img);
	}
	flush();
    }

    function error($msg) {
	global $cnt_i, $registry;
	$item = ($cnt_i++ % 2);
	$select_img = Horde::img('alerts/error.png');
	print sprintf('<table cellspacing=0 cellpadding=0 width=100%%><tr><td class="item%d" style="color: #ff0000"><b>%s</b></td><td  class="item%1$d" width="20">%s</td></tr></table>', $item, $msg, $select_img);
	flush();
    }

    function info($msg = "", $bold = true) {

	global $cnt_i, $registry;

	if (empty($msg)) {
	    echo "<br />";
	} else {

	    $item = ($cnt_i++ % 2);

	$select_img = Horde::img('alerts/select.png');
	    if ($bold) {
		print sprintf('<table cellspacing=0 cellpadding=0 width=100%%><tr><td class="item%d"><b>%s</b></td><td  class="item%1$d" width="20">%s</td></tr></table>', $item, $msg, $select_img);
	    } else {
		print sprintf('<table cellspacing=0 cellpadding=0 width=100%%><tr><td class="item%d small">%s</td><td  class="item%1$d" width="20">%s</td></tr></table>', $item, $msg, '');
	    }
	    flush();
	}
    }

    function parseCharset($headers)
    {
        if (preg_match('/charset=(.*)/i', $headers, $m)) {
            return $m[1];
        }
	return 'UTF-8';
    }

    function convert_string($msg) {
	global $po;

	$f = array('/&lt;/', '/&gt;/');
	$t = array('<', '>');
	$msg = preg_replace($f, $t, $msg);
	return Horde_String::convertCharset(html_entity_decode($msg), 'UTF-8', Translate_Display::parseCharset($po->meta['Content-Type']));
    }

    function display_string($msg) {
	global $po;

	$f = array('/</', '/>/');
	$t = array('&lt;', '&gt;');
	$msg = preg_replace($f, $t, $msg);
	return Horde_String::convertCharset($msg, Translate_Display::parseCharset($po->meta['Content-Type']), 'UTF-8');
    }

    function get_percent($used, $total) {
	if ($total > 0) {
	    $percent = sprintf("%2.2f", (($used * 100) / $total));
	} else {
	    $percent = 0;
	}

	return $percent;
    }

    function create_bargraph ($used, $total, $text = true, $reverse = false, $small = false) {
	if ($total > 0) {
	    $percent = round(($used * 100) / $total);
	} else {
	    $percent = 0;
	}

	$html = '<table border="0" cellpadding="0" cellspacing="0"><tr><td nowrap="nowrap">';
	$html .= '<table border="0" width="100" cellpadding="0" cellspacing="0">';
	$html .= '<tr height="10">';

	if ($percent > 0) {
	    $html .= '<td nowrap="nowrap" width="' . ($percent) . '" bgcolor="#00FF00"></td>';
	}

	if ($percent != 100) {
	    $html .= '<td nowrap="nowrap" width="' . (100 - $percent) . '" ';
	    if ($reverse) {
		$html .= ' bgcolor="#FFFFFF"></td>';
	    } else {
		$html .= ' bgcolor="#006699"></td>';
	    }
	}

	$html .= '</tr></table></td>';

	if ($text) {
	    if ($small) {
		$html .= '<td class="small"> ' . $percent .'% </td>';
	    } else {
		$html .= '<td> ' . $percent .'% </td>';
	    }
	}

	$html .= '</tr></table>';

	return $html;
    }

}

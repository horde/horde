<?php
/**
 * Wrapper for CVSGraph.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('chora');

// Exit if cvsgraph isn't active or it's not supported.
if (empty($conf['paths']['cvsgraph']) || !$VC->hasFeature('branches')) {
    Chora::url('browsefile', $where)->redirect();
}

if (!is_file($fullname . ',v')) {
    Chora::fatal(sprintf(_("%s: no such file or directory"), $where), '404 Not Found');
}

$root = escapeShellCmd($VC->sourceroot());
$file = escapeShellCmd($where . ',v');

if (Horde_Util::getFormData('show_image')) {
    // Pipe out the actual image.
    $args = array('c' => $conf['paths']['cvsgraph_conf'],
                  'r' => $root);

    // Build up the argument string.
    $argstr = '';
    if (!strncasecmp(PHP_OS, 'WIN', 3)) {
        foreach ($args as $key => $val) {
            $argstr .= "-$key \"$val\" ";
        }
    } else {
        foreach ($args as $key => $val) {
            $argstr .= "-$key '$val' ";
        }
    }

    header('Content-Type: image/png');
    passthru($conf['paths']['cvsgraph'] . ' ' . $argstr . ' ' . $file);
    exit;
}

// Display the wrapper page for the image.
$title = sprintf(_("Graph for %s"), $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($where, 'space2html', array('encode' => true, 'encode_all' => true)));
$extraLink = Chora::getFileViews($where, 'cvsgraph');

require $registry->get('templates', 'horde') . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';

$imgUrl = Chora::url('cvsgraph', $where, array('show_image' => 1));

$args = array('c' => $conf['paths']['cvsgraph_conf'],
              'M' => 'graphMap',
              'r' => $root,
              '0' => '&amp;',
              '1' => Chora::url('browsefile', $where, array('dummy' => 'true')),
              '2' => Chora::url('diff', $where, array('dummy' =>'true')),
              '3' => Chora::url('co', $where, array('dummy' => 'true')),
);

// Build up the argument string.
$argstr = '';
if (!strncasecmp(PHP_OS, 'WIN', 3)) {
    foreach ($args as $key => $val) {
        $argstr .= "-$key \"$val\" ";
    }
} else {
    foreach ($args as $key => $val) {
        $argstr .= "-$key '$val' ";
    }
}

// Generate the imagemap.
$map = shell_exec($conf['paths']['cvsgraph'] . ' ' . $argstr . ' -i ' . $file);

require CHORA_TEMPLATES . '/cvsgraph/cvsgraph.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';

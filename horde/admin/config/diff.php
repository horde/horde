<?php
/**
 * Script to show the differences between the currently saved and the newly
 * generated configuration.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

/* Set up the diff renderer. */
$render_type = Horde_Util::getFormData('render', 'inline');
$class = 'Text_Diff_Renderer_' . $render_type;
$renderer = new $class();

/**
 * Private function to render the differences for a specific app.
 */
function _getDiff($app)
{
    global $registry, $renderer, $session;

    /* Read the existing configuration. */
    $current_config = '';
    $path = $registry->get('fileroot', $app) . '/config';
    $current_config = @file_get_contents($path . '/conf.php');

    /* Calculate the differences. */
    $diff = new Text_Diff(explode("\n", $current_config),
                          explode("\n", $session->get('horde', 'config/' . $app)));
    $diff = $renderer->render($diff);

    return empty($diff)
        ? _("No change.")
        : $diff;
}

$diffs = array();
/* Only bother to do anything if there is any config. */
if ($config = $session->get('horde', 'config/')) {
    /* Set up the toggle button for inline/unified. */
    $url = Horde::url('admin/config/diff.php')->add('render', ($render_type == 'inline') ? 'unified' : 'inline');

    if ($app = Horde_Util::getFormData('app')) {
        /* Handle a single app request. */
        $toggle_renderer = Horde::link($url . '#' . $app) . (($render_type == 'inline') ? _("unified") : _("inline")) . '</a>';
        $diff = _getDiff($app);
        if ($render_type != 'inline') {
            $diff = htmlspecialchars($diff);
        }
        $diffs[] = array('app'  => $app,
                         'diff' => $diff,
                         'toggle_renderer' => $toggle_renderer);
    } else {
        /* List all the apps with generated configuration. */
        ksort($config);
        foreach ($config as $app => $config) {
            $toggle_renderer = Horde::link($url . '#' . $app) . (($render_type == 'inline') ? _("unified") : _("inline")) . '</a>';
            $diff = _getDiff($app);
            if ($render_type != 'inline') {
                $diff = htmlspecialchars($diff);
            }
            $diffs[] = array('app'  => $app,
                             'diff' => $diff,
                             'toggle_renderer' => $toggle_renderer);
        }
    }
}

/* Set up the template. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set('diffs', $diffs, true);

$title = _("Configuration Differences");
require HORDE_TEMPLATES . '/common-header.inc';
echo $template->fetch(HORDE_TEMPLATES . '/admin/config/diff.html');
require HORDE_TEMPLATES . '/common-footer.inc';

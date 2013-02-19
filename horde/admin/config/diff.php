<?php
/**
 * Script to show the differences between the currently saved and the newly
 * generated configuration.
 *
 * Copyright 2004-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:configuration')
));

$vars = $injector->getInstance('Horde_Variables');

/* Set up the diff renderer. */
$render_type = $vars->get('render', 'inline');
$class = 'Horde_Text_Diff_Renderer_' . Horde_String::ucfirst($render_type);
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
    $diff = new Horde_Text_Diff(
        'auto',
        array(explode("\n", $current_config),
        explode("\n", $session->get('horde', 'config/' . $app)))
    );
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

    if ($app = $vars->app) {
        /* Handle a single app request. */
        $toggle_renderer = Horde::link($url . '#' . $app) . (($render_type == 'inline') ? _("unified") : _("inline")) . '</a>';
        $diffs[] = array(
            'app'  => $app,
            'diff' => ($render_type == 'inline') ? _getDiff($app) : htmlspecialchars(_getDiff($app)),
            'toggle_renderer' => $toggle_renderer
        );
    } else {
        /* List all the apps with generated configuration. */
        ksort($config);
        foreach ($config as $app => $config) {
            $toggle_renderer = Horde::link($url . '#' . $app) . (($render_type == 'inline') ? _("unified") : _("inline")) . '</a>';
            $diffs[] = array(
                'app'  => $app,
                'diff' => ($render_type == 'inline') ? _getDiff($app) : htmlspecialchars(_getDiff($app)),
                'toggle_renderer' => $toggle_renderer
            );
        }
    }
}

/* Set up the template. */
$view = new Horde_View(array(
    'templatePath' => HORDE_TEMPLATES . '/admin/config'
));
$view->diffs = $diffs;

$page_output->topbar = $page_output->sidebar = false;

$page_output->header(array(
    'title' => _("Configuration Differences")
));
echo $view->render('diff');
$page_output->footer();

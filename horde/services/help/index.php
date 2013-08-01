<?php
/**
 * Help display script.
 *
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$vars = $injector->getInstance('Horde_Variables');

$rtl = $registry->nlsconfig->curr_rtl;
$show = isset($vars->show)
    ? Horde_String::lower($vars->show)
    : 'index';
$module = isset($vars->module)
    ? Horde_String::lower(preg_replace('/\W/', '', $vars->module))
    : 'horde';
$topic = $vars->get('topic', 'overview');

$base_url = $registry->getServiceLink('help', $module);

$sidebar_url = Horde::url($base_url->copy()->add(array(
    'show' => 'sidebar',
    'topic' => $topic
)));

if ($show == 'index') {
    $main_url = Horde::url($base_url->copy()->add(array(
        'show' => 'entry',
        'topic' => $topic
    )));
    $menu_url = Horde::url($base_url->copy()->add('show', 'menu'));

    require HORDE_TEMPLATES . '/help/index.inc';
    exit;
}

$help_app = $registry->get('name', ($module == 'admin') ? 'horde' : $module);
$fileroot = ($module == 'admin')
    ? $registry->get('fileroot') . '/admin'
    : $registry->get('fileroot', $module);
$fileroots = array(
    $fileroot . '/locale/' . $language . '/',
    $fileroot . '/locale/' . substr($language, 0, 2) . '/',
    $fileroot . '/locale/en/'
);
foreach ($fileroots as $val) {
    $fname = $val . 'help.xml';
    if (@is_file($fname)) {
        break;
    }
}

$views = array();
switch ($registry->getView()) {
case $registry::VIEW_BASIC:
    $views[] = 'basic';
    break;

case $registry::VIEW_DYNAMIC:
    $views[] = 'dynamic';
    break;
}

$help = new Horde_Help(Horde_Help::SOURCE_FILE, $fname, $views);

$page_output->sidebar = $page_output->topbar = false;
$page_output->header(array(
    'body_class' => 'help help_' . urlencode($show),
    'title' => _("Help")
));

switch ($show) {
case 'menu':
    $version = Horde_String::ucfirst($module) . ' ' . $registry->getVersion($module);
    require HORDE_TEMPLATES . '/help/menu.inc';
    break;

case 'sidebar':
    /* Generate Tabs */
    if (!isset($vars->side_show)) {
        $vars->side_show = 'index';
    }
    $tabs = new Horde_Core_Ui_Tabs('side_show', $vars);
    $tabs->addTab(_("Help _Topics"), $sidebar_url, 'index');
    $tabs->addTab(_("Sea_rch"), $sidebar_url, 'search');

    /* Set up the tree. */
    $tree = $injector->getInstance('Horde_Core_Factory_Tree')->create('horde_help', 'Javascript');
    $tree->setOption(array('target' => 'help_main'));

    $contents = '';
    switch ($vars->get('side_show', 'index')) {
    case 'index':
        $topics = $help->topics();
        $added_nodes = array();
        $node_params_master = array(
            'icon' => '',
            'target' => 'help_main'
        );

        foreach ($topics as $id => $title) {
            if (!$title) {
                continue;
            }

            /* If the title doesn't begin with :: then replace all
             * double colons with single colons. */
            if (substr($title, 0, 2) != '::') {
                $title = str_replace('::', ': ', $title);
            }

            /* Split title in multiple levels */
            $levels = preg_split('/:\s/', $title);

            $idx = '';
            $lcount = count($levels) - 1;
            $node_params = $node_params_master;
            $parent = null;

            foreach ($levels as $key => $name) {
                $idx .= '|' . $name;
                if (empty($added_nodes[$idx])) {
                    if ($key == $lcount) {
                        $node_params['url'] = $base_url->copy()->setRaw(true)->add(array(
                            'show' => 'entry',
                            'topic' => $id
                        ));
                        $added_nodes[$idx] = true;
                    }
                    $tree->addNode(array(
                        'id' => $idx,
                        'parent' => $parent,
                        'label' => $name,
                        'expanded' => false,
                        'params' => $node_params
                    ));
                }
                $parent .= '|' . $name;
            }
        }
        break;

    case 'search':
        /* Create Form */
        $searchForm = new Horde_Form($vars, null, 'search');
        $searchForm->setButtons(_("Search"));

        $searchForm->addHidden('sidebar', 'show', 'text', false);
        $searchForm->addHidden('', 'module', 'text', false);
        $searchForm->addHidden('', 'side_show', 'text', false);
        $searchForm->addVariable(_("Keyword"), 'keyword', 'text', false, false, null, array(null, 20));

        $renderer = new Horde_Form_Renderer();
        $renderer->setAttrColumnWidth('50%');

        Horde::startBuffer();
        $searchForm->renderActive($renderer, $vars, $sidebar_url->copy()->setRaw(true), 'post');
        $contents = Horde::endBuffer() . '<br />';

        $keywords = $vars->get('keyword');
        if (!empty($keywords)) {
            $results = $help->search($keywords);
            foreach ($results as $id => $title) {
                if (empty($title)) {
                    continue;
                }
                $contents .= Horde::link($base_url->copy()->add(array('show' => 'entry', 'topic' => $id)), null, null, 'help_main') .
                    htmlspecialchars($title) . "</a><br />\n";
            }
        }
        break;
    }

    require HORDE_TEMPLATES . '/help/sidebar.inc';
    break;

case 'entry':
    if ($topic) {
        echo $help->lookup($topic);
    }
    break;
}

$page_output->footer();

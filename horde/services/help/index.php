<?php
/**
 * Help display script.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$vars = Horde_Variables::getDefaultVariables();

$rtl = $registry->nlsconfig->curr_rtl;
$title = _("Help");
$show = isset($vars->show)
    ? Horde_String::lower($vars->show)
    : 'index';
$module = isset($vars->module)
    ? Horde_String::lower(preg_replace('/\W/', '', $vars->module))
    : 'horde';
$topic = isset($vars->topic) ? $vars->topic : 'overview';

$base_url = Horde::getServiceLink('help', $module);

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

if ($module == 'admin') {
    $help_app = $registry->get('name', 'horde');
    $fileroot = $registry->get('fileroot') . '/admin';
} else {
    $help_app = $registry->get('name', $module);
    $fileroot = $registry->get('fileroot', $module);
}

$help = new Horde_Help(Horde_Help::SOURCE_FILE, array($fileroot . '/locale/' . $language . '/help.xml', $fileroot . '/locale/' . substr($language, 0, 2) . '/help.xml', $fileroot . '/locale/en/help.xml'));

$bodyClass = 'help help_' . urlencode($show);
require HORDE_TEMPLATES . '/common-header.inc';

switch ($show) {
case 'menu':
    $version = Horde_String::ucfirst($module) . ' ' . $registry->getVersion($module);
    require HORDE_TEMPLATES . '/help/menu.inc';
    break;

case 'sidebar':
    if (!isset($vars->side_show)) {
        $vars->set('side_show', 'index');
    }

    /* Generate Tabs */
    $tabs = new Horde_Core_Ui_Tabs('side_show', $vars);
    $tabs->addTab(_("Help _Topics"), $sidebar_url, 'index');
    $tabs->addTab(_("Sea_rch"), $sidebar_url, 'search');

    /* Set up the tree. */
    $tree = $injector->getInstance('Horde_Core_Factory_Tree')->create('horde_menu', 'Javascript');
    $tree->setOption(array('target' => 'help_main'));

    $contents = '';
    switch ($vars->side_show) {
    case 'index':
        $topics = $help->topics();
        $added_nodes = array();
        $node_params_master = array(
            'icon' => strval(Horde_Themes::img('help.png')),
            'target' => 'help_main'
        );

        foreach ($topics as $id => $title) {
            if (!$title) {
                continue;
            }

            $node_params = $node_params_master;
            $parent = null;

            /* If the title doesn't begin with :: then replace all
             * double colons with single colons. */
            if (substr($title, 0, 2) != '::') {
                $title = str_replace('::', ': ', $title);
            }

            /* Split title in multiple levels */
            $levels = preg_split('/:\s/', $title);
            if (count($levels) == 1) {
                $levels = array(1 => $title);
            }

            $parent = null;
            $idx = '';

            foreach ($levels as $key => $name) {
                $idx .= '|' . $name;
                if (empty($added_nodes[$idx])) {
                    $added_nodes[$idx] = true;
                    if ($key) {
                        $node_params['url'] = $base_url->copy()->setRaw(true)->add(array(
                            'show' => 'entry',
                            'topic' => $id
                        ));
                    }
                    $tree->addNode($idx, $parent, $name, 0, false, $node_params);
                }
                $parent .= '|' . $name;
            }
        }
        break;

    case 'search':
        /* Create Form */
        $searchForm = new Horde_Form($vars, null, 'search');
        $searchForm->setButtons(_("Search"));

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

require HORDE_TEMPLATES . '/common-footer.inc';

<?php
/**
 * The Agora search page.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jason Felice <jason.m.felice@gmail.com>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('agora');

/* Set up the forums object. */
$scope = Horde_Util::getGet('scope', 'agora');
$messages = $injector->getInstance('Agora_Factory_Driver')->create($scope);
$vars = Horde_Variables::getDefaultVariables();
$form = new Agora_Form_Search($vars, $scope);
$thread_page = Horde_Util::getFormData('thread_page');

$view = new Agora_View();

if ($form->isSubmitted() || $thread_page != null) {

    $form->getInfo($vars, $info);

    if (!empty($info['keywords'])) {
        $info['keywords'] = preg_split('/\s+/', $info['keywords']);
    }

    $sort_by = Agora::getSortBy('thread');
    $sort_dir = Agora::getSortDir('thread');
    $thread_per_page = $prefs->getValue('thread_per_page');
    $thread_start = $thread_page * $thread_per_page;

    $searchResults = $messages->search($info, $sort_by, $sort_dir, $thread_start, $thread_per_page);
    if ($searchResults instanceof PEAR_Error) {
        $notification->push($searchResults->getMessage(), 'horde.error');
        Horde::url('search.php')->redirect();
    }

    if ($searchResults['total'] > count($searchResults['results'])) {
        $pager_ob = new Horde_Core_Ui_Pager('thread_page', $vars, array('num' => $searchResults['total'], 'url' => 'search.php', 'perpage' => $thread_per_page));
        foreach ($info as $key => $val) {
            if ($val) {
                if ($key == 'keywords') {
                    $val = implode(' ', $val);
                }
                $pager_ob->preserve($key, $val);
            }
        }
        $view->pager_link = $pager_ob->render();
    }

    $view->searchTotal = number_format($searchResults['total']);
    $view->searchResults = $searchResults['results'];
}


Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$view->notify = Horde::endBuffer();

Horde::startBuffer();
$form->renderActive(null, $vars, Horde::url('search.php'), 'get');
$view->searchForm = Horde::endBuffer();

$page_output->header(array(
    'title' => _("Search Forums")
));
echo $view->render('search');
$page_output->footer();

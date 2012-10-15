<?php
/**
 * The Hylax script to show a summary view.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
$hylax = Horde_Registry::appInit('hylax');

$folder = strtolower(Horde_Util::getFormData('folder', 'inbox'));
$path = Horde_Util::getFormData('path');
$base_folders = Hylax::getBaseFolders();

/* Get the list of faxes in folder. */
$folder_list = $hylax->storage->listFaxes($folder);

/* Set up URLs which will be used in the list. */
$view_url  = Horde::url('view.php');
$view_img  = Horde::img('view.gif', _("View"), 'align="middle"');

$download_url = $view_url->copy()->add('action', 'download');
$download_img  = Horde::img('download.gif', _("Download"));

$edit_url = Horde::url('edit.php');
$edit_label = ($folder == 'pending') ? _("Edit") : _("Resend");
$edit_img = Horde::img('edit.gif', $edit_label);

$del_url  = Horde::url('delete.php');
$del_img  = Horde::img('delete-small.gif', _("Delete"));

$params = array('folder' => $folder, 'path' => $path);
$warn_img = Horde::img('alerts/warning.gif', _("Warning"));
$send_url  = Horde::url('send.php');

$print_url  = Horde::url('print.php');
$print_img  = Horde::img('print.gif', _("Print"));

/* Loop through list and set up items. */
$i = 0;
foreach ($folder_list as $key => $value) {
    $params['fax_id'] = $value['fax_id'];

    /* View. */
    $url = $view_url->copy()->add($params);
    $folder_list[$key]['actions'][] = Horde::link($url, _("View")) . $view_img . '</a>';

    /* Download. */
    $url = $download_url->copy()->add($params);
    $folder_list[$key]['actions'][] = Horde::link($url, _("Download")) . $download_img . '</a>';

    /* Delete. */
    // $url = $del_url->add($params);
    // $folder_list[$key]['actions'][] = Horde::link($url, _("Delete")) . $del_img . '</a>';

    /* Print. */
    $url = $print_url->copy()->add($params)->add('url', Horde::selfUrl(true));
    $folder_list[$key]['actions'][] = Horde::link($url, _("Print")) . $print_img . '</a>';
    $folder_list[$key]['alt_count'] = $i;
    $i = $i ? 0 : 1;

    /* Format date. */
    $folder_list[$key]['fax_created'] = strftime('%d/%m/%Y %H:%M', $value['fax_created']);

    if (empty($value['fax_number']) && $value['fax_type'] != 0) {
        $url = $send_url->copy()->add('fax_id', $value['fax_id']);
        $folder_list[$key]['fax_number'] = $warn_img . '&nbsp;' . Horde::link($url, _("Insert Number")) . _("Insert Number") . '</a>';
    } elseif (empty($value['fax_number']) && $value['fax_type'] == 0) {
        $folder_list[$key]['fax_number'] = _("unknown");
    }
    $folder_list[$key]['fax_status'] = $hylax->gateway->getStatus($value['job_id']);
}

/* Set up actions. */
$actions = array();
foreach ($base_folders as $key => $value) {
    if ($folder != $key) {
        $url = Horde::url('folder.php')->add('folder', $key);
        $actions[] = Horde::link($url) . $value . '</a>';
    } else {
        $actions[] = $value;
    }
}

/* Set up template. */
$template = $injector->createInstance('Horde_Template');
if ($folder == 'archive') {
    $template->set('folder_name', $path);
} else {
    $template->set('folder_name', $base_folders[$folder]);
}
$template->set('folder', $folder_list, true);
$template->set('actions', $actions);
$template->set('menu', Hylax::getMenu('string'));

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

$page_output->header();
echo $template->fetch(HYLAX_TEMPLATES . '/folder/folder.html');
$page_output->footer();

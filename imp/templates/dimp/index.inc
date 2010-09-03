<?php
/**
 * Dynamic view (dimp) index template.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

$use_sub = $prefs->getValue('subscribe');
$use_trash = $prefs->getValue('use_trash');

$has_blacklist = $registry->hasMethod('mail/blacklistFrom');
$has_whitelist = $registry->hasMethod('mail/whitelistFrom');

// Quota information
$show_quota = (isset($_SESSION['imp']['imap']['quota']) && is_array($_SESSION['imp']['imap']['quota']));

// Get the list of available IMAP flags
$flag_list = $injector->getInstance('IMP_Imap_Flags')->getList(array('imap' => true));

// Get application folders list.
$application_folders = array();
foreach (IMP_Dimp::menuList() as $app) {
    if (($registry->get('status', $app) != 'inactive') &&
        $registry->hasPermission($app, Horde_Perms::SHOW)) {
        $application_folders[] = array(
            'app' => rawurlencode($app),
            'icon' => $registry->get('icon', $app),
            'name' => htmlspecialchars($registry->get('name', $app))
        );
    }
}

function _simpleButton($id, $text, $image, $nodisplay = false)
{
    $ak = Horde::getAccessKey($text, true);
    return '<li class="servicelink"' .
        (strlen($id) ? ' id="' . $id . '"' : '') .
        (strlen($ak) ? ' accesskey="' . $ak . '"' : '') .
        ($nodisplay ? ' style="display:none"' : '') . '>' .
        '<span class="iconSpan ' . $image . '"></span>' .
        '<a>' . Horde::highlightAccessKey($text, $ak) . '</a></li>';
}

?>
<div id="dimpLoading">
 <?php echo _("Loading...") ?>
 <noscript>
  <div class="nojserror"><?php echo _("Error! This application requires javascript to be available and enabled in your browser.") ?></div>
 </noscript>
</div>

<div id="dimpPage" style="display:none">
 <div id="header"></div>
 <div id="pageContainer">
  <div id="sidebar">
   <div id="logo">
    <a id="logolink"><?php echo _("Horde") ?></a>
   </div>
   <ul id="dimpbarActions">
    <?php echo _simpleButton('composelink', _("_New Message"), 'dimpactionCompose') ?>
    <?php echo _simpleButton('checkmaillink', _("_Get Mail"), 'dimpactionCheckmail') ?>
    <?php echo _simpleButton('refreshlink', _("_Refresh Search"), 'dimpactionRefresh', true) ?>
    <?php echo _simpleButton('alertsloglink', _("Alerts _Log"), 'infoIcon') ?>
<?php if (!empty($_SESSION['imp']['filteravail'])): ?>
    <?php echo _simpleButton('applyfilterlink', _("Apply Filters"), 'filtersIcon') ?>
<?php endif; ?>
   </ul>
   <div class="sepfull"></div>
   <ul id="serviceActions">
<?php if (($registry->get('status', 'horde') != 'hidden') && ($registry->get('status', 'horde') != 'notoolbar')): ?>
    <?php echo _simpleButton('appportal', _("_Portal"), 'hordeIcon') ?>
<?php endif; ?>
<?php if (Horde_Menu::showService('options')): ?>
    <?php echo _simpleButton('appoptions', _("_Options"), 'prefsIcon') ?>
<?php endif; ?>
<?php if (Horde_Menu::showService('logout')): ?>
    <?php echo _simpleButton('applogout', _("_Log Out"), 'logoutIcon') ?>
<?php endif; ?>
   </ul>
   <div class="sepfull"></div>
   <div id="foldersLoading"><?php echo _("Loading...") ?></div>
   <div id="foldersSidebar" style="display:none">
    <ul class="folderlist" id="specialfolders"></ul>
<?php if (!empty($application_folders)): ?>
    <div class="sepfull"></div>
    <ul id="applicationfolders">
<?php foreach ($application_folders as $val): ?>
     <li class="custom">
      <img src="<?php echo $val['icon'] ?>" alt="<?php echo $val['name'] ?>" />
      <a title="<?php echo $val['name'] ?>" id="app<?php echo $val['app'] ?>"><?php echo $val['name'] ?></a>
     </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php if (!empty($site_menu)): ?>
    <div id="sitemenu">
     <div class="sepfull"></div>
     <ul>
<?php foreach ($site_menu as $key => $menu_item): ?>
<?php if ($menu_item == 'separator'): ?>
     </ul>
     <div class="sepfull"></div>
     <ul>
<?php else: ?>
      <li class="custom">
       <img src="<?php echo $menu_item['icon'] ?>" alt="<?php echo $menu_item['text'] ?>" />
       <a title="<?php echo $menu_item['text'] ?>" id="menu<?php echo $key ?>"><?php echo $menu_item['text'] ?></a>
      </li>
<?php endif; ?>
<?php endforeach; ?>
     </ul>
    </div>
<?php endif; ?>
<?php if ($_SESSION['imp']['protocol'] != 'pop'): ?>
    <div class="sepfull"></div>
    <ul class="folderlist" id="normalfolders">
     <li class="folder" id="folderopts">
      <span class="iconSpan folderImg"></span>
      <a id="folderopts_link" title="<?php echo _("Folder Options") ?>"><?php echo _("Folder Options") ?></a>
     </li>
     <li class="folder" id="dropbase" style="display:none">
      <span class="iconSpan folderImg"></span>
      <a title="<?php echo _("Move to Base Level") ?>"><?php echo _("Move to Base Level") ?></a>
     </li>
    </ul>
<?php endif; ?>
   </div>
  </div>

  <div id="dimpmain">
   <div id="dimpmain_portal"><?php echo _("Loading...") ?></div>
   <div id="dimpmain_folder" style="display:none">
    <div id="dimpmain_folder_top">
     <div id="tabbar">
<?php if (!empty($application_folders) || !empty($site_menu)): ?>
      <div class="tabset">
       <ul>
        <li>
         <a id="hometab"><img src="<?php echo $registry->get('icon', 'horde') ?>" alt="<?php echo _("Portal") ?>" /><?php echo _("Portal") ?></a>
        </li>
<?php foreach ($application_folders as $val): ?>
        <li<?php if ($val['app'] == 'dimp') echo ' class="activeTab"' ?>>
         <a class="applicationtab" id="apptab<?php echo $val['app'] ?>"><img src="<?php echo $val['icon'] ?>" alt="<?php echo $val['name'] ?>" /><?php echo $val['name'] ?></a>
        </li>
<?php endforeach; ?>
<?php foreach ($site_menu as $key => $menu_item): if ($menu_item == 'separator') continue; ?>
        <li>
         <a id="tab<?php echo $key ?>"><img src="<?php echo $menu_item['icon'] ?>" alt="<?php echo $menu_item['text'] ?>" /><?php echo $menu_item['text'] ?></a>
        </li>
<?php endforeach; ?>
<?php if ($show_quota): ?>
        <li id="quota">
         <span class="used"><?php echo Horde::img('quotauncover.gif') ?></span>
        </li>
<?php endif; ?>
       </ul>
      </div>
<?php elseif ($show_quota): ?>
      <div id="quota">
        <span class="used"><?php echo Horde::img('quotauncover.gif') ?></span>
      </div>
<?php endif; ?>
     </div>
     <div class="header mboxheader">
      <div>
       <span class="rightFloat" id="msgHeader"></span>
       <span id="folderName"></span>
       <span class="iconImg readonlyImg" style="display:none" title="<?php echo htmlspecialchars(_("Read-Only")) ?>"></span>
      </div>
     </div>

     <div class="dimpActions">
<?php if ($_SESSION['imp']['protocol'] != 'pop'): ?>
      <div id="qsearch">
       <span id="qsearch_icon"></span>
       <form action="#" method="post">
        <input autocomplete="off" id="qsearch_input" type="text" />
       </form>
       <span class="closeImg" id="qsearch_close" style="display:none" title="<?php echo _("Clear Search") ?>"></span>
       <span id="qsearch_close_filter" style="display:none">Clear Filter</span>
      </div>
<?php endif; ?>
      <div><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Reply', 'id' => 'button_reply', 'title' => _("Reply"))) ?></div>
      <div><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Forward', 'id' => 'button_forward', 'title' => _("Forward"))) ?></div>
<?php if (!empty($conf['spam']['reporting'])): ?>
      <div><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Spam', 'id' => 'button_spam', 'title' => _("Spam"))) ?></div>
<?php endif; ?>
<?php if (!empty($conf['notspam']['reporting'])): ?>
      <div><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Ham', 'id' => 'button_ham', 'title' => _("Innocent"))) ?></div>
<?php endif; ?>
      <div><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Delete', 'id' => 'button_deleted', 'title' => _("Delete"))) ?></div>
      <div id="button_compose"><?php echo IMP_Dimp::actionButton(array('icon' => 'Compose', 'title' => _("Compose"))) ?></div>
      <div id="button_checkmail"><?php echo IMP_Dimp::actionButton(array('icon' => 'Checkmail', 'title' => _("Get Mail"))) ?></div>
      <div><?php echo IMP_Dimp::actionButton(array('icon' => 'Other', 'id' => 'button_other', 'title' => _("Other Actions"))) ?></div>
     </div>

     <div id="msglistHeader" class="item">
      <div class="msgStatus"></div>
      <div class="msgFrom sep"></div>
      <div class="msgSubject sep"></div>
      <div class="msgDate sep"></div>
      <div class="msgSize sep"></div>
     </div>
    </div>

    <div id="msgSplitPane"></div>
    <span id="viewportLoading" class="loadingImg" style="display:none"></span>

    <div id="previewPane" style="display:none">
     <span id="msgLoading" class="loadingImg" style="display:none"></span>
     <div id="previewInfo" style="display:none">
      <?php echo _("To preview a message, select it from the message list.") ?>
      <br />
<?php if ($browser->isBrowser('opera')): ?>
      <?php echo _("A left click") ?> + <span class="kbd"><?php echo _("Shift") ?></span> + <span class="kbd"><?php echo _("Ctrl") ?></span> <?php echo _("will display available actions.") ?>
<?php else: ?>
      <?php echo _("A right-click on a message or a folder will display available actions.") ?>
<?php endif; ?>
      <br />
      <?php printf(_("Click on a message while holding down the %s key to select multiple messages.  To select a range of messages, click the first message of the range, navigate to the last message of the range, and then click on the last message while holding down the %s key."), '<span class="kbd">' . _("Ctrl") . '</span>', '<span class="kbd">' . _("Shift") . '</span>') ?><br /><br />
      <?php echo _("The following keyboard shortcuts are available:") ?><br />
      <span class="iconImg keyupImg"></span> / <span class="iconImg keydownImg"></span> : <?php echo _("Move up/down through the message list.") ?><br />
      <span class="kbd"><?php echo _("PgUp") ?></span> / <span class="kbd"><?php echo _("PgDown") ?></span> : <?php echo _("Move one page up/down through the message list.") ?><br />
      <span class="kbd"><?php echo _("Alt") ?></span> + <span class="kbd"><?php echo _("PgUp") ?></span> / <span class="kbd"><?php echo _("PgDown") ?></span> : <?php echo _("Scroll up/down through the display of the previewed message.") ?><br />
      <span class="kbd"><?php echo _("Home") ?></span> / <span class="kbd"><?php echo _("End") ?></span> : <?php echo  _("Move to the beginning/end of the message list.") ?><br />
      <span class="kbd"><?php echo _("Del") ?></span> : <?php echo _("Delete the currently selected message(s).") ?> <?php printf(_("%s will delete the current message and move to the next message if a single message is selected."), '<span class="kbd">' . _("Shift") . '</span> + <span class="kbd">' . _("Del") . '</span>') ?><br />
      <span class="kbd"><?php echo _("Shift") ?></span> + <span class="kbd"><?php echo _("N") ?></span> : <?php echo _("Move to the next unseen message (non-search folders only).") ?><br />
      <span class="kbd"><?php echo _("Enter") ?></span> : <?php echo _("Open message in a popup window.") ?><br />
      <span class="kbd"><?php echo _("Ctrl") ?></span> + <span class="kbd"><?php echo 'A' ?></span> : <?php echo _("Select all messages in the current mailbox.") ?><br />
     </div>

     <div id="previewMsg" style="display:none">
      <div class="msgHeaders">
       <div id="toggleHeaders">
        <a id="th_expand"><span class="iconImg" title="<?php echo htmlspecialchars(_("Expand Headers")) ?>"></span></a>
        <a id="th_collapse" style="display:none"><span class="iconImg" title="<?php echo htmlspecialchars(_("Collapse Headers")) ?>"></span></a>
       </div>
       <div id="msgHeadersColl">
        <a id="msg_newwin"><span class="iconImg" title="<?php echo htmlspecialchars(_("Open in new window")) ?>"></span></a>
        <span class="date"></span>
        <span class="subject"></span>
        <span class="fromcontainer"><?php echo _("from") ?> <span class="from"></span></span>
       </div>
       <div id="msgHeaders" style="display:none">
        <div class="dimpOptions">
         <div>
          <span id="msg_newwin_options">
           <span class="iconImg"></span>
           <a><?php echo _("Open in new window") ?></a>
          </span>
         </div>
<?php if (!empty($conf['user']['allow_view_source'])): ?>
         <div>
          <span id="msg_view_source">
           <span class="iconImg"></span>
           <a><?php echo _("View Source") ?></a>
          </span>
         </div>
<?php endif; ?>
         <div style="display:none">
          <span id="msg_resume_draft">
           <span class="iconImg"></span>
           <a><?php echo _("Resume Draft") ?></a>
          </span>
         </div>
        </div>
        <div id="msgHeadersContent">
         <table>
          <thead>
           <tr>
            <td class="label"><?php echo _("Subject") ?>:</td>
            <td class="subject"></td>
           </tr>
           <tr id="msgHeaderFrom">
            <td class="label"><?php echo _("From") ?>:</td>
            <td class="from"></td>
           </tr>
           <tr id="msgHeaderDate">
            <td class="label"><?php echo _("Date") ?>:</td>
            <td class="date"></td>
           </tr>
           <tr id="msgHeaderTo">
            <td class="label"><?php echo _("To") ?>:</td>
            <td class="to"></td>
           </tr>
           <tr id="msgHeaderCc">
            <td class="label"><?php echo _("Cc") ?>:</td>
            <td class="cc"></td>
           </tr>
           <tr id="msgAtc" style="display:none">
            <td class="label" id="partlist_toggle">
             <span class="iconImg attachmentImg attachmentImage"></span>
             <span class="iconImg" id="partlist_col"></span>
             <span class="iconImg" id="partlist_exp" style="display:none"></span>
             </td>
            <td>
             <div></div>
             <div id="partlist" style="display:none">
              <table></table>
             </div>
            </td>
           </tr>
           <tr id="msgLogInfo" style="display:none">
            <td class="label" id="msgloglist_toggle">
             <span class="iconImg" id="msgloglist_col"></span>
             <span class="iconImg" id="msgloglist_exp" style="display:none"></span>
            </td>
            <td>
             <div><span class="msgLogLabel"><?php echo _("Message Log") ?></span></div>
             <div id="msgloglist" style="display:none">
              <ul></ul>
             </div>
            </td>
           </tr>
          </thead>
         </table>
        </div>
       </div>
      </div>
      <div id="messageBody" class="messageBody"></div>
     </div>
    </div>
   </div>
  </div>
 </div>
</div>

<div class="context" id="ctx_folder" style="display:none">
 <a id="ctx_folder_create"><span class="contextImg"></span><?php echo _("Create subfolder") ?></a>
 <a id="ctx_folder_rename"><span class="contextImg"></span><?php echo _("Rename Folder") ?></a>
 <a id="ctx_folder_empty"><span class="contextImg"></span><?php echo _("Empty Folder") ?></a>
 <a id="ctx_folder_delete"><span class="contextImg"></span><?php echo _("Delete Folder") ?></a>
 <div class="sep"></div>
 <a id="ctx_folder_setflag"><span class="contextImg"></span><?php echo _("Mark all as") ?></a>
 <div class="sep"></div>
 <a id="ctx_folder_poll"><span class="contextImg"></span><?php echo _("Check for New Mail") ?></a>
 <a id="ctx_folder_nopoll"><span class="contextImg"></span><?php echo _("Do Not Check for New Mail") ?></a>
<?php if ($use_sub): ?>
 <a id="ctx_folder_sub"><span class="contextImg"></span><?php echo _("Subscribe") ?></a>
 <a id="ctx_folder_unsub"><span class="contextImg"></span><?php echo _("Unsubscribe") ?></a>
<?php endif; ?>
 <div style="display:none">
  <div class="sep"></div>
  <a id="ctx_folder_expand"><span class="contextImg"></span><?php echo _("Expand All") ?></a>
  <a id="ctx_folder_collapse"><span class="contextImg"></span><?php echo _("Collapse All") ?></a>
 </div>
</div>

<div class="context" id="ctx_folder_flag" style="display:none">
 <a id="ctx_folder_seen"><span class="contextImg"></span><?php echo _("Read") ?></a>
 <a id="ctx_folder_unseen"><span class="contextImg"></span><?php echo _("Unseen") ?></a>
</div>

<div class="context" id="ctx_container" style="display:none">
 <a id="ctx_container_create"><span class="contextImg"></span><?php echo _("Create subfolder") ?></a>
 <a id="ctx_container_rename"><span class="contextImg"></span><?php echo _("Rename Folder") ?></a>
 <div class="sep"></div>
 <a id="ctx_container_expand"><span class="contextImg"></span><?php echo _("Expand All") ?></a>
 <a id="ctx_container_collapse"><span class="contextImg"></span><?php echo _("Collapse All") ?></a>
</div>

<?php if ($conf['user']['allow_folders']): ?>
<div class="context" id="ctx_folderopts" style="display:none">
 <a id="ctx_folderopts_new"><span class="contextImg"></span><?php echo _("New Folder") ?></a>
<?php if ($use_sub): ?>
 <a id="ctx_folderopts_sub" style="display:none"><span class="contextImg"></span><?php echo _("Hide Unsubscribed") ?></a>
 <a id="ctx_folderopts_unsub"><span class="contextImg"></span><?php echo _("Show Unsubscribed") ?></a>
<?php endif; ?>
 <a id="ctx_folderopts_expand"><span class="contextImg"></span><?php echo _("Expand All") ?></a>
 <a id="ctx_folderopts_collapse"><span class="contextImg"></span><?php echo _("Collapse All") ?></a>
 <div class="sep"></div>
 <a id="ctx_folderopts_reload"><span class="contextImg"></span><?php echo _("Rebuild Folder List") ?></a>
</div>
<?php endif; ?>

<div class="context" id="ctx_noactions" style="display:none">
 <div><?php echo _("No actions available") ?></div>
</div>

<div class="context" id="ctx_vfolder" style="display:none">
 <a id="ctx_vfolder_edit"><span class="contextImg"></span><?php echo _("Edit Virtual Folder") ?></a>
 <a id="ctx_vfolder_delete"><span class="contextImg"></span><?php echo _("Delete Virtual Folder") ?></a>
</div>

<div class="context" id="ctx_message" style="display:none">
 <a id="ctx_message_resume" style="display:none"><span class="contextImg"></span><?php echo _("Resume Draft") ?></a>
 <a id="ctx_message_reply"><span class="contextImg"></span><?php echo _("Reply") ?></a>
 <a id="ctx_message_forward"><span class="contextImg"></span><?php echo _("Forward") ?></a>
<?php if (!empty($flag_list)): ?>
 <div>
  <div class="sep"></div>
  <a id="ctx_message_setflag"><span class="contextImg"></span><?php echo _("Mark as") ?>...</a>
  <a id="ctx_message_unsetflag"><span class="contextImg"></span><?php echo _("Unmark as") ?>...</a>
 </div>
<?php endif; ?>
 <div class="sep"></div>
<?php if (!empty($conf['spam']['reporting'])): ?>
 <a id="ctx_message_spam"><span class="contextImg"></span><?php echo _("Report as Spam") ?></a>
<?php endif; ?>
<?php if (!empty($conf['notspam']['reporting'])): ?>
 <a id="ctx_message_ham"><span class="contextImg"></span><?php echo _("Report as Innocent") ?></a>
<?php endif; ?>
<?php if ($has_blacklist): ?>
 <a id="ctx_message_blacklist"><span class="contextImg"></span><?php echo _("Blacklist") ?></a>
<?php endif; ?>
<?php if ($has_whitelist): ?>
 <a id="ctx_message_whitelist"><span class="contextImg"></span><?php echo _("Whitelist") ?></a>
<?php endif; ?>
 <a id="ctx_message_deleted"><span class="contextImg"></span><?php echo _("Delete") ?></a>
<?php if (!$use_trash): ?>
 <a id="ctx_message_undeleted"><span class="contextImg"></span><?php echo _("Undelete") ?></a>
<?php endif; ?>
 <div style="display:none">
  <div class="sep"></div>
  <a id="ctx_message_source"><span class="contextImg"></span><?php echo _("View Source") ?></a>
 </div>
</div>

<div class="context" id="ctx_reply" style="display:none;">
 <a id="ctx_reply_reply"><span class="contextImg"></span><?php echo _("To Sender") ?></a>
 <a id="ctx_reply_reply_all"><span class="contextImg"></span><?php echo _("To All") ?></a>
 <a id="ctx_reply_reply_list"><span class="contextImg"></span><?php echo _("To List") ?></a>
</div>

<div class="context" id="ctx_forward" style="display:none;">
<?php if (!$prefs->isLocked('forward_default')): ?>
 <a id="ctx_forward_attach"><span class="contextImg"></span><?php echo _("As Attachment") ?></a>
 <a id="ctx_forward_body"><span class="contextImg"></span><?php echo _("In Body Text") ?></a>
 <a id="ctx_forward_both"><span class="contextImg"></span><?php echo _("Attachment and Body Text") ?></a>
 <div class="sep"></div>
<?php endif; ?>
 <a id="ctx_forward_redirect"><span class="contextImg"></span><?php echo _("Redirect") ?></a>
</div>

<div class="context" id="ctx_otheractions" style="display:none">
 <a id="oa_preview_hide"><span class="contextImg"></span><?php echo _("Hide Preview") ?></a>
 <a id="oa_preview_show"><span class="contextImg"></span><?php echo _("Show Preview") ?></a>
 <a id="oa_layout_horiz"><span class="contextImg"></span><?php echo _("Horizontal Layout") ?></a>
 <a id="oa_layout_vert"><span class="contextImg"></span><?php echo _("Vertical Layout") ?></a>
<?php if (!empty($flag_list)): ?>
 <div>
  <div class="sep"></div>
  <a id="oa_setflag"><span class="contextImg"></span><?php echo _("Mark as") ?>...</a>
  <a id="oa_unsetflag"><span class="contextImg"></span><?php echo _("Unmark as") ?>...</a>
 </div>
<?php endif; ?>
<?php if ($has_blacklist || $has_whitelist): ?>
<?php if ($has_blacklist): ?>
 <div>
  <div class="sep"></div>
  <a id="oa_blacklist"><span class="contextImg"></span><?php echo _("Blacklist") ?></a>
 </div>
<?php endif; ?>
<?php if ($has_whitelist): ?>
 <div>
  <div<?php if (!$has_blacklist) echo ' class="sep"'; ?>></div>
  <a id="oa_whitelist"><span class="contextImg"></span><?php echo _("Whitelist") ?></a>
 </div>
<?php endif; ?>
<?php endif; ?>
 <div class="sep"></div>
 <a id="oa_selectall"><span class="contextImg"></span><?php echo _("Select All") ?></a>
<?php if (!$use_trash): ?>
 <div class="sep"></div>
 <a id="oa_purge_deleted"><span class="contextImg"></span><?php echo _("Purge Deleted") ?></a>
 <a id="oa_undeleted"><span class="contextImg"></span><?php echo _("Undelete") ?></a>
<?php endif; ?>
</div>

<?php if (!empty($flag_list)): ?>
<div class="context" id="ctx_flag" style="display:none">
<?php foreach ($flag_list as $key => $val): ?>
 <a flag="<?php echo htmlspecialchars($key) ?>"><span class="contextImg <?php echo htmlspecialchars($val['c']) ?>"<?php echo ($val['t'] == 'imapp') ? ' style="background:' . htmlspecialchars($val['b']) . '"' : '' ?>></span><?php echo htmlspecialchars($val['l']) ?></a>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="context" id="ctx_contacts" style="display:none">
 <a id="ctx_contacts_new"><span class="contextImg"></span><?php echo _("New Message") ?></a>
 <a id="ctx_contacts_add"><span class="contextImg"></span><?php echo _("Add to Address Book") ?></a>
</div>

<div class="context" id="ctx_mboxsort" style="display:none">
 <a id="ctx_mboxsort_none"><?php echo _("Clear Sort") ?></a>
</div>

<?php if ($_SESSION['imp']['protocol'] != 'pop'): ?>
<div class="context" id="ctx_qsearchopts" style="display:none">
 <a id="ctx_qsearchopts_by"><?php echo _("Search By") ?></a>
 <div class="sep"></div>
 <a id="ctx_qsearchopts_filter"><?php echo _("Show Only") ?></a>
 <a id="ctx_qsearchopts_filternot"><?php echo _("Don't Show") ?></a>
 <div class="sep"></div>
 <a id="ctx_qsearchopts_advanced"><?php echo _("Advanced Search...") ?></a>
</div>

<div class="context" id="ctx_qsearchby" style="display:none">
 <a id="ctx_qsearchby_all"><?php echo _("Entire Message") ?></a>
 <a id="ctx_qsearchby_body"><?php echo _("Body") ?></a>
 <a id="ctx_qsearchby_from"><?php echo _("From") ?></a>
 <a id="ctx_qsearchby_to"><?php echo _("To") ?></a>
 <a id="ctx_qsearchby_subject"><?php echo _("Subject") ?></a>
</div>
<?php endif; ?>

<div style="display:none">
 <span id="largeaddrspan">
  <span class="largeaddrtoggle">
   <span class="largeaddrlist">[<?php echo _("Show Addresses - %d recipients") ?>]</span>
   <span class="largeaddrlist" style="display:none">[<?php echo _("Hide Addresses") ?>]</span>
  </span>
  <span class="dispaddrlist" style="display:none"></span>
 </span>
</div>

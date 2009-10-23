<?php

$usetrash = $prefs->getValue('use_trash');
$show_text = in_array($prefs->getValue('menu_view'), array('both', 'text'));
$use_sub = $prefs->getValue('subscribe');

$has_blacklist = $registry->hasMethod('mail/blacklistFrom');
$has_whitelist = $registry->hasMethod('mail/whitelistFrom');

// Quota information
$show_quota = (isset($_SESSION['imp']['quota']) && is_array($_SESSION['imp']['quota']));

// Get the list of available IMAP flags
$imp_flags = IMP_Imap_Flags::singleton();
$flag_list = $imp_flags->getList(array('imap' => true));

// Small utility functions to simplify creating dimpactions buttons.
// As of right now, we don't show text only links.
function _createDA($text, $image, $id = null, $class = '', $show_text = true,
                   $div_text = '')
{
    $params = array('icon' => $image, 'id' => $id, 'class' => $class);
    if ($show_text) {
        $params['title'] = $text;
    } else {
        $params['tooltip'] = $text;
    }
    echo '<div' . $div_text . '>' . IMP_Dimp::actionButton($params) . '</div>';
}

function _simpleButton($id, $text, $image)
{
    $ak = Horde::getAccessKey($text, true);
    return '<li class="servicelink"' .
        (strlen($id) ? ' id="' . $id . '"' : '') .
        (strlen($ak) ? ' accesskey="' . $ak . '"' : '') . '>' .
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
  <div id="sidebarPanel" class="noprint">
   <div id="logo">
    <a id="logolink"><?php echo _("Horde") ?></a>
   </div>
   <ul id="dimpbarActions">
    <?php echo _simpleButton('composelink', _("_New Message"), 'dimpactionCompose') ?>
    <?php echo _simpleButton('checkmaillink', _("_Get Mail"), 'dimpactionCheckmail') ?>
    <?php echo _simpleButton('alertsloglink', _("Alerts _Log"), 'infoIcon') ?>
<?php if (!empty($_SESSION['imp']['filteravail'])): ?>
    <?php echo _simpleButton('applyfilterlink', _("Apply Filter"), 'filtersIcon') ?>
<?php endif; ?>
   </ul>
   <div class="sepfull"></div>
   <ul id="serviceActions">
<?php if (($registry->get('status', 'horde') != 'hidden') && ($registry->get('status', 'horde') != 'notoolbar')): ?>
    <?php echo _simpleButton('appportal', _("_Portal"), 'hordeIcon') ?>
<?php endif; ?>
<?php if (Horde::showService('options')): ?>
    <?php echo _simpleButton('appoptions', _("_Options"), 'prefsIcon') ?>
<?php endif; ?>
<?php if (Horde::showService('logout')): ?>
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
    <div class="sepfull"></div>
    <ul class="folderlist" id="normalfolders">
     <li class="folder" id="folderopts">
      <span class="iconSpan folderImg"></span>
      <a title="<?php echo _("Folder Options") ?>"><?php echo _("Folder Options") ?></a>
     </li>
     <li class="folder" id="dropbase" style="display:none">
      <span class="iconSpan folderImg"></span>
      <a title="<?php echo _("Move to Base Level") ?>"><?php echo _("Move to Base Level") ?></a>
     </li>
    </ul>
   </div>
  </div>

  <div id="dimpmain">
   <div id="dimpmain_portal"><?php echo _("Loading...") ?></div>
   <div id="dimpmain_folder" style="display:none">
    <div id="dimpmain_folder_top" class="noprint">
     <div id="tabbar">
<?php if (!empty($application_folders) || !empty($site_menu)): ?>
      <div class="tabset">
       <ul>
        <li>
         <a id="hometab"><img src="<?php echo $registry->get('icon', 'horde') ?>" alt="<?php echo _("Home") ?>" /><?php echo _("Home") ?></a>
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
         <span class="used"><span class="iconImg"></span></span>
        </li>
<?php endif; ?>
       </ul>
      </div>
<?php elseif ($show_quota): ?>
      <div id="quota">
       <span class="used"><span class="iconImg"></span></span>
      </div>
<?php endif; ?>
     </div>
     <div class="header mboxheader">
      <div>
       <span class="rightFloat" id="msgHeader"></span>
       <span id="viewportLoading" class="loadingImg" style="display:none"></span>
       <span id="folderName"></span>
       <span class="iconImg readonlyImg" style="display:none" title="<?php echo htmlspecialchars(_("Read-Only")) ?>"></span>
      </div>
     </div>

     <div class="dimpActions">
<?php if ($_SESSION['imp']['protocol'] != 'pop'): ?>
      <div id="qsearch">
       <span id="qsearch_icon"></span>
       <form action="#" method="post">
        <input autocomplete="off" id="qsearch_input" type="text" size="25" />
       </form>
       <span class="closeImg" id="qsearch_close" style="display:none" title="<?php echo _("Clear Search") ?>"></span>
       <span id="qsearch_close_filter" style="display:none">Clear Filter</span>
      </span>
      </div>
<?php endif; ?>
      <?php _createDA(_("Reply"), 'Reply', 'button_reply', 'noselectDisable', $show_text) ?>
      <?php _createDA(_("Forward"), 'Forward', 'button_forward', 'noselectDisable', $show_text) ?>
<?php if (!empty($conf['spam']['reporting'])): ?>
      <?php _createDA(_("Spam"), 'Spam', 'button_spam', 'noselectDisable', $show_text) ?>
<?php endif; ?>
<?php if (!empty($conf['notspam']['reporting'])): ?>
      <?php _createDA(_("Innocent"), 'Ham', 'button_ham', 'noselectDisable', $show_text, ' style="display:none"') ?>
<?php endif; ?>
      <?php _createDA(_("Delete"), 'Delete', 'button_deleted', 'noselectDisable', $show_text) ?>
      <?php _createDA(_("Compose"), 'Compose', '', '', $show_text, ' id="button_compose"') ?>
      <?php _createDA(_("Get Mail"), 'Checkmail', '', '', $show_text, ' id="button_checkmail"') ?>
      <?php _createDA(_("Other Actions"), 'Other', 'button_other', '', $show_text) ?>
     </div>

     <div id="msglistHeader" class="item">
      <div class="msgStatus"></div>
      <div class="msgFrom sep">
       <a class="widget" sortby="<?php echo Horde_Imap_Client::SORT_FROM ?>"><?php echo _("From") ?></a>
       <a class="widget" style="display:none" sortby="<?php echo Horde_Imap_Client::SORT_TO ?>"><?php echo _("To") ?></a>
      </div>
      <div class="msgSubject sep">
       <a class="widget" sortby="<?php echo Horde_Imap_Client::SORT_SUBJECT ?>"><?php echo _("Subject") ?>
        <small sortby="<?php echo Horde_Imap_Client::SORT_THREAD ?>">[<?php echo _("Thread") ?>]</small>
       </a>
       <a class="widget" style="display:none" sortby="<?php echo Horde_Imap_Client::SORT_THREAD ?>"><?php echo _("Thread") ?>
        <small sortby="<?php echo Horde_Imap_Client::SORT_SUBJECT ?>">[<?php echo _("Subject") ?>]</small>
       </a>
      </div>
      <div class="msgDate sep">
       <a class="widget" sortby="<?php echo Horde_Imap_Client::SORT_DATE ?>"><?php echo _("Date") ?>
        <small sortby="<?php echo Horde_Imap_Client::SORT_ARRIVAL ?>">[<?php echo _("Arrival") ?>]</small>
       </a>
       <a class="widget" style="display:none" sortby="<?php echo Horde_Imap_Client::SORT_ARRIVAL ?>"><?php echo _("Arrival") ?>
        <small sortby="<?php echo Horde_Imap_Client::SORT_DATE ?>">[<?php echo _("Date") ?>]</small>
       </a>
      </div>
      <div class="msgSize sep">
       <a class="widget" sortby="<?php echo Horde_Imap_Client::SORT_SIZE ?>"><?php echo _("Size") ?></a>
      </div>
     </div>
    </div>

    <div id="msgSplitPane">
     <div id="msgList" class="noprint msglist"></div>
     <div id="splitBar" style="display:none" class="noprint"></div>
     <div id="previewPane" style="display:none">
      <span id="msgLoading" class="loadingImg" style="display:none"></span>
      <div id="previewInfo" style="display:none">
       <?php echo _("To preview a message, select it from the list above.") ?>
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
       <span class="kbd"><?php echo _("Enter") ?></span> : <?php echo _("Open message in a popup window.") ?><br />
       <span class="kbd"><?php echo _("Ctrl") ?></span> + <span class="kbd"><?php echo 'A' ?></span> : <?php echo _("Select all messages in the current mailbox.") ?><br />
      </div>
      <div id="previewMsg" style="display:none">
       <div class="msgHeaders">
        <div id="toggleHeaders" class="noprint">
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
         <div class="dimpOptions noprint">
          <div>
           <span id="msg_newwin_options">
            <span class="iconImg"></span>
            <a><?php echo _("Open in new window") ?></a>
           </span>
          </div>
          <div>
           <span id="msg_print">
            <span class="iconImg"></span>
            <a><?php echo _("Print") ?></a>
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
             <td class="label"><span class="iconImg attachmentImg attachmentImage"></span><a id="partlist_toggle"><span class="iconImg" id="partlist_col"></span><span class="iconImg" id="partlist_exp" style="display:none"></span></a></td>
             <td>
              <div></div>
              <div id="partlist" style="display:none">
               <table></table>
              </div>
             </td>
            </tr>
            <tr id="msgLogInfo" style="display:none">
             <td class="label">
              <a id="msgloglist_toggle"><span class="iconImg" id="msgloglist_col"></span><span class="iconImg" id="msgloglist_exp" style="display:none"></span></a>
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
       <div id="msgBody" class="msgBody"></div>
      </div>
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
 <a id="ctx_folder_seen"><span class="contextImg"></span><?php echo _("Mark all as Read") ?></a>
 <a id="ctx_folder_unseen"><span class="contextImg"></span><?php echo _("Mark all as New") ?></a>
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
 <span>No actions available</span>
</div>

<div class="context" id="ctx_vfolder" style="display:none">
 <a id="ctx_vfolder_edit"><span class="contextImg"></span><?php echo _("Edit Virtual Folder") ?></a>
 <a id="ctx_vfolder_delete"><span class="contextImg"></span><?php echo _("Delete Virtual Folder") ?></a>
</div>

<div class="context" id="ctx_message" style="display:none">
 <a id="ctx_message_reply"><span class="contextImg"></span><?php echo _("Reply") ?></a>
 <a id="ctx_message_forward"><span class="contextImg"></span><?php echo _("Forward") ?></a>
 <div class="sep"></div>
<?php if (!empty($flag_list)): ?>
 <a id="ctx_message_setflag"><span class="contextImg"></span><?php echo _("Mark as") ?></a>
 <a id="ctx_message_unsetflag"><span class="contextImg"></span><?php echo _("Unmark as") ?></a>
 <div class="sep"></div>
<?php endif; ?>
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
<?php if (!$usetrash): ?>
 <a id="ctx_message_undeleted"><span class="contextImg"></span><?php echo _("Undelete") ?></a>
<?php endif; ?>
</div>

<div class="context" id="ctx_draft" style="display:none">
 <a id="ctx_draft_resume"><span class="contextImg"></span><?php echo _("Resume Draft") ?></a>
 <div class="sep"></div>
<?php if (!empty($flag_list)): ?>
 <a id="ctx_draft_setflag"><span class="contextImg"></span><?php echo _("Mark as") ?></a>
 <a id="ctx_draft_unsetflag"><span class="contextImg"></span><?php echo _("Unmark as") ?></a>
 <div class="sep"></div>
<?php endif; ?>
 <a id="ctx_draft_deleted"><span class="contextImg"></span><?php echo _("Delete") ?></a>
<?php if (!$usetrash): ?>
 <a id="ctx_draft_undeleted"><span class="contextImg"></span><?php echo _("Undelete") ?></a>
<?php endif; ?>
</div>

<div class="context" id="ctx_reply" style="display:none;">
 <a id="ctx_reply_reply"><span class="contextImg"></span><?php echo _("To Sender") ?></a>
 <a id="ctx_reply_reply_all"><span class="contextImg"></span><?php echo _("To All") ?></a>
 <a id="ctx_reply_reply_list"><span class="contextImg"></span><?php echo _("To List") ?></a>
</div>

<div class="context" id="ctx_otheractions" style="display:none">
 <a id="previewtoggle"><span class="contextImg"></span><?php echo ($prefs->getValue('dimp_show_preview') ? _("Hide Preview") : _("Show Preview")); ?></a>
<?php if (!empty($flag_list)): ?>
 <div>
  <div class="sep"></div>
  <a id="oa_setflag"><span class="contextImg"></span><?php echo _("Mark as") ?></a>
  <a id="oa_unsetflag"><span class="contextImg"></span><?php echo _("Unmark as") ?></a>
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
  <div class="sep"></div>
  <a id="oa_whitelist"><span class="contextImg"></span><?php echo _("Whitelist") ?></a>
 </div>
<?php endif; ?>
<?php endif; ?>
 <div class="sep"></div>
 <a id="oa_selectall"><span class="contextImg"></span><?php echo _("Select All") ?></a>
<?php if (!$usetrash): ?>
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

<div id="folderform" style="display:none">
 <form action="#" class="RBForm">
  <p></p>
  <input type="text" size="15" />
  <input type="button" class="button RBFolderOk" value="<?php echo _("Ok") ?>" />
  <input type="button" class="button RBFolderCancel" value="<?php echo _("Cancel") ?>" />
 </form>
</div>

<div id="msgBodyCover" style="display:none"></div>

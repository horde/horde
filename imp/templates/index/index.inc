<?php
// Generate various dimp graphics used in multiple locations
$hordeimg = $registry->getImageDir('horde');

// Thread images
$thread_imgs = IMP_IMAP_Thread::getImageUrls(true, true);

// Attachment images
$imp_ui = new IMP_UI_Mailbox('INBOX');
$atc_imgs = array();
foreach ($imp_ui->getAttachmentAltList() as $k => $v) {
    $atc_imgs[] = IMP::img($k . '.png', $v, array('id' => 'atc_img_' . $k));
}

$usetrash = $prefs->getValue('use_trash');
$menu_view = $prefs->getValue('menu_view');
$show_text = ($menu_view == 'text' || $menu_view == 'both');
$loading_text = _("Loading...");
$is_ie6 = ($browser->isBrowser('msie') && ($browser->getMajor() < 7));

$has_blacklist = $registry->hasMethod('mail/blacklistFrom');
$has_whitelist = $registry->hasMethod('mail/whitelistFrom');

// Sidebar width
$sidebar_width = max((int)$prefs->getValue('sidebar_width') - 50, 150) . 'px';

// Quota information
$show_quota = (isset($_SESSION['imp']['quota']) && is_array($_SESSION['imp']['quota']));

// Small utility functions to simplify creating dimpactions buttons.
// As of right now, we don't show text only links.
function _createDA($text, $image, $id = null, $class = '', $show_text = true)
{
    $params = array('icon' => $image, 'id' => $id, 'class' => $class);
    if ($show_text) {
        $params['title'] = $text;
    } else {
        $params['tooltip'] = $text;
    }
    echo DIMP::actionButton($params);
}

function _simpleButton($id, $text, $image, $imagedir = null)
{
    $ak = Horde::getAccessKey($text, true);
    return '<li class="servicelink"'
        . (strlen($id) ? ' id="' . $id . '"' : '')
        . (strlen($ak) ? ' accesskey="' . $ak . '"' : '') . '>'
        . ($imagedir
            ? IMP::img($image, Horde::stripAccessKey($text), '', $imagedir)
            : IMP::img($image, Horde::stripAccessKey($text)))
        . '<a>'
        . Horde::highlightAccessKey($text, $ak) . '</a></li>';
}
?>
<div id="dimpLoading"><?php echo $loading_text ?></div>
<div id="dimpPage" style="display:none">
 <div id="header"></div>
 <div id="pageContainer">
  <div id="sidebarPanel" class="noprint" style="width:<?php echo $sidebar_width ?>">
   <div id="logo"><h1><a id="logolink"><?php echo _("Horde") ?></a></h1></div>
   <ul id="dimpbarActions">
    <?php echo _simpleButton('composelink', _("_New Message"), 'compose.png') ?>
    <?php echo _simpleButton('checkmaillink', _("_Get Mail"), 'checkmail.png') ?>
<?php if (($_SESSION['imp']['protocol'] != 'pop') && $prefs->getValue('fetchmail_menu')): ?>
    <?php echo _simpleButton('fetchmaillink', _("_Fetch Mail"), 'fetchmail.png') ?>
<?php endif; ?>
<?php if (!$is_ie6): ?>
    <?php echo _simpleButton('alertsloglink', _("Alerts _Log"), 'info_icon.png', $hordeimg) ?>
<?php endif; ?>
   </ul>
   <div class="sepfull" style="width:<?php echo $sidebar_width ?>"></div>
   <ul id="serviceActions">
<?php if ($registry->get('status', 'horde') != 'hidden' && $registry->get('status', 'horde') != 'notoolbar'): ?>
    <?php echo _simpleButton('appportal', _("_Portal"), 'horde.png', $hordeimg) ?>
<?php endif; ?>
<?php if (Horde::showService('options')): ?>
    <?php echo _simpleButton('appoptions', _("_Options"), 'prefs.png', $hordeimg) ?>
<?php endif; ?>
<?php if (Horde::showService('logout')): ?>
    <?php echo _simpleButton('applogout', _("_Log Out"), 'logout.png', $hordeimg) ?>
<?php endif; ?>
   </ul>
   <div class="sepfull" style="width:<?php echo $sidebar_width ?>"></div>
   <div id="foldersLoading"><?php echo $loading_text ?></div>
   <div id="foldersSidebar" style="display:none">
    <ul class="folderlist" id="specialfolders"></ul>
<?php if (!empty($application_folders)): ?>
    <div class="sepfull" style="width:<?php echo $sidebar_width ?>"></div>
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
     <div class="sepfull" style="width:<?php echo $sidebar_width ?>"></div>
     <ul>
<?php foreach ($site_menu as $key => $menu_item): ?>
<?php if ($menu_item == 'separator'): ?>
     </ul>
     <div class="sepfull" style="width:<?php echo $sidebar_width ?>"></div>
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
    <div class="sepfull" style="width:<?php echo $sidebar_width ?>"></div>
    <div id="myfolders"><?php echo _("My Folders") ?></div>
    <ul class="folderlist" id="normalfolders">
     <li class="folder" id="newfolder">
      <div class="create"></div>
      <a title="<?php echo _("New Folder") ?>"><?php echo _("New Folder") ?></a>
     </li>
     <li class="folder" id="dropbase" style="display:none">
      <div class="base"></div>
      <a title="<?php echo _("Move to Base Level") ?>"><?php echo _("Move to Base Level") ?></a>
     </li>
    </ul>
   </div>
  </div>

  <div id="dimpmain" style="left:<?php echo $sidebar_width ?>">
   <div id="dimpmain_portal"><?php echo $loading_text ?></div>
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
         <span class="used"><span class="iconImg quotaImg"></span></span>
        </li>
<?php endif; ?>
       </ul>
      </div>
<?php elseif ($show_quota): ?>
      <div id="quota">
       <span class="used"><span class="iconImg quotaImg"></span></span>
      </div>
<?php endif; ?>
     </div>
     <br class="clear" />
     <div id="mailboxHeader" class="header">
      <div>
       <span class="rightFloat" id="msgHeader"></span>
       <span id="folderLoading" class="loadingImg" style="display:none"></span>
       <span id="folderName"></span>
      </div>
     </div>

     <div class="dimpActions">
      <form action="#" method="post" onsubmit="return false;">
       <input type="text" name="msgList_filter" id="msgList_filter" size="30" />
      </form>
      <span>
       <?php _createDA(_("Reply"), 'reply_menu.png', 'button_reply', '', $show_text) ?>
      </span>
      <span>
       <?php _createDA(_("Forward"), 'forward_menu.png', 'button_forward', '', $show_text) ?>
      </span>
<?php if (!empty($conf['spam']['reporting'])): ?>
      <span>
       <?php _createDA(_("Spam"), 'spam_menu.png', 'button_spam', '', $show_text) ?>
      </span>
<?php endif; ?>
<?php if (!empty($conf['notspam']['reporting'])): ?>
      <span style="display:none">
       <?php _createDA(_("Innocent"), 'ham_menu.png', 'button_ham', '', $show_text) ?>
      </span>
<?php endif; ?>
      <span id="button_compose">
       <?php _createDA(_("Compose"), 'compose_menu.png', '', '', $show_text) ?>
      </span>
      <span id="button_checkmail">
       <?php _createDA(_("Get Mail"), 'checkmail_menu.png', '', '', $show_text) ?>
      </span>
      <span>
       <?php _createDA(_("Delete"), 'delete_menu.png', 'button_deleted', '', $show_text) ?>
      </span>
      <span>
       <?php _createDA(_("Other Actions"), 'plus_menu.png', 'button_other', '', $show_text) ?>
      </span>
     </div>

     <div style="display:none">
      <div id="qoptions">
       <span id="qclose"><a>x</a></span>
       <span class="qlabel"><?php echo _("Folders: ") ?></span>
<?php if (!empty($conf['dimp']['search']['search_all'])): ?>
       <a id="sf_all"><?php echo _("All") ?></a>
<?php endif; ?>
       <a id="sf_current"></a>
       <span class="qlabel">|&nbsp;</span>
       <span class="qlabel"><?php echo _("Message: ") ?></span>
       <a id="sf_msgall"><?php echo _("All") ?></a>
       <a id="sf_from"><?php echo _("From") ?></a>
       <a id="sf_to"><?php echo _("To") ?></a>
       <a id="sf_subject"><?php echo _("Subject") ?></a>
      </div>
     </div>

     <div id="msglistHeader" class="item">
      <div class="msgStatus">&nbsp;</div>
      <div class="msgFrom">
       <a class="widget" sortby="<?php echo Horde_Imap_Client::SORT_FROM ?>"><?php echo _("From") ?></a>
       <a class="widget" style="display:none" sortby="<?php echo Horde_Imap_Client::SORT_TO ?>"><?php echo _("To") ?></a>
      </div>
      <div class="msgSubject">
       <a class="widget" sortby="<?php echo Horde_Imap_Client::SORT_SUBJECT ?>"><?php echo _("Subject") ?>
        <small sortby="<?php echo Horde_Imap_Client::SORT_THREAD ?>">[<?php echo _("Thread") ?>]</small>
       </a>
       <a class="widget" style="display:none" sortby="<?php echo Horde_Imap_Client::SORT_THREAD ?>"><?php echo _("Thread") ?>
        <small sortby="<?php echo Horde_Imap_Client::SORT_SUBJECT ?>">[<?php echo _("Subject") ?>]</small>
       </a>
      </div>
      <div class="msgDate">
       <a class="widget" sortby="<?php echo Horde_Imap_Client::SORT_DATE ?>"><?php echo _("Date") ?>
        <small sortby="<?php echo Horde_Imap_Client::SORT_ARRIVAL ?>">[<?php echo _("Arrival") ?>]</small>
       </a>
       <a class="widget" style="display:none" sortby="<?php echo Horde_Imap_Client::SORT_ARRIVAL ?>"><?php echo _("Arrival") ?>
        <small sortby="<?php echo Horde_Imap_Client::SORT_DATE ?>">[<?php echo _("Date") ?>]</small>
       </a>
      </div>
      <div class="msgSize"><a class="widget" sortby="<?php echo Horde_Imap_Client::SORT_SIZE ?>"><?php echo _("Size") ?></a></div>
     </div>
     <div class="clear" id="mlistHeaderClear">&nbsp;</div>
    </div>

    <div id="msgSplitPane">
     <div id="msgListDiv" class="noprint">
      <div id="msgList_empty" style="display:none"><em><?php echo _("No messages to display") ?></em></div>
      <div id="msgList_error" style="display:none"><em><?php echo _("Could not get message list from server") ?></em></div>
      <div id="msgList" class="msglist">&nbsp;</div>
     </div>
     <div id="splitBar" style="display:none" class="splitBar noprint"></div>
     <div id="previewPane" style="display:none">
      <span id="msgLoading" class="loadingImg" style="display:none"></span>
      <div id="previewInfo" style="display:none">
       <?php echo _("To preview a message, select it from the list above. A right-click on the messages will display available actions.") ?><br />
       <?php printf(_("Click on a message while holding down the %s key to select multiple messages.  To select a range of messages, click the first message of the range, navigate to the last message of the range, and then click on the last message while holding down the %s key."), '<span class="kbd">' . _("Ctrl") . '</span>', '<span class="kbd">' . _("Shift") . '</span>') ?><br /><br />
       <?php echo _("The following keyboard shortcuts are available:") ?><br />
       <span class="iconImg keyupImg"></span> / <span class="iconImg keydownImg"></span> : <?php echo _("Move up/down through the message list.") ?><br />
       <span class="kbd"><?php echo _("PgUp") ?></span> / <span class="kbd"><?php echo _("PgDown") ?></span> : <?php echo _("Move one page up/down through the message list.") ?><br />
       <span class="kbd"><?php echo _("Home") ?></span> / <span class="kbd"><?php echo _("End") ?></span> : <?php echo  _("Move to the beginning/end of the message list.") ?><br />
       <span class="kbd"><?php echo _("Del") ?></span> : <?php echo _("Delete the currently selected message(s).") ?> <?php printf(_("%s will delete the current message and move to the next message if a single message is selected."), '<span class="kbd">' . _("Shift") . '</span> + <span class="kbd">' . _("Del") . '</span>') ?><br />
       <span class="kbd"><?php echo _("Enter") ?></span> : <?php echo _("Open message in a popup window.") ?><br />
       <span class="kbd"><?php echo _("Ctrl") ?></span> + <span class="kbd"><?php echo 'A' ?></span> : <?php echo _("Select all messages in the current mailbox.") ?><br />
      </div>
      <div id="previewMsg" style="display:none">
       <div class="msgHeaders">
        <div id="toggleHeaders" class="noprint">
         <a id="th_expand"><span class="iconImg arrowcollapsedImg" title="<?php echo htmlspecialchars(_("Expand Headers")) ?>"></span></a>
         <a id="th_collapse" style="display:none"><span class="iconImg arrowexpandedImg" title="<?php echo htmlspecialchars(_("Collapse Headers")) ?>"></span></a>
         <span class="iconImg attachmentImg" style="display:none"></span>
        </div>
        <div id="msgHeadersColl">
         <a id="msg_newwin"><span class="iconImg newwinImg newwinCollapsed" title="<?php echo htmlspecialchars(_("Open in new window")) ?>"></span></a>
         <span class="date"></span>
         <span class="subject"></span>
         <span class="fromcontainer"><?php echo _("from") ?> <span class="from"></span></span>
        </div>
        <div id="msgHeaders" style="display:none">
         <div class="dimpOptions noprint">
          <div id="msg_newwin_options"><span class="iconImg newwinImg"></span><a><?php echo _("Open in new window") ?></a></div>
          <div id="msg_print"><?php echo IMP::img('print.png', '', '', $hordeimg) ?><a><?php echo _("Print") ?></a></div>
<?php if (!empty($conf['user']['allow_view_source'])): ?>
          <div id="msg_view_source"><span class="iconImg msgsourceImg"></span><a><?php echo _("View Message Source") ?></a></div>
<?php endif; ?>
         </div>
         <div id="msgHeadersContent">
          <table cellspacing="0">
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
             <td class="label"><span class="iconImg attachmentImg attachmentImage"></span><a id="partlist_toggle"><span class="iconImg arrowcollapsedImg" id="partlist_col"></span><span class="iconImg arrowexpandedImg" id="partlist_exp" style="display:none"></span></a></td>
             <td>
              <div></div>
              <div id="partlist" style="display:none">
               <table cellspacing="2"></table>
              </div>
             </td>
            </tr>
           </thead>
          </table>
         </div>
        </div>
       </div>
       <div id="msgBody" class="msgBody">
        <table width="100%" cellspacing="0"></table>
       </div>
      </div>
     </div>
     <div style="clear:left" />
    </div>
   </div>
  </div>
 </div>
</div>

<div class="context" id="ctx_folder" style="display:none">
 <a id="ctx_folder_create"><span class="contextImg createfolderImg"></span><?php echo _("Create subfolder") ?></a>
 <a id="ctx_folder_rename"><span class="contextImg editfolderImg"></span><?php echo _("Rename Folder") ?></a>
 <a id="ctx_folder_empty"><span class="contextImg deleteImg"></span><?php echo _("Empty Folder") ?></a>
 <a id="ctx_folder_delete"><span class="contextImg folderdeleteImg"></span><?php echo _("Delete Folder") ?></a>
 <div id="ctx_folder_seen_sep" class="sep"></div>
 <a id="ctx_folder_seen"><span class="contextImg mailseenImg"></span><?php echo _("Mark all as Read") ?></a>
 <a id="ctx_folder_unseen"><span class="contextImg mailunseenImg"></span><?php echo _("Mark all as New") ?></a>
 <div id="ctx_folder_poll_sep" class="sep"></div>
 <a id="ctx_folder_poll"><span class="contextImg mailseenImg"></span><?php echo _("Check for New Mail") ?></a>
 <a id="ctx_folder_nopoll"><span class="contextImg mailseenImg"></span><?php echo _("Do Not Check for New Mail") ?></a>
</div>

<div class="context" id="ctx_container" style="display:none">
 <a id="ctx_container_create"><span class="contextImg createfolderImg"></span><?php echo _("Create subfolder") ?></a>
 <a id="ctx_container_rename"><span class="contextImg editfolderImg"></span><?php echo _("Rename Folder") ?></a>
</div>

<div class="context" id="ctx_message" style="display:none">
 <a id="ctx_message_reply"><span class="contextImg replyImg"></span><?php echo _("Reply to Sender") ?></a>
 <a id="ctx_message_reply_all"><span class="contextImg replyallImg"></span><?php echo _("Reply to All") ?></a>
 <a id="ctx_message_reply_list"><span class="contextImg replyallImg"></span><?php echo _("Reply to List") ?></a>
 <div class="sep"></div>
 <a id="ctx_message_forward_all"><span class="contextImg forwardImg"></span><?php echo _("Forward Entire Message") ?></a>
 <a id="ctx_message_forward_body"><span class="contextImg forwardImg"></span><?php echo _("Forward Body Text Only") ?></a>
 <a id="ctx_message_forward_attachments"><span class="contextImg forwardImg"></span><?php echo _("Forward Attachments Only") ?></a>
 <div class="sep"></div>
 <a id="ctx_message_seen"><span class="contextImg mailseenImg"></span><?php echo _("Mark as Read") ?></a>
 <a id="ctx_message_unseen"><span class="contextImg mailunseenImg"></span><?php echo _("Mark as New") ?></a>
 <a id="ctx_message_flagged"><span class="contextImg mailflaggedImg"></span><?php echo _("Flag Message") ?></a>
 <a id="ctx_message_clear"><span class="contextImg clearflagImg"></span><?php echo _("Clear Flag") ?></a>
 <div class="sep"></div>
<?php if (!empty($conf['spam']['reporting'])): ?>
 <a id="ctx_message_spam"><span class="contextImg spamImg"></span><?php echo _("Mark as Spam") ?></a>
<?php endif; ?>
<?php if (!empty($conf['notspam']['reporting'])): ?>
 <a id="ctx_message_ham"><span class="contextImg hamImg"></span><?php echo _("Mark as Innocent") ?></a>
<?php endif; ?>
<?php if ($has_blacklist): ?>
 <a id="ctx_message_blacklist"><span class="contextImg blacklistImg"></span><?php echo _("Blacklist") ?></a>
<?php endif; ?>
<?php if ($has_whitelist): ?>
 <a id="ctx_message_whitelist"><span class="contextImg whitelistImg"></span><?php echo _("Whitelist") ?></a>
<?php endif; ?>
 <a id="ctx_message_deleted"><span class="contextImg deleteImg"></span><?php echo _("Delete") ?></a>
<?php if (!$usetrash): ?>
 <a id="ctx_message_undeleted"><span class="contextImg deleteImg"></span><?php echo _("Undelete") ?></a>
<?php endif; ?>
</div>

<div class="context" id="ctx_draft" style="display:none">
 <a id="ctx_draft_resume"><span class="contextImg draftImg"></span><?php echo _("Resume Draft") ?></a>
 <div class="sep"></div>
 <a id="ctx_draft_flagged"><span class="contextImg mailflaggedImg"></span><?php echo _("Flag Message") ?></a>
 <a id="ctx_draft_clear"><span class="contextImg clearflagImg"></span><?php echo _("Clear Flag") ?></a>
 <div class="sep"></div>
 <a id="ctx_draft_deleted"><span class="contextImg deleteImg"></span><?php echo _("Delete") ?></a>
<?php if (!$usetrash): ?>
 <a id="ctx_draft_undeleted"><span class="contextImg deleteImg"></span><?php echo _("Undelete") ?></a>
<?php endif; ?>
</div>

<div class="context" id="ctx_reply" style="display:none;">
 <a id="ctx_reply_reply"><span class="contextImg replyallImg"></span><?php echo _("To Sender") ?></a>
 <a id="ctx_reply_reply_all"><span class="contextImg replyallImg"></span><?php echo _("To All") ?></a>
 <a id="ctx_reply_reply_list"><span class="contextImg replyallImg"></span><?php echo _("To List") ?></a>
</div>

<div class="context" id="ctx_forward" style="display:none">
 <a id="ctx_forward_forward_all"><span class="contextImg forwardImg"></span><?php echo _("Entire Message") ?></a>
 <a id="ctx_forward_forward_body"><span class="contextImg forwardImg"></span><?php echo _("Body Text Only") ?></a>
 <a id="ctx_forward_forward_attachments"><span class="contextImg forwardImg"></span><?php echo _("Attachments Only") ?></a>
</div>

<div class="context" id="ctx_otheractions" style="display:none">
 <a id="previewtoggle"><span class="contextImg previewImg"></span><?php echo ($prefs->getValue('dimp_show_preview') ? _("Hide Preview") : _("Show Preview")); ?></a>
 <div class="sep"></div>
 <a id="oa_seen"><span class="contextImg mailseenImg"></span><?php echo _("Mark as Read") ?></a>
 <a id="oa_unseen"><span class="contextImg mailunseenImg"></span><?php echo _("Mark as New") ?></a>
 <a id="oa_flagged"><span class="contextImg mailflaggedImg"></span><?php echo _("Flag Message") ?></a>
 <a id="oa_clear"><span class="contextImg clearflagImg"></span><?php echo _("Clear Flag") ?></a>
 <div class="sep" id="oa_sep1"></div>
<?php if ($has_blacklist || $has_whitelist): ?>
<?php if ($has_blacklist): ?>
 <a id="oa_blacklist"><span class="contextImg blacklistImg"></span><?php echo _("Blacklist") ?></a>
<?php endif; ?>
<?php if ($has_whitelist): ?>
 <a id="oa_whitelist"><span class="contextImg whitelistImg"></span><?php echo _("Whitelist") ?></a>
<?php endif; ?>
 <div class="sep" id="oa_sep2"></div>
<?php endif; ?>
 <a id="oa_selectall"><span class="contextImg tickImg"></span><?php echo _("Select All") ?></a>
<?php if (!$usetrash): ?>
 <div class="sep"></div>
 <a id="oa_purge_deleted"><span class="contextImg deleteImg"></span><?php echo _("Purge Deleted") ?></a>
 <a id="oa_undeleted"><span class="contextImg deleteImg"></span><?php echo _("Undelete") ?></a>
<?php endif; ?>
</div>

<div class="context" id="ctx_contacts" style="display:none">
 <div><?php _createDA(_("New Message"), 'compose.png', 'ctx_contacts_new') ?></div>
 <div><?php _createDA(_("Add to Address Book"), 'add_contact.png', 'ctx_contacts_add') ?></div>
</div>

<div style="display:none">
 <span id="popdown_img" class="iconImg popdownImg popdown"></span>
 <span id="high_priority_img" class="iconImg highpriorityImg" title="<?php echo htmlspecialchars(_("High Priority")) ?>"></span>
 <span id="low_priority_img" class="iconImg lowpriorityImg" title="<?php echo htmlspecialchars(_("Low Priority")) ?>"></span>
 <?php foreach ($thread_imgs as $val) { echo $val; } ?>
 <?php foreach ($atc_imgs as $val) { echo $val; } ?>
 <span id="largeaddrspan">
  <span class="largeaddrtoggle">
   <span class="largeaddrlist">[<?php echo _("Show Addresses - %d recipients") ?>]</span>
   <span class="largeaddrlist" style="display:none">[<?php echo _("Hide Addresses") ?>]</span>
  </span>
  <span class="dispaddrlist" style="display:none"></span>
 </span>
</div>

<?php if (!$is_ie6): ?>
<div id="alertslog">
 <div style="display:none">
  <ul>
   <li class="noalerts"><strong><?php echo _("No Alerts") ?></strong></li>
  </ul>
 </div>
</div>
<?php endif; ?>

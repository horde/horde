<?php
/**
 * Dynamic view (dimp) index template.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

// Quota information
$show_quota = $session->get('imp', 'imap_quota')
    ? '<span class="used">' . Horde::img('quotauncover.gif', '', array('width' => 99)) . '</span>'
    : '';

function _simpleButton($id, $text, $image, $nodisplay = false)
{
    $ak = Horde::getAccessKey($text, true);
    return '<li class="servicelink"' .
        (strlen($id) ? ' id="' . $id . '"' : '') .
        (strlen($ak) ? ' accesskey="' . $ak . '"' : '') .
        ($nodisplay ? ' style="display:none"' : '') . '>' .
        '<span class="iconImgSidebar ' . $image . '"></span>' .
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
    <?php echo _simpleButton('alertsloglink', _("Alerts _Log"), 'infoIcon') ?>
<?php if ($session->get('imp', 'filteravail')): ?>
    <?php echo _simpleButton('applyfilterlink', _("Apply Filters"), 'filtersIcon') ?>
<?php endif; ?>
   </ul>
<?php if ($show_quota): ?>
   <div id="quota">
    <div id="quota-text"></div>
    <?php echo $show_quota ?>
   </div>
<?php endif; ?>
   <div class="sepfull"></div>
   <ul id="serviceActions">
<?php if (($registry->get('status', 'horde') != 'hidden') && ($registry->get('status', 'horde') != 'notoolbar')): ?>
<?php if (empty($GLOBALS['conf']['menu']['apps_iframe'])): $ak = Horde::getAccessKey(_("_Portal"), true); ?>
    <li class="servicelink" id="portallink">
     <span class="iconImgSidebar hordeIcon"></span>
     <a href="<?php echo Horde::getServiceLink('portal')->remove('ajaxui') ?>"<?php if (strlen($ak)) echo ' accesskey="' . $ak . '"'?>><?php echo Horde::highlightAccessKey(_("_Portal"), $ak) ?></a>
    </li>
<?php endif; ?>
<?php endif; ?>
<?php if (Horde_Menu::showService('prefs')): ?>
    <?php echo _simpleButton('appprefs', _("Preferences"), 'prefsIcon') ?>
<?php endif; ?>
<?php if (Horde_Menu::showService('logout')): ?>
    <?php echo _simpleButton('applogout', _("_Log Out"), 'logoutIcon') ?>
<?php endif; ?>
   </ul>
   <div class="sepfull"></div>
   <div id="foldersLoading"><?php echo _("Loading...") ?></div>
   <div id="foldersSidebar" style="display:none">
    <ul class="mboxlist" id="specialmboxes"></ul>
<?php if ($render_sidebar): ?>
    <div class="sepfull"></div>
    <ul id="sidebar_apps">
     <?php echo $render_sidebar ?>
    </ul>
<?php endif; ?>
<?php if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)): ?>
    <div class="sepfull"></div>
    <ul class="mboxlist" id="normalmboxes">
     <li class="mbox" id="folderopts">
      <span class="iconImgSidebar folderoptsImg"></span>
      <a id="folderopts_link" title="<?php echo _("Folder Actions") ?>"><?php echo _("Folder Actions") ?></a>
     </li>
     <li class="mbox" id="dropbase" style="display:none">
      <span class="iconImgSidebar folderImg"></span>
      <a title="<?php echo _("Move to Base Level") ?>"><?php echo _("Move to Base Level") ?></a>
     </li>
     <div class="sepfull sepfulllight"></div>
    </ul>
<?php endif; ?>
   </div>
  </div>

  <div id="dimpmain">
   <div id="dimpmain_iframe"><?php echo _("Loading...") ?></div>
   <div id="dimpmain_folder" style="display:none">
    <div id="dimpmain_folder_top">
     <div class="header mboxheader">
      <div>
       <span class="rightFloat" id="msgHeader"></span>
       <span id="mailboxName"></span>
       <span class="iconImg readonlyImg" style="display:none" title="<?php echo htmlspecialchars(_("Read-Only")) ?>"></span>
      </div>
     </div>

     <div class="dimpActions">
<?php if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)): ?>
      <div id="qsearch">
       <span id="qsearch_icon">
        <span class="iconImg searchImg"></span>
        <span class="iconImg popdownImg"></span>
       </span>
       <form action="#" method="post">
        <input autocomplete="off" id="qsearch_input" type="text" />
       </form>
      </div>
      <div id="filter"><?php echo IMP_Dimp::actionButton(array('icon' => 'Filter', 'id' => 'button_filter', 'title' => _("Filter"))) ?></div>
<?php endif; ?>
      <div><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Reply', 'id' => 'button_reply', 'title' => _("Reply"))) ?></div>
      <div><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Forward', 'id' => 'button_forward', 'title' => _("Forward"))) ?></div>
<?php if (!empty($conf['spam']['reporting'])): ?>
      <div style="display:none"><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Spam', 'id' => 'button_spam', 'title' => _("Spam"))) ?></div>
<?php endif; ?>
<?php if (!empty($conf['notspam']['reporting'])): ?>
      <div style="display:none"><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Ham', 'id' => 'button_ham', 'title' => _("Innocent"))) ?></div>
<?php endif; ?>
      <div style="display:none"><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Resume', 'id' => 'button_resume', 'title' => _("Resume"))) ?></div>
      <div style="display:none"><?php echo IMP_Dimp::actionButton(array('icon' => 'Resume', 'id' => 'button_template', 'title' => _("Use Template"))) ?></div>
      <div><?php echo IMP_Dimp::actionButton(array('class' => 'noselectDisable', 'icon' => 'Delete', 'id' => 'button_delete', 'title' => _("Delete"))) ?></div>
      <div id="button_compose"><?php echo IMP_Dimp::actionButton(array('icon' => 'Compose', 'title' => _("Compose"))) ?></div>
      <div id="button_checkmail"><?php echo IMP_Dimp::actionButton(array('icon' => 'Checkmail', 'title' => _("Get Mail"))) ?></div>
      <div><?php echo IMP_Dimp::actionButton(array('icon' => 'Other', 'id' => 'button_other', 'title' => _("Other"))) ?></div>
     </div>

     <div id="searchbar" style="display:none">
      <span class="iconImg closeImg" id="search_close" title="<?php echo _("Clear Search") ?>"></span>
      <span class="iconImg dimpactionRefresh" id="search_refresh" title="<?php echo _("Refresh Search Results") ?>"></span>
      <span class="iconImg dimpactionEditsearch" id="search_edit" style="display:none" title="<?php echo _("Edit Search Query") ?>"></span>
      <span id="search_time_elapsed" style="display:none"></span>
      <div id="search_label"></div>
     </div>
    </div>

    <div id="msgSplitPane"></div>

    <div id="previewPane" style="display:none">
     <div id="previewInfo" style="display:none"></div>
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
         <div style="display:none">
          <span id="msg_resume_draft">
           <span class="iconImg"></span>
           <a><?php echo _("Resume Draft") ?></a>
          </span>
         </div>
         <div style="display:none">
          <span id="msg_template">
           <span class="iconImg"></span>
           <a><?php echo _("Use Template") ?></a>
          </span>
         </div>
         <div>
          <span id="preview_other_opts">
           <span class="iconImg dimpactionOther"></span>
           <a><?php echo _("Other Options") ?></a>
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
           <tr id="msgHeaderBcc">
            <td class="label"><?php echo _("Bcc") ?>:</td>
            <td class="bcc"></td>
           </tr>
           <tr id="msgAtc" style="display:none">
            <td class="label" id="partlist_toggle">
             <span class="iconImg attachmentImg attachmentImage"></span>
             <span class="iconImg" id="partlist_col"></span>
             <span class="iconImg" id="partlist_exp" style="display:none"></span>
            </td>
            <td>
             <div></div>
             <div id="partlist" style="display:none"></div>
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

<div id="helptext">
 <div style="display:none">
  <span class="iconImg closeImg" id="helptext_close" title="<?php echo _("Close") ?>"></span>
  <?php echo _("To preview a message, select it from the message list.") ?>
  <br />
<?php if ($browser->isBrowser('opera')): ?>
  <?php echo _("A left click") ?> + <span class="kbd"><?php echo _("Shift") ?></span> + <span class="kbd"><?php echo _("Ctrl") ?></span> <?php echo _("will display available actions.") ?>
<?php else: ?>
  <?php echo _("A right-click on a message or a mailbox will display available actions.") ?>
<?php endif; ?>
  <br />
  <?php printf(_("Click on a message while holding down the %s key to select multiple messages.  To select a range of messages, click the first message of the range, navigate to the last message of the range, and then click on the last message while holding down the %s key."), '<span class="kbd">' . _("Ctrl") . '</span>', '<span class="kbd">' . _("Shift") . '</span>') ?><br /><br />
  <?php echo _("The following keyboard shortcuts are available:") ?><br />
  <span class="iconImg keyupImg"></span> / <span class="iconImg keydownImg"></span> : <?php echo _("Move up/down through the message list.") ?><br />
  <span class="kbd"><?php echo _("PgUp") ?></span> / <span class="kbd"><?php echo _("PgDown") ?></span> : <?php echo _("Move one page up/down through the message list.") ?><br />
  <span class="kbd"><?php echo _("Alt") ?></span> + <span class="kbd"><?php echo _("PgUp") ?></span> / <span class="kbd"><?php echo _("PgDown") ?></span> : <?php echo _("Scroll up/down through the display of the previewed message.") ?><br />
  <span class="kbd"><?php echo _("Home") ?></span> / <span class="kbd"><?php echo _("End") ?></span> : <?php echo  _("Move to the beginning/end of the message list.") ?><br />
  <span class="kbd"><?php echo _("Del") ?></span> : <?php echo _("Delete the currently selected message(s).") ?> <?php printf(_("%s will delete the current message and move to the next message if a single message is selected."), '<span class="kbd">' . _("Shift") . '</span> + <span class="kbd">' . _("Del") . '</span>') ?><br />
  <span class="kbd"><?php echo _("Shift") ?></span> + <span class="kbd"><?php echo _("N") ?></span> : <?php echo _("Move to the next unseen message (non-search mailboxes only).") ?><br />
  <span class="kbd"><?php echo _("Enter") ?></span> : <?php echo _("Open message in a popup window.") ?><br />
  <span class="kbd"><?php echo _("Ctrl") ?></span> + <span class="kbd"><?php echo 'A' ?></span> : <?php echo _("Select all messages in the current mailbox.") ?>
 </div>
</div>

<div id="messageBodyError">
 <table class="mimeStatusMessageTable" style="display:none">
  <tr>
   <td><?php echo _("Unable to view message in preview pane.") ?></td>
  </tr>
  <tr>
   <td><a href="#" class="messageBodyErrorLink"><?php echo _("Click HERE to view the message in a new window.") ?></a></td>
  </tr>
 </table>
</div>

<?php require IMP_TEMPLATES . '/dimp/common.inc' ?>

<div id="msglistHeaderContainer">
 <div class="item msglistHeader" id="msglistHeaderHoriz" style="display:none">
  <div class="iconImg msgStatus">
   <div class="iconImg msCheckAll msCheck" id="horiz_opts" title="<?php echo _("Select All") ?>"></div>
  </div>
  <div class="msgFrom sep"></div>
  <div class="msgSubject sep"></div>
  <div class="msgDate sep"></div>
  <div class="msgSize sep"></div>
 </div>
 <div class="item msglistHeader" id="msglistHeaderVert" style="display:none">
  <div class="iconImg msgStatus">
   <div class="iconImg msCheckAll msCheck" title="<?php echo _("Select All") ?>"></div>
  </div>
  <div class="msgSort">
   <a class="widget" id="vertical_sort"><?php echo _("Sort") ?></a>
  </div>
 </div>
</div>

<?php
Horde::includeScriptFiles();
Horde::outputInlineScript();
?>

</body></html>

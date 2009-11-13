<?php
/**
 * Dynamic view (dimp) compose template.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */
?>
<div id="dimpLoading"><?php echo _("Loading...") ?></div>
<div id="pageContainer" style="display:none">
 <div id="msgData">
  <div class="dimpActions dimpActionsMsg">
   <div class="headercloseimg closeImg" id="windowclose" title="X"></div>
   <div><?php echo IMP_Dimp::actionButton(array('class' => 'hasmenu', 'icon' => 'Reply', 'id' => 'reply_link', 'title' => _("Reply"))) ?></div>
   <div><?php echo IMP_Dimp::actionButton(array('icon' => 'Forward', 'id' => 'forward_link', 'title' => _("Forward"))) ?></div>
<?php if (!empty($conf['spam']['reporting']) && (!$conf['spam']['spamfolder'] || ($folder != IMP::folderPref($prefs->getValue('spam_folder'), true)))): ?>
   <div><?php echo IMP_Dimp::actionButton(array('icon' => 'Spam', 'id' => 'button_spam', 'title' => _("Report Spam"))) ?></div>
<?php endif; ?>
<?php if (!empty($conf['notspam']['reporting']) && (!$conf['notspam']['spamfolder'] || ($folder == IMP::folderPref($prefs->getValue('spam_folder'), true)))): ?>
   <div><?php echo IMP_Dimp::actionButton(array('icon' => 'Ham', 'id' => 'button_ham', 'title' => _("Report Innocent"))) ?></div>
<?php endif; ?>
<?php if (!$readonly): ?>
   <div><?php echo IMP_Dimp::actionButton(array('icon' => 'Delete', 'id' => 'button_deleted', 'title' => _("Delete"))) ?></div>
<?php endif; ?>
  </div>

  <div class="msgfullread">
   <div class="msgHeaders">
    <div id="msgHeaders">
     <div class="dimpOptions">
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
      <div>
       <span>
        <span class="iconImg saveAsImg"></span>
        <a href="<?php echo $show_msg_result['save_as'] ?>"><?php echo _("Save") ?></a>
       </span>
      </div>
     </div>
     <div id="msgHeadersContent">
      <table>
       <thead>
        <tr>
         <td class="label"><?php echo _("Subject") ?>:</td>
         <td class="subject"><?php echo $show_msg_result['subject'] ?></td>
        </tr>
<?php foreach($show_msg_result['headers'] as $val): ?>
        <tr<?php if (isset($val['id'])): ?> id="msgHeader<?php echo $val['id'] ?>"<?php endif; ?>>
         <td class="label"><?php echo $val['name'] ?>:</td>
         <td><?php echo $val['value'] ?></td>
        </tr>
<?php endforeach; ?>
<?php if (isset($show_msg_result['atc_label'])): ?>
        <tr id="msgAtc">
         <td class="label"><?php if ($show_msg_result['atc_list']): ?><span class="iconImg attachmentImg attachmentImage"></span><a id="partlist_toggle"><span id="partlist_col" class="iconImg"></span><span id="partlist_exp" class="iconImg" style="display:none"></span></a><?php else: ?><span class="iconImg attachmentImg attachmentImage"></span><?php endif; ?></td>
         <td>
          <span class="atcLabel"><?php echo $show_msg_result['atc_label'] ?></span><?php echo isset($show_msg_result['atc_download']) ? $show_msg_result['atc_download'] : '' ?>
<?php if (isset($show_msg_result['atc_list'])): ?>
          <div id="partlist" style="display:none">
           <table>
            <?php echo $show_msg_result['atc_list'] ?>
           </table>
          </div>
<?php endif; ?>
         </td>
        </tr>
<?php endif; ?>
        <tr id="msgLogInfo" style="display:none">
         <td class="label"><a id="msgloglist_toggle"><span class="iconImg" id="msgloglist_col"></span><span class="iconImg" id="msgloglist_exp" style="display:none"></span></a></td>
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
   <div class="messageBody">
    <?php echo $show_msg_result['msgtext'] ?>
   </div>
  </div>
 </div>

<?php if (!$disable_compose): ?>
 <div id="qreply" style="display:none">
  <div class="header">
   <div class="headercloseimg" id="compose_close">
    <span class="closeImg" title="X"></span>
   </div>
   <div><?php echo _("Message:") . ' ' . $show_msg_result['subject'] ?></div>
  </div>
  <?php echo $compose_result['html']; ?>
 </div>
</div>

<div class="context" id="ctx_replypopdown" style="display:none">
 <a id="ctx_reply_reply"><span class="contextImg"></span><?php echo _("To Sender") ?></a>
 <a id="ctx_reply_reply_all"><span class="contextImg"></span><?php echo _("To All") ?></a>
<?php if ($show_msg_result['list_info']['exists']): ?>
 <a id="ctx_reply_reply_list"><span class="contextImg"></span><?php echo _("To List") ?></a>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="context" id="ctx_contacts" style="display:none">
 <a id="ctx_contacts_new"><span class="contextImg"></span><?php echo _("New Message") ?></a>
 <a id="ctx_contacts_add"><span class="contextImg"></span><?php echo _("Add to Address Book") ?></a>
</div>

<div style="display:none">
 <span id="largeaddrspan">
  <span class="largeaddrtoggle">
   <span class="largeaddrlist">[<?php echo _("Show Addresses - %d recipients") ?>]</span>
   <span class="largeaddrlist" style="display:none">[<?php echo _("Hide Addresses") ?>]</span>
  </span>
  <span class="dispaddrlist" style="display:none"></span>
 </span>
</div>

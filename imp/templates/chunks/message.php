<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

$horde_img = $registry->getImageDir('horde');
$menu_view = $prefs->getValue('menu_view');
$show_text = ($menu_view == 'text' || $menu_view == 'both');

// Small utility function to simplify creating dimpactions buttons.
// As of right now, we don't show text only links.
function _createDAfmsg($text, $image, $id, $class = '', $show_text = true)
{
    $params = array('icon' => $image, 'id' => $id, 'class' => $class);
    if ($show_text) {
        $params['title'] = $text;
    } else {
        $params['tooltip'] = $text;
    }
    echo DIMP::actionButton($params);
}

?>
<div id="pageContainer">
 <div id="msgData">
  <div class="noprint">
   <div class="header">
    <div class="headercloseimg" id="windowclose"><?php echo IMP::img('close.png', 'X', array(), $horde_img) ?></div>
    <div><?php echo _("Message:") . ' ' . $show_msg_result['subject'] ?></div>
   </div>

   <div class="dimpActions dimpActionsMsg">
    <span id="button_reply_cont"><?php _createDAfmsg(_("Reply"), 'reply_menu.png', 'reply_link', 'hasmenu', $show_text) ?></span>
    <span id="button_forward_cont"><?php _createDAfmsg(_("Forward"), 'forward_menu.png', 'forward_link', 'hasmenu', $show_text) ?></span>
<?php if (!empty($conf['spam']['reporting']) && (!$conf['spam']['spamfolder'] || ($folder != IMP::folderPref($prefs->getValue('spam_folder'), true)))): ?>
    <span><?php _createDAfmsg(_("Report Spam"), 'spam_menu.png', 'button_spam', '', $show_text) ?></span>
<?php endif; ?>
<?php if (!empty($conf['notspam']['reporting']) && (!$conf['notspam']['spamfolder'] || ($folder == IMP::folderPref($prefs->getValue('spam_folder'), true)))): ?>
    <span><?php _createDAfmsg(_("Report Innocent"), 'ham_menu.png', 'button_ham', '', $show_text) ?></span>
<?php endif; ?>
    <span><?php _createDAfmsg(_("Delete"), 'delete_menu.png', 'button_deleted', '', $show_text) ?></span>
   </div>
  </div>

  <div class="msgfullread">
   <div class="msgHeaders">
    <div id="msgHeaders">
     <div class="dimpOptions noprint">
      <div id="msg_print"><?php echo IMP::img('print.png', '', '', $horde_img) ?><a><?php echo _("Print") ?></a></div>
<?php if (!empty($conf['user']['allow_view_source'])): ?>
      <div id="msg_view_source"><span class="iconImg"></span><a><?php echo _("View Message Source") ?></a></div>
<?php endif; ?>
     </div>
     <div id="msgHeadersContent">
      <table cellspacing="0">
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
         <td class="label"><?php if ($show_msg_result['atc_list']): ?><a id="partlist_toggle"><span id="partlist_col" class="iconImg"></span><span id="partlist_exp" class="iconImg" style="display:none"></span> <span class="iconImg attachmentImg attachmentImage"></span></a><?php else: ?><span class="iconImg attachmentImg attachmentImage"></span><?php endif; ?></td>
         <td>
          <span class="atcLabel"><?php echo $show_msg_result['atc_label'] ?></span><?php echo $show_msg_result['atc_download'] ?>
<?php if (isset($show_msg_result['atc_list'])): ?>
          <div id="partlist" style="display:none">
           <table cellspacing="2">
            <?php echo $show_msg_result['atc_list'] ?>
           </table>
          </div>
<?php endif; ?>
         </td>
        </tr>
<?php endif; ?>
       </thead>
      </table>
     </div>
    </div>
   </div>
   <div class="msgBody">
    <table width="100%" cellspacing="0">
     <?php echo $show_msg_result['msgtext'] ?>
    </table>
   </div>
  </div>
 </div>

 <div id="qreply" style="display:none">
  <div class="header">
   <div class="headercloseimg"><?php echo IMP::img('close.png', 'X', array(), $horde_img) ?></div>
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

<div class="context" id="ctx_fwdpopdown" style="display:none">
 <a id="ctx_forward_forward_all"><span class="contextImg"></span><?php echo _("Entire Message") ?></a>
 <a id="ctx_forward_forward_body"><span class="contextImg"></span><?php echo _("Body Text Only") ?></a>
 <a id="ctx_forward_forward_attachments"><span class="contextImg"></span><?php echo _("Attachments Only") ?></a>
</div>

<div class="context" id="ctx_contacts" style="display:none">
 <a id="ctx_contacts_new"><span class="contextImg"></span><?php echo _("New Message") ?></a>
 <a id="ctx_contacts_add"><span class="contextImg"></span><?php echo _("Add to Address Book") ?></a>
</div>

<span id="popdown_img" class="iconImg popdownImg popdown" style="display:none"></span>

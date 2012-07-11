<form id="compose" name="compose" enctype="multipart/form-data" action="<?php echo $this->compose_link ?>" method="post" style="display:none">
 <?php echo $this->hiddenFieldTag('html', intval($this->rte && $this->compose_html)) ?>
 <?php echo $this->hiddenFieldTag('composeCache') ?>
 <?php echo $this->hiddenFieldTag('request_read_receipt', intval($this->read_receipt_set)) ?>
 <?php echo $this->hiddenFieldTag('save_attachments_select', intval($this->save_attach_set)) ?>

 <div class="horde-buttonbar">
  <div class="iconImg headercloseimg closeImg" id="compose_close"></div>
<?php if ($this->compose_enable): ?>
<?php if (!$this->is_template): ?>
  <div>
   <?php echo $this->actionButton(array('icon' => 'Forward', 'id' => 'send_button', 'title' => _("Send"))) ?>
   <div class="horde-button-split"></div>
  </div>
<?php endif; ?>
<?php endif; ?>
<?php if ($this->is_template): ?>
  <div>
   <?php echo $this->actionButton(array('icon' => 'Templates', 'id' => 'template_button', 'title' => _("Save Template"))) ?>
   <div class="horde-button-split"></div>
  </div>
<?php else: ?>
  <div>
   <?php echo $this->actionButton(array('id' => 'spellcheck', 'title' => _("Check Spelling"))) ?>
   <div class="horde-button-split"></div>
  </div>
  <div>
   <?php echo $this->actionButton(array('icon' => 'Drafts', 'id' => 'draft_button', 'title' => _("Save as Draft"))) ?>
   <div class="horde-button-split"></div>
  </div>
<?php endif; ?>
 </div>

 <div id="writemsg">
  <div class="msgwrite">
   <div class="dimpOptions">
<?php if ($this->rte): ?>
    <div>
     <label>
     <?php echo $this->checkBoxTag('htmlcheckbox', 1, $this->compose_html, array('class' => 'checkbox')) . _("HTML composition") ?>
     </label>
    </div>
<?php endif; ?>
<?php if ($this->save_sent_mail): ?>
    <div style="display:none">
     <label>
      <?php echo $this->checkBoxTag('save_sent_mail', 1, false, array('class' => 'checkbox')) . _("Save in") ?>
      <span id="sent_mail_label"></span>
     </label>
     <?php echo $this->hiddenFieldTag('save_sent_mail_mbox') ?>
    </div>
<?php endif; ?>
<?php if ($this->priority): ?>
    <div>
     <?php echo _("Priority") ?>:<span id="priority_label"></span>
     <?php echo $this->hiddenFieldTag('priority', 'normal') ?>
    </div>
<?php endif; ?>
<?php if ($this->encrypt): ?>
    <div>
     <?php echo _("Encryption") ?>:<span id="encrypt_label"></span>
     <?php echo $this->hiddenFieldTag('encrypt', $this->encrypt) ?>
    </div>
<?php endif; ?>
    <div>
     <span id="msg_other_options">
      <a><?php echo _("Other Options") ?></a>
     </span>
    </div>
   </div>

<?php if (strlen($this->title)): ?>
   <p class="p17 bold"><?php echo $this->h($this->title) ?></p>
   <p>&nbsp;</p>
<?php endif; ?>

   <table>
    <tr>
     <td class="label"><?php echo _("From") ?>:</td>
     <td>
      <select id="identity" name="identity">
<?php foreach ($this->select_list as $v): ?>
       <?php echo $this->optionTag($v['val'], $this->h($v['label']), $v['sel']) ?>
<?php endforeach; ?>
      </select>
    </td>
    </tr>
    <tr id="sendto">
     <td class="label">
      <span><?php echo _("To") ?>:</span>
     </td>
     <td class="sendtextarea">
      <?php echo $this->textAreaTag('to', null, array('size' => '75x1')) ?>
      <span id="to_loading_img" class="loadingImg" style="display:none"></span>
     </td>
    </tr>
<?php if ($this->cc): ?>
    <tr id="sendcc" style="display:none">
     <td class="label">
      <span><?php echo _("Cc") ?>:</span>
     </td>
     <td class="sendtextarea">
      <?php echo $this->textAreaTag('cc', null, array('size' => '75x1')) ?>
      <span id="cc_loading_img" class="loadingImg" style="display:none"></span>
     </td>
    </tr>
<?php endif; ?>
<?php if ($this->bcc): ?>
    <tr id="sendbcc" style="display:none">
     <td class="label">
      <span><?php echo _("Bcc") ?>:</span>
     </td>
     <td class="sendtextarea">
      <?php echo $this->textAreaTag('bcc', null, array('size' => '75x1')) ?>
      <span id="bcc_loading_img" class="loadingImg" style="display:none"></span>
     </td>
    </tr>
<?php endif; ?>
<?php if ($this->cc || $this->bcc): ?>
    <tr>
     <td></td>
     <td>
<?php if ($this->cc): ?>
      <span id="togglecc"><?php echo _("Add Cc") ?></span>
<?php endif; ?>
<?php if ($this->bcc): ?>
      <span id="togglebcc"><?php echo _("Add Bcc") ?></span>
<?php endif; ?>
     </td>
    </tr>
<?php endif; ?>
    <tr>
     <td class="label"><?php echo _("Subject")?>:</td>
     <td class="subject">
      <?php echo $this->textFieldTag('subject') ?>
     </td>
    </tr>
    <tr class="atcrow">
     <td class="label">
      <span class="iconImg attachmentImg"></span>:
     </td>
     <td>
      <span id="upload_limit" style="display:none"><?php echo _("The attachment limit has been reached.") ?></span>
      <span id="upload_wait" style="display:none"></span>
      <span>
       <?php echo $this->fileFieldTag('file_1', array('id' => 'upload')) ?>
      </span>
      <ul id="attach_list" style="display:none"></ul>
     </td>
    </tr>
    <tr id="noticerow" style="display:none">
     <td colspan="2">
      <ul class="notices">
       <li id="replyallnotice" style="display:none">
        <?php echo _("You are") ?> <span class="replyAllNoticeUnderline"><?php echo _("replying to ALL") ?></span> (<span class="replyAllNoticeCount"></span>).
        <input id="replyall_revert" class="button" type="button" value="<?php echo _("Reply To Sender instead") ?>" />
       </li>
       <li id="replylistnotice" style="display:none">
        <?php echo _("You are replying to a mailing list") ?><span class="replyListNoticeId"></span>.
        <input id="replylist_revert" class="button" type="button" value="<?php echo _("Reply To Sender instead") ?>" />
       </li>
       <li id="fwdattachnotice" style="display:none">
        <?php echo _("Click this box to add the original message text to the body.") ?>
       </li>
       <li id="fwdbodynotice" style="display:none">
        <?php echo _("Click this box to add the original message as an attachment.") ?>
       </li>
       <li id="identitychecknotice" style="display:none">
        <?php echo _("Your identity has been switched to the identity associated with the current recipient address. Click this box to revert to the original identity. The identity will not be checked again during this compose action.") ?>
       </li>
       <li id="langnotice" style="display:none">
        <?php echo _("The recipient has indicated that they prefer replies in these languages") ?>: <span class="langNoticeList"></span>.
       </li>
      </ul>
     </td>
    </tr>
   </table>
  </div>

  <div id="composeMessageParent">
   <textarea name="message" id="composeMessage" class="fixed"></textarea>
  </div>
 </div>
</form>

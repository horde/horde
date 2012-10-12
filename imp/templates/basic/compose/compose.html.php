<form method="post" id="compose" name="compose"<?php if ($this->file_upload): ?>enctype="multipart/form-data" <?php endif; ?> action="<?php echo $this->post_action ?>">
<?php foreach ($this->hidden as $k => $v): ?>
 <?php echo $this->hiddenFieldTag($k, $v) ?>
<?php endforeach; ?>
 <?php echo $this->forminput ?>

 <h1 class="header">
  <span class="rightFloat">
   <?php echo $this->hordeHelp('imp', 'compose-buttons') ?>
  </span>
  <?php echo $this->h($this->title) ?>
 </h1>

 <br />

 <?php echo $this->status ?>

 <table cellspacing="0">
  <tr>
   <td></td>
   <td class="nowrap">
<?php if ($this->send_msg): ?>
<?php if ($this->allow_compose): ?>
    <?php echo $this->submitTag(_("Send Message"), array_merge(array('name' => 'btn_send_message', 'class' => 'horde-default'), $this->hordeAccessKeyAndTitle(_("_Send Message"), false, true))) ?>
<?php endif; ?>
<?php if ($this->save_draft): ?>
    <?php echo $this->submitTag(_("Save Draft"), array_merge(array('name' => 'btn_save_draft'), $this->hordeAccessKeyAndTitle(_("Save _Draft"), false, true))) ?>
<?php endif; ?>
<?php else: ?>
    <?php echo $this->submitTag(_("Save Template"), array('name' => 'btn_save_template')) ?>
<?php endif; ?>
    <?php echo $this->submitTag(_("Cancel Message"), array('class' => 'horde-cancel', 'name' => 'btn_cancel_compose', 'title' => _("Cancel Message"))) ?>
   </td>
  </tr>

  <tr>
   <td class="light rightAlign">
   <strong><?php echo $this->di_locked ? _("From") : $this->hordeLabel('identity', _("_Identity")) ?></strong>
   </td>
   <td class="item">
<?php if ($this->di_locked): ?>
<?php if ($this->fromaddr_locked): ?>
    <strong><?php echo $this->h($this->from) ?></strong>
<?php else: ?>
    <input id="text_identity" type="text" tabindex="<?php echo ++$this->tabindex ?>" name="from" value="<?php echo $this->h($this->from) ?>" style="direction:ltr" />
<?php endif; ?>
<?php else: ?>
    <input type="hidden" id="last_identity" name="last_identity" value="<?php echo $this->last_identity ?>" />
<?php if ($this->count_select_list): ?>
    <select id="identity" name="identity" tabindex="<?php echo ++$this->tabindex ?>">
<?php foreach ($this->select_list as $v): ?>
     <?php echo $this->optionTag($v['value'], $v['label'], $v['selected']) ?>
<?php endforeach; ?>
   </select>
<?php else: ?>
    <input type="hidden" name="identity" value="<?php echo $this->identity_default ?>" />
    <?php echo $this->h($this->identity_text) ?>
<?php endif; ?>
<?php endif; ?>
   </td>
  </tr>

<?php foreach ($this->addr as $v): ?>
  <tr>
   <td class="light rightAlign">
    <strong><?php echo $this->hordeLabel($v['id'], $v['label']) ?></strong>
   </td>
   <td class="item addressTr">
    <?php echo $this->textFieldTag($v['id'], $v['val'], array('autocomplete' => 'off', 'tabindex' => ++$this->tabindex, 'style' => 'direction:ltr')) ?>
    <span id="<?php echo $v['id'] ?>_loading_img" style="display:none" class="loadingImg"></span>
   </td>
  </tr>
<?php endforeach; ?>

  <tr>
   <td class="light rightAlign">
    <strong><?php echo $this->hordeLabel('subject', _("S_ubject")) ?></strong>
   </td>
   <td class="item">
    <?php echo $this->textFieldTag('subject', $this->subject, array('tabindex' => ++$this->tabindex)) ?>
   </td>
  </tr>

<?php if ($this->set_priority): ?>
  <tr>
   <td class="light rightAlign">
    <strong><?php echo $this->hordeLabel('priority', _("_Priority")) ?></strong>
   </td>
   <td class="item">
    <select id="priority" name="priority" tabindex="<?php echo ++$this->tabindex ?>">
<?php foreach ($this->pri_opt as $v): ?>
     <?php echo $this->optionTag($v['val'], $v['label'], $v['selected']) ?>
<?php endforeach; ?>
    </select>
   </td>
  </tr>
<?php endif; ?>

  <tr>
   <td></td>
   <td class="item">
    <table width="100%" cellspacing="0">
     <tr>
<?php foreach ($this->compose_options as $v): ?>
      <td align="center">
       <?php echo $v['url'] . $v['img'] ?>
       <br />
       <?php echo $v['label'] ?></a>
      </td>
<?php endforeach; ?>
     </tr>
    </table>
   </td>
  </tr>

<?php if ($this->ssm): ?>
  <tr>
   <td></td>
   <td class="item">
    <?php echo $this->checkBoxTag('save_sent_mail', 1, $this->ssm_selected, array('class' => 'checkbox')) ?>
    <?php echo $this->hordeLabel('ssm', _("Sa_ve a copy in")) ?>
<?php if ($this->ssm_mboxes): ?>
    <select tabindex="<?php echo ++$this->tabindex ?>" id="sent_mail" name="sent_mail">
     <?php echo $this->ssm_mboxes ?>
    </select>
<?php else: ?>
    <span id="sent_mail"><?php echo $this->ssm_mbox ?></span>
<?php endif; ?>
   </td>
  </tr>
<?php endif; ?>

<?php if ($this->rrr): ?>
  <tr>
   <td></td>
   <td class="item">
    <?php echo $this->checkBoxTag('request_read_receipt', 1, $this->rrr_selected, array('class' => 'checkbox')) ?>
    <?php echo $this->hordeLabel('rrr', _("Request a _Read Receipt")) ?>
   </td>
  </tr>
<?php endif; ?>

<?php if ($this->compose_html): ?>
  <tr>
   <td></td>
   <td class="item">
    <?php echo $this->hordeImage('compose.png', _("Switch Composition Method")) ?>
    <?php echo $this->html_switch . ($this->rtemode ? _("Switch to plain text composition") : _("Switch to HTML composition")) ?></a>
   </td>
  </tr>
<?php endif; ?>

<?php if ($this->replyauto_all): ?>
  <tr>
   <td></td>
   <td class="item">
    <span class="notices">
     <li id="replyallnotice">
      <?php echo _("You are") ?> <span class="replyAllNoticeUnderline"><?php echo _("replying to ALL") ?></span> (<?php echo $this->replyauto_all ?> <?php echo _("recipients") ?>).
      <input name="btn_replyall_revert" type="submit" value="<?php echo _("Reply To Sender instead") ?>" />
     </li>
    </span>
   </td>
  </tr>
<?php endif; ?>

<?php if ($this->replyauto_list): ?>
  <tr>
   <td></td>
   <td class="item">
    <span class="notices">
     <li id="replylistnotice">
      <?php echo _("You are replying to a mailing list") . ($this->replyauto_list_id ? '(' . $this->replyauto_list_id . ')' : '') ?>
      <input name="btn_replylist_revert" type="submit" value="<?php echo _("Reply To Sender instead") ?>" />
     </li>
    </span>
   </td>
  </tr>
<?php endif; ?>

<?php if ($this->reply_lang): ?>
  <tr>
   <td></td>
   <td class="item">
    <span class="notices">
     <li id="langnotice">
      <?php echo _("The recipient has indicated that they prefer replies in these languages:") ?>
      <?php echo $this->reply_lang ?>
     </li>
    </span>
   </td>
  </tr>
<?php endif; ?>

  <tr>
   <td class="light rightAlign">
    <strong><?php echo $this->hordeLabel('composeMessage', _("Te_xt")) ?></strong>
   </td>
   <td class="item" id="composeMessageParent">
    <textarea class="fixed composebody" tabindex="<?php echo ++$this->tabindex ?>" name="message" id="composeMessage" rows="20" cols="80">
     <?php echo $this->h($this->message) ?>
    </textarea>
   </td>
  </tr>

  <tr>
   <td></td>
   <td class="nowrap">
<?php if ($this->send_msg): ?>
<?php if ($this->allow_compose): ?>
    <?php echo $this->submitTag(_("Send Message"), array_merge(array('name' => 'btn_send_message', 'class' => 'horde-default'), $this->hordeAccessKeyAndTitle(_("_Send Message"), false, true))) ?>
<?php endif; ?>
<?php if ($this->save_draft): ?>
    <?php echo $this->submitTag(_("Save Draft"), array_merge(array('name' => 'btn_save_draft'), $this->hordeAccessKeyAndTitle(_("Save _Draft"), false, true))) ?>
<?php endif; ?>
<?php else: ?>
    <?php echo $this->submitTag(_("Save Template"), array('name' => 'btn_save_template')) ?>
<?php endif; ?>
    <?php echo $this->submitTag(_("Cancel Message"), array('class' => 'horde-cancel', 'name' => 'btn_cancel_compose', 'title' => _("Cancel Message"))) ?>
   </td>
  </tr>

<?php if ($this->use_encrypt): ?>
  <tr>
   <td></td>
   <td class="item nowrap">
    <?php echo $this->hordeLabel('encrypt_options', _("Encr_yption Options")) ?>:
    <select id="encrypt_options" name="encrypt_options">
     <?php echo $this->encrypt_options ?>
    </select>
   </td>
  </tr>
<?php endif; ?>

<?php if ($this->pgp_options): ?>
  <tr>
   <td></td>
   <td class="item nowrap">
    <?php echo $this->checkBoxTag('pgp_attach_pubkey', 1, $this->pgp_attach_pubkey, array('class' => 'checkbox')) ?>
    <?php echo $this->hordeLabel('pgp_attach_pubkey', _("Attach a copy of your PGP public key to the message?")) ?>
   </td>
  </tr>
<?php endif; ?>

<?php if ($this->vcard): ?>
  <tr>
   <td></td>
   <td class="item nowrap">
    <?php echo $this->checkBoxTag('vcard', 1, $this->attach_vcard, array('class' => 'checkbox')) ?>
    <?php echo $this->hordeLabel('vcard', _("Attach your contact information to the message?")) ?>
   </td>
  </tr>
<?php endif; ?>
 </table>

<?php if ($this->file_upload): ?>
 <br />

 <h1 class="header">
  <strong><a id="attachments"></a><?php echo _("Attachments") ?></strong>
 </h1>

 <table width="100%" cellspacing="0">
  <tr class="item" id="upload_atc">
<?php if ($this->maxattachsize): ?>
   <td>
    <?php echo _("Maximum total attachment size reached.") ?>
   </td>
<?php else: ?>
<?php if ($this->maxattachmentnumber): ?>
   <td>
    <?php echo _("Maximum number of attachments reached.") ?>
   </td>
<?php else: ?>
   <td>
    <table>
     <tr id="attachment_row_1">
      <td>
       <strong><label for="upload_1"><?php echo _("File") ?> 1:</label></strong>
       <input id="upload_1" name="upload_1" tabindex="<?php echo ++$this->tabindex ?>" type="file" size="25" />
      </td>
     </tr>
     <tr>
      <td>
       (<?php echo _("Maximum Attachment Size") ?>: <?php echo $this->attach_size ?> <?php echo _("bytes") ?>)
      </td>
     </tr>
    </table>
   </td>
<?php endif; ?>
<?php endif; ?>
   <td class="rightAlign">
    <input type="submit" name="btn_add_attachment" value="<?php echo _("Update") ?>" />
   </td>
  </tr>

<?php if ($this->show_link_save_attach): ?>
<?php foreach ($this->attach_options as $v): ?>
  <tr class="item">
   <td colspan="3">
    <strong><label for="<?php echo $v['name'] ?>"><?php echo $v['label'] ?></label></strong>
    <select id="<?php echo $v['name'] ?>" name="<?php echo $v['name'] ?>">
     <?php echo $this->optionTag(1, _("Yes"), $v['val'] == 1) ?>
     <?php echo $this->optionTag(0, _("No"), $v['val'] == 0) ?>
    </select>
   </td>
  </tr>
<?php endforeach; ?>
<?php endif; ?>
 </table>

<?php if ($this->numberattach): ?>
 <br />

 <div class="smallheader leftAlign">
  <?php echo _("Current Attachments") ?> (<?php echo _("Total Size") ?>: <?php echo $this->total_attach_size ?> <?php echo _("KB") ?><?php echo $this->perc_attach ?>)
 </div>

 <table class="leftAlign attachList">
<?php foreach ($this->atc as $v): ?>
  <tr class="item">
   <td>
    <img style="padding-right:5px" src="<?php echo $v['icon'] ?>" />
    <strong><?php echo $this->h($v['name']) ?></strong>
<?php if ($v['fwdattach']): ?>
    (<strong><?php echo _("Size") ?>:</strong>
    <?php echo $v['size'] . ' ' . _("KB") ?>)
<?php else: ?>
    (<?php echo $this->h($v['type']) ?>)
    <strong><?php echo _("Size") ?>:</strong>
    <?php echo $v['size'] . ' ' . _("KB") ?>
<?php endif; ?>
   </td>
  </tr>
  <tr class="item">
<?php if (!$v['fwdattach']): ?>
   <td style="padding-left:30px">
    <table>
     <tr>
      <td class="rightAlign">
       <strong><label for="file_description_<?php echo $v['number'] ?>"><?php echo _("Description") ?>:</label></strong>
      </td>
      <td>
       <input type="text" size="40" id="file_description_<?php echo $v['number'] ?>" name="file_description_<?php echo $v['number'] ?>" value="<?php echo $this->h($v['description']) ?>" />
      </td>
     </tr>
     <tr>
      <td class="rightAlign">
       <strong><label for="delattachment<?php echo $v['number'] ?>"><?php echo _("Delete?") ?></label></strong>
      </td>
      <td>
       <input type="checkbox" class="checkbox" id="delattachment<?php echo $v['number'] ?>" name="delattachments[]" value="<?php echo $v['number'] ?>" />
      </td>
     </tr>
    </table>
   </td>
<?php endif; ?>
<?php endforeach; ?>
  </tr>
 </table>
<?php endif; ?>
<?php endif; ?>

</form>

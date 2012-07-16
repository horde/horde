<?php echo $this->status ?>

<div id="dimpLoading">
 <?php echo _("Loading...") ?>
</div>

<div id="msgData" style="display:none">
 <div class="horde-buttonbar">
  <div class="iconImg headercloseimg closeImg" id="windowclose" title="X"></div>
  <div>
   <?php echo $this->actionButton(array('icon' => 'Reply', 'id' => 'reply_link', 'title' => _("Reply"))) ?>
   <div class="horde-button-split"></div>
  </div>
  <div>
   <?php echo $this->actionButton(array('icon' => 'Forward', 'id' => 'forward_link', 'title' => _("Forward"))) ?>
   <div class="horde-button-split"></div>
  </div>
<?php if ($this->show_spam): ?>
  <div>
   <?php echo $this->actionButton(array('icon' => 'Spam', 'id' => 'button_spam', 'title' => _("Spam"))) ?>
   <div class="horde-button-split"></div>
  </div>
<?php endif; ?>
<?php if ($this->show_innocent): ?>
  <div>
   <?php echo $this->actionButton(array('icon' => 'Innocent', 'id' => 'button_innocent', 'title' => _("Innocent"))) ?>
   <div class="horde-button-split"></div>
  </div>
<?php endif; ?>
<?php if ($this->show_delete): ?>
  <div>
   <?php echo $this->actionButton(array('icon' => 'Delete', 'id' => 'button_delete', 'title' => _("Delete"))) ?>
   <div class="horde-button-split"></div>
  </div>
<?php endif; ?>
 </div>

 <div class="msgfullread">
  <div class="msgHeaders">
   <div id="msgHeaders">
    <div class="dimpOptions">
<?php if ($this->show_view_source): ?>
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
       <a href="<?php echo $this->save_as ?>"><?php echo _("Save") ?></a>
      </span>
     </div>
<?php if ($this->show_view_all): ?>
     <div>
      <span id="msg_all_parts">
       <span class="iconImg"></span>
       <a><?php echo _("View All Parts") ?></a>
      </span>
     </div>
<?php endif; ?>
    </div>
    <div id="msgHeadersContent">
     <p class="p17 bold"><?php echo $this->subject ?></p>
     <p>&nbsp;</p>
     <table>
      <thead>
<?php foreach ($this->hdrs as $val): ?>
       <tr<?php if ($val['id']) echo ' id="' . $val['id'] . '"'; ?>>
        <td class="label"><?php echo $val['label'] ?>:</td>
        <td><?php echo $val['val'] ?></td>
       </tr>
<?php endforeach; ?>
       <tr id="msgHeaderAtc"<?php if (!isset($this->atc_label)) echo ' style="display:none"'; ?>>
        <td class="label">
         <?php echo _("Attachments") ?>:
        </td>
        <td>
         <div id="partlist">
          <table>
           <?php echo $this->atc_list ?>
          </table>
         </div>
        </td>
       </tr>
      </thead>
     </table>
     <div id="msgloglist" style="display:none">
      <ul></ul>
     </div>
    </div>
   </div>
  </div>
  <div class="messageBody">
   <?php echo $this->msgtext ?>
  </div>
 </div>
</div>

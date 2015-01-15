<?php echo $this->status ?>

<div id="impLoading">
 <?php echo _("Loading...") ?>
</div>

<div id="msgData" style="display:none">
 <div class="horde-buttonbar">
  <div class="iconImg headercloseimg closeImg" id="windowclose" title="X"></div>
  <ul>
  <li class="horde-icon">
   <?php echo $this->actionButton(array('icon' => 'Reply', 'id' => 'reply_link', 'title' => _("Reply"))) ?>
  </li>
  <li class="horde-icon">
   <?php echo $this->actionButton(array('icon' => 'Forward', 'id' => 'forward_link', 'title' => _("Forward"))) ?>
  </li>
<?php if ($this->show_spam): ?>
  <li class="horde-icon">
   <?php echo $this->actionButton(array('icon' => 'Spam', 'id' => 'button_spam', 'title' => _("Spam"))) ?>
  </li>
<?php endif; ?>
<?php if ($this->show_innocent): ?>
  <li class="horde-icon">
   <?php echo $this->actionButton(array('icon' => 'Innocent', 'id' => 'button_innocent', 'title' => _("Innocent"))) ?>
  </li>
<?php endif; ?>
<?php if ($this->show_delete): ?>
  <li class="horde-icon">
   <?php echo $this->actionButton(array('icon' => 'Delete', 'id' => 'button_delete', 'title' => _("Delete"))) ?>
  </li>
<?php endif; ?>
  </ul>
 </div>

 <div class="msgfullread">
  <div class="msgHeaders">
   <div id="msgHeaders">
    <div class="optionsContainer">
<?php if ($this->show_view_source): ?>
     <div>
      <span id="msg_view_source" title="<?php echo _("View Source") ?>">
       <span class="iconImg"></span>
       <a><?php echo _("View Source") ?></a>
      </span>
     </div>
<?php endif; ?>
     <div>
      <span title="<?php echo _("Save") ?>">
       <span class="iconImg saveAsImg"></span>
       <a href="<?php echo $this->save_as ?>"><?php echo _("Save") ?></a>
      </span>
     </div>
<?php if ($this->show_view_all): ?>
     <div>
      <span id="msg_all_parts" title="<?php echo _("View All Parts") ?>">
       <span class="iconImg"></span>
       <a><?php echo _("View All Parts") ?></a>
      </span>
     </div>
<?php endif; ?>
<?php if ($this->listinfo): ?>
     <div>
      <span id="msg_listinfo" title="<?php echo _("List Info") ?>">
       <span class="iconImg"></span>
       <a href="#" onclick="<?php echo $this->listinfo ?>"><?php echo _("List Info") ?></a>
      </span>
     </div>
<?php endif; ?>
    </div>
    <div>
     <div class="subject allowTextSelection"><?php echo $this->subject ?></div>
     <table id="msgHeadersTable">
<?php if (isset($this->fulldate)): ?>
      <tr id="msgHeaderDate">
       <td class="label"><?php echo _("Date") ?>:</td>
       <td class="allowTextSelection">
        <span class="messagePrintShow"><?php echo $this->h($this->fulldate) ?></span>
        <span class="messagePrintNoShow"><?php echo $this->h($this->localdate) ?></span>
        <time class="msgHeaderDateRelative" is="time-ago" datetime="<?php echo $this->h($this->datestamp) ?>"></time>
       </td>
      </tr>
<?php endif; ?>
      <tr id="msgHeaderFrom" style="display:none">
       <td class="label"><?php echo _("From") ?>:</td>
       <td class="allowTextSelection"></td>
      </tr>
      <tr id="msgHeaderTo" style="display:none">
       <td class="label"><?php echo _("To") ?>:</td>
       <td class="allowTextSelection"></td>
      </tr>
      <tr id="msgHeaderCc" style="display:none">
       <td class="label"><?php echo _("Cc") ?>:</td>
       <td class="allowTextSelection"></td>
      </tr>
      <tr id="msgHeaderBcc" style="display:none">
       <td class="label"><?php echo _("Bcc") ?>:</td>
       <td class="allowTextSelection"></td>
      </tr>
<?php foreach ($this->user_hdrs as $val): ?>
      <tr>
       <td class="label"><?php echo $this->h($val['name']) ?>:</td>
       <td class="allowTextSelection"><?php echo $this->h($val['value']) ?></td>
      </tr>
<?php endforeach; ?>
     </table>
     <div id="partlist" style="display:none">
      <div class="partlistDownloadAll"></div>
      <ul></ul>
     </div>
     <ul id="msgloglist" style="display:none"></ul>
    </div>
   </div>
  </div>
  <div id="messageBody" class="messageBody allowTextSelection">
   <?php echo $this->msgtext ?>
  </div>
 </div>
</div>

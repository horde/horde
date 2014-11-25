<?php echo $this->status ?>

<div id="dimpLoading">
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
    <div class="dimpOptions">
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
<?php foreach ($this->hdrs as $val): ?>
      <tr<?php if (!empty($val['id'])) echo ' id="' . $val['id'] . '"'; ?>>
       <td class="label"><?php echo $val['label'] ?>:</td>
       <td class="allowTextSelection">
<?php if (isset($val['print'])): ?>
        <span class="messagePrintShow"><?php echo $this->h($val['print']) ?></span>
        <span class="messagePrintNoShow"><?php echo $this->h($val['val']) ?></span>
<?php else: ?>
         <?php echo $val['val'] ?>
<?php endif; ?>
<?php if (isset($val['datestamp'])): ?>
         <time class="msgHeaderDateRelative" is="time-ago" datetime="<?php echo $this->h($val['datestamp']) ?>"></time>
<?php endif; ?>
       </td>
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

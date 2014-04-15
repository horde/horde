<div id="dimpLoading">
 <?php echo _("Loading...") ?>
 <noscript>
  <div class="nojserror"><?php echo _("Error! This application requires javascript to be available and enabled in your browser.") ?></div>
 </noscript>
</div>

<div id="horde-page" style="display:none">
 <div id="dimpmain">
  <div id="dimpmain_iframe" style="display:none"></div>
  <div id="dimpmain_folder" style="display:none">
   <div id="dimpmain_folder_top">
    <div class="horde-buttonbar">
    <ul class="rightFloat">
     <li class="horde-nobutton">
      <?php echo $this->actionButton(array('id' => 'button_other', 'title' => _("Other"), 'right' => true)) ?>
     </li>
<?php if ($this->show_search): ?>
     <li class="horde-nobutton" id="filter">
      <?php echo $this->actionButton(array('id' => 'button_filter', 'title' => _("Filter"), 'right' => true)) ?>
     </li>
<?php endif; ?>
    </ul>
    <ul>
     <li class="horde-icon">
      <?php echo $this->actionButton(array('icon' => 'Refresh', 'id' => 'checkmaillink', 'title' => _("Refresh"))) ?>
     </li>
     <li class="horde-icon">
      <?php echo $this->actionButton(array('class' => 'noselectDisable', 'icon' => 'Reply', 'id' => 'button_reply', 'title' => _("Reply"))) ?>
     </li>
     <li class="horde-icon">
      <?php echo $this->actionButton(array('class' => 'noselectDisable', 'icon' => 'Forward', 'id' => 'button_forward', 'title' => _("Forward"))) ?>
     </li>
<?php if ($this->show_spam): ?>
     <li class="horde-icon" style="display:none">
      <?php echo $this->actionButton(array('class' => 'noselectDisable', 'icon' => 'Spam', 'id' => 'button_spam', 'title' => _("Spam"))) ?>
     </li>
<?php endif; ?>
<?php if ($this->show_innocent): ?>
     <li class="horde-icon" style="display:none">
      <?php echo $this->actionButton(array('class' => 'noselectDisable', 'icon' => 'Innocent', 'id' => 'button_innocent', 'title' => _("Innocent"))) ?>
     </li>
<?php endif; ?>
     <li class="horde-icon" style="display:none">
      <?php echo $this->actionButton(array('class' => 'noselectDisable', 'icon' => 'Resume', 'id' => 'button_resume', 'title' => _("Resume"))) ?>
     </li>
     <li class="horde-icon" style="display:none">
      <?php echo $this->actionButton(array('icon' => 'Resume', 'id' => 'button_template', 'title' => _("Use Template"))) ?>
     </li>
     <li class="horde-icon">
      <?php echo $this->actionButton(array('class' => 'noselectDisable', 'icon' => 'Delete', 'id' => 'button_delete', 'title' => _("Delete"))) ?>
     </li>
     <li class="horde-icon" id="button_compose">
      <?php echo $this->actionButton(array('icon' => 'Compose', 'title' => _("New Message"))) ?>
     </li>
    </ul>
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
      <div id="msgHeadersColl">
       <ul class="rightFloat">
        <li>
         <div class="date"></div>
        </li>
        <li>
         <a id="preview_other">
          <span class="iconImg" title="<?php echo _("Other Options") ?>"></span>
         </a>
        </li>
       </ul>
       <ul>
        <li>
         <div class="iconImg" id="th_expand" title="<?php echo _("Expand Headers") ?>"></div>
         <span class="subject allowTextSelection"></span>
         <?php echo _("from") ?>
         <span class="from"></span>
        </li>
       </ul>
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
       <div>
        <span class="iconImg" id="th_collapse" title="<?php echo _("Collapse Headers") ?>"></span>
        <span class="subject allowTextSelection"></span>
       </div>
       <table>
        <thead>
         <tr id="msgHeaderFrom">
          <td class="label"><?php echo _("From") ?>:</td>
          <td class="from allowTextSelection"></td>
         </tr>
         <tr id="msgHeaderDate">
          <td class="label"><?php echo _("Date") ?>:</td>
          <td class="date allowTextSelection"></td>
         </tr>
         <tr id="msgHeaderTo">
          <td class="label"><?php echo _("To") ?>:</td>
          <td class="to allowTextSelection"></td>
         </tr>
         <tr id="msgHeaderCc">
          <td class="label"><?php echo _("Cc") ?>:</td>
          <td class="cc allowTextSelection"></td>
         </tr>
         <tr id="msgHeaderBcc">
          <td class="label"><?php echo _("Bcc") ?>:</td>
          <td class="bcc allowTextSelection"></td>
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
           <div>
            <span class="msgLogLabel"><?php echo _("Message Log") ?></span>
           </div>
           <ul id="msgloglist" style="display:none"></ul>
          </td>
         </tr>
        </thead>
       </table>
      </div>
     </div>
     <div id="messageBody" class="messageBody allowTextSelection"></div>
    </div>
   </div>
  </div>
 </div>
</div>

<?php echo $this->sidebar ?>

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

<div id="msglistHeaderContainer">
 <div class="vpRowHoriz vpRow horde-table-header" id="msglistHeaderHoriz" style="display:none">
  <div class="msgStatus">
   <div class="iconImg msCheckAll msCheck" id="horiz_opts" title="<?php echo _("Select All") ?>"></div>
  </div>
  <div class="msgFrom sep">
   <div class="horde-split-left"></div>
  </div>
  <div class="msgSubject sep">
   <div class="horde-split-left"></div>
  </div>
  <div class="msgDate sep">
   <div class="horde-split-left"></div>
  </div>
  <div class="msgSize sep">
   <div class="horde-split-left"></div>
  </div>
 </div>
 <div class="horde-table-header" id="msglistHeaderVert" style="display:none">
  <div class="iconImg msgStatus">
   <div class="iconImg msCheckAll msCheck" title="<?php echo _("Select All") ?>"></div>
  </div>
  <div class="msgSort">
   <a class="widget" id="vertical_sort"><?php echo _("Sort") ?></a>
  </div>
 </div>
</div>

<?php if ($this->picker_img): ?>
<div id="flagnewContainer" style="display:none">
 <div>
  <input name="flagname" />
  <input name="flagcolor" size="5" />
  <a class="flagcolorpicker" href="#"><?php echo $this->picker_img ?></a>
 </div>
</div>
<?php endif; ?>

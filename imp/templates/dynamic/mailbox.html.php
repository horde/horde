<div id="impLoading">
 <?php echo _("Loading...") ?>
 <noscript>
  <div class="nojserror"><?php echo _("Error! This application requires javascript to be available and enabled in your browser.") ?></div>
 </noscript>
</div>

<div id="horde-page" style="display:none">
 <div id="impbase">
  <div id="impbase_iframe" style="display:none"></div>
  <div id="impbase_folder" style="display:none">
   <div id="impbase_folder_top">
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
     <span class="iconImg actionRefresh" id="search_refresh" title="<?php echo _("Refresh Search Results") ?>"></span>
     <span class="iconImg actionEditsearch" id="search_edit" style="display:none" title="<?php echo _("Edit Search Query") ?>"></span>
     <span id="search_time_elapsed" style="display:none"></span>
     <div id="search_label"></div>
    </div>

    <div id="viewport_error" style="display:none">
     <span class="iconImg actionRefresh" id="viewport_error_refresh" title="<?php echo _("Retry") ?>"></span>
     <div><?php echo _("Error loading message list.") ?></div>
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
         <span id="th_expand">
          <span class="iconImg" title="<?php echo _("Expand Headers") ?>"></span>
          <span class="subject allowTextSelection"></span>
         </span>
         <?php echo _("from") ?>
         <span class="from"></span>
        </li>
       </ul>
      </div>
      <div id="msgHeaders" style="display:none">
       <div class="optionsContainer">
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
          <span class="iconImg actionOther"></span>
          <a><?php echo _("Other Options") ?></a>
        </span>
        </div>
       </div>
       <div>
        <span id="th_collapse">
         <span class="iconImg" title="<?php echo _("Collapse Headers") ?>"></span>
         <span class="subject allowTextSelection"></span>
        </span>
       </div>
       <table id="msgHeadersTable">
        <tr id="msgHeaderDate">
         <td class="label"><?php echo _("Date") ?>:</td>
         <td class="date allowTextSelection"></td>
        </tr>
        <tr id="msgHeaderFrom">
         <td class="label"><?php echo _("From") ?>:</td>
         <td class="from allowTextSelection"></td>
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
       </table>

       <div id="partlist" style="display:none">
        <div class="partlistAllParts"></div>
        <div class="partlistDownloadAll"></div>
        <ul></ul>
       </div>

       <ul id="msgloglist" style="display:none"></ul>

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
   <td><a href="#" class="messageBodyErrorLink"><?php echo _("Click to view the message in a new window.") ?></a></td>
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
  <div class="msgSort">
   <a class="widget" id="vertical_sort"><?php echo _("Sort") ?></a>
  </div>
  <div class="iconImg msgStatus">
   <div class="iconImg msCheckAll msCheck" title="<?php echo _("Select All") ?>"></div>
  </div>
 </div>
</div>

<?php if ($this->picker_img): ?>
<div id="flagnew_redbox" style="display:none">
 <div>
  <input name="flagname" />
  <input name="flagcolor" size="5" />
  <a class="flagcolorpicker" href="#"><?php echo $this->picker_img ?></a>
 </div>
</div>
<?php endif; ?>

<div id="mbox_export_redbox" style="display:none">
 <select name="download_type">
  <option value="mbox"><?php echo _("Download into a MBOX file") ?></option>
  <option value="mboxzip"><?php echo _("Download into a MBOX file (ZIP compressed)") ?></option>
 </select>
</div>

<div id="mbox_import_redbox" style="display:none">
 <div>
  <input name="import_file" type="file"></input>
  <input name="MAX_FILE_SIZE" type="hidden"><?php echo $this->max_size ?></input>
  <input name="import_mbox" type="hidden"></input>
 </div>
</div>

<div id="sidebar_remote_redbox" style="display:none">
 <div>
  <input name="remote_password" type="password"></input>
  <input name="remote_id" type="hidden"></input>
  <div>
   <?php echo _("Save password?") ?>
   <input name="remote_password_save" type="checkbox"></input>
  </div>
 </div>
</div>

<div id="delete_mbox_redbox" style="display:none">
 <div>
  <input name="delete_subfolders" type="checkbox"></input>
  <?php echo _("Delete all subfolders?") ?>
 </div>
</div>

<div id="subscribe_mbox_redbox" style="display:none">
 <div>
  <input name="subscribe_subfolders" type="checkbox"></input>
 </div>
</div>

<div id="unsubscribe_mbox_redbox" style="display:none">
 <div>
  <input name="unsubscribe_subfolders" type="checkbox"></input>
 </div>
</div>

<div id="poll_mbox_redbox" style="display:none">
 <div>
  <input name="poll" type="checkbox"></input>
  <?php echo _("Check mailbox for new mail?") ?>
 </div>
</div>

<div id="slider_count" style="display:none"></div>

<div id="flag_newedit" style="display:none">
 <div>
  <div class="sep"></div>
  <a>
   <div class="iconImg"></div>
   <?php echo _("Create New Flag...") ?>
  </a>
  <a>
   <div class="iconImg"></div>
   <?php echo _("Edit Flags...") ?>
  </a>
 </div>
</div>

<div id="flag_row" style="display:none">
 <a class="ctxFlagRow">
  <div class="iconImg" style="display:none"></div>
  <span class="iconImg"></span>
 </a>
</div>

<div id="sidebar_mbox_loading" style="display:none">
 <span class="imp-sidebar-mbox-loading"><?php echo _("Loading...") ?></span>
</div>

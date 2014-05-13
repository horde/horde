<div id="compose" data-role="page">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'title' => _("New Message"))) ?>

 <div data-role="content">
  <form id="imp-redirect-form" style="display:none">
   <input type="hidden" id="imp-redirect-cache" name="composeCache" value="<?php echo $this->h($this->composeCache) ?>" />
   <label for="imp-redirect-to"><?php echo _("To:") ?></label>
   <input type="text" id="imp-redirect-to" name="redirect_to" />
  </form>

  <form id="imp-compose-form">
   <input type="hidden" id="imp-compose-cache" name="composeCache" />
   <input type="hidden" name="user" value="<?php echo $this->h($this->user) ?>" />
   <div data-role="fieldcontain">
<?php if (count($this->identities) > 1): ?>
    <label for="imp-compose-identity"><?php echo _("From:") ?></label>
    <select id="imp-compose-identity" name="identity">
<?php foreach ($this->identities as $identity): ?>
     <option value="<?php echo $this->h($identity['val']) ?>"<?php if ($identity['sel']) echo ' selected="selected"' ?>><?php echo $this->h($identity['label']) ?></option>
<?php endforeach ?>
    </select>
<?php endif; ?>

    <label for="imp-compose-to"><?php echo _("To:") ?></label>
    <div class="imp-compose-addr-div">
     <input type="text" id="imp-compose-to" name="to[]" />
     <ul id="imp-compose-to-suggestions" data-role="listview" data-inset="true"></ul>
     <div id="imp-compose-to-addr"></div>
    </div>

    <label for="imp-compose-cc"><?php echo _("Cc:") ?></label>
    <div class="imp-compose-addr-div">
     <input type="text" id="imp-compose-cc" name="cc[]" />
     <ul id="imp-compose-cc-suggestions" data-role="listview" data-inset="true"></ul>
     <div id="imp-compose-cc-addr"></div>
    </div>

    <label for="imp-compose-subject"><?php echo _("Subject:") ?></label>
    <input type="text" id="imp-compose-subject" name="subject" />

    <label for="imp-compose-message"><?php echo _("Text:") ?></label>
    <textarea id="imp-compose-message" name="message" rows="15" class="fixed"></textarea>
   </div>
  </form>
 </div>

 <div data-role="footer" class="ui-bar" data-position="fixed">
  <a href="#compose-submit"><?php echo _("Send Message") ?></a>
  <a href="#compose-cancel"><?php echo _("Cancel") ?></a>
  <a href="#compose-more" data-rel="popup"><?php echo _("More...") ?></a>
 </div>

 <div data-role="popup" data-history="false" data-theme="a" id="compose-more">
  <ul data-role="listview" data-inset="true">
<?php if ($this->draft): ?>
   <li>
    <a href="#compose-draft"><?php echo _("Save Draft") ?></a>
   </li>
   <li>
    <a href="#compose-discard" id="imp-compose-discard"><?php echo _("Discard Draft") ?></a>
   </li>
<?php endif; ?>
<?php if ($this->attach): ?>
   <li>
    <a href="#compose-attach"><?php echo _("Attachments...") ?></a>
   </li>
<?php endif; ?>
  </ul>
 </div>

<?php if ($this->attach): ?>
 <div data-role="popup" data-overlay-theme="a" data-history="false" id="imp-compose-attach">
  <div data-role="header" class="ui-corner-top">
   <h1><?php echo _("Attachments") ?></h1>
  </div>
  <div data-role="content" class="ui-corner-bottom ui-content">
   <ul data-role="listview" data-inset="true"></ul>
   <form id="imp-compose-attach-form" enctype="multipart/form-data" method="post">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo intval($this->max_size) ?>" />
    <div id="imp-compose-upload-container">
     <a data-role="button"><?php echo _("Add Attachment") ?></a>
     <input type="file" name="file_upload" />
    </div>
   </form>
   <a href="#" data-role="button" data-inline="true" data-rel="back" data-theme="c"><?php echo _("Close") ?></a>
  </div>
 </div>
<?php endif; ?>
</div>

<div id="compose" data-role="page">
 <div data-role="header">
  <h1 id="imp-compose-title"><?php echo _("New Message") ?></h1>
<?php if ($this->logout): ?>
  <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete" class="ui-btn-right"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>

 <form id="imp-redirect-form" action="<?php echo $this->composeLink ?>" style="display:none">
  <input type="hidden" id="imp-redirect-cache" name="composeCache" value="<?php echo $this->h($this->composeCache) ?>" />
  <label for="imp-redirect-to"><?php echo _("To:") ?></label>
  <input type="text" id="imp-redirect-to" name="redirect_to" />
 </form>

 <form id="imp-compose-form" action="<?php echo $this->composeLink ?>" method="post" enctype="multipart/form-data" target="submit_frame">
  <input type="hidden" id="imp-compose-cache" name="composeCache" value="<?php echo $this->h($this->composeCache) ?>" />
  <div data-role="fieldcontain">
   <label for="imp-compose-identity"><?php echo _("From:") ?></label>
   <select id="imp-compose-identity" name="identity">
<?php foreach ($this->identities as $identity): ?>
    <option value="<?php echo $this->h($identity['val']) ?>"<?php if ($identity['sel']) echo ' selected="selected"' ?>><?php echo $this->h($identity['label']) ?></option>
<?php endforeach ?>>
   </select>

   <label for="imp-compose-to"><?php echo _("To:") ?></label>
   <input type="text" id="imp-compose-to" name="to" />
  </div>

  <div data-role="fieldcontain">
   <label for="imp-compose-subject"><?php echo _("Subject:") ?></label>
   <input type="text" id="imp-compose-subject" name="subject" />

   <label for="imp-compose-message"><?php echo _("Text:") ?></label>
   <textarea id="imp-compose-message" name="message" rows="15" class="fixed"></textarea>
  </div>
 </form>

 <div data-role="footer" class="ui-bar">
  <a href="" data-role="button" id="imp-compose-submit"><?php echo _("Send Message") ?></a>
  <a href="#" data-role="button" data-rel="back"><?php echo _("Cancel") ?></a>
 </div>
</div>

<iframe name="submit_frame" id="submit_frame" style="display:none" src="javascript:false;"></iframe>

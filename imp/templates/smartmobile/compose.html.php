<div id="compose" data-role="page">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'title' => _("New Message"))) ?>

 <div data-role="content">
  <form id="imp-redirect-form" style="display:none">
   <input type="hidden" id="imp-redirect-cache" name="composeCache" value="<?php echo $this->h($this->composeCache) ?>" />
   <label for="imp-redirect-to"><?php echo _("To:") ?></label>
   <input type="text" id="imp-redirect-to" name="redirect_to" />
  </form>

  <form id="imp-compose-form">
   <input type="hidden" id="imp-compose-cache" name="composeCache" value="<?php echo $this->h($this->composeCache) ?>" />
   <div data-role="fieldcontain">
    <label for="imp-compose-identity"><?php echo _("From:") ?></label>
    <select id="imp-compose-identity" name="identity">
<?php foreach ($this->identities as $identity): ?>
     <option value="<?php echo $this->h($identity['val']) ?>"<?php if ($identity['sel']) echo ' selected="selected"' ?>><?php echo $this->h($identity['label']) ?></option>
<?php endforeach ?>
    </select>

    <label for="imp-compose-to"><?php echo _("To:") ?></label>
    <input type="text" id="imp-compose-to" name="to" />
    <ul id="imp-compose-to-suggestions" data-role="listview" data-inset="true"></ul>

    <label for="imp-compose-cc"><?php echo _("Cc:") ?></label>
    <input type="text" id="imp-compose-cc" name="cc" />
    <ul id="imp-compose-cc-suggestions" data-role="listview" data-inset="true"></ul>

    <label for="imp-compose-subject"><?php echo _("Subject:") ?></label>
    <input type="text" id="imp-compose-subject" name="subject" />

    <label for="imp-compose-message"><?php echo _("Text:") ?></label>
    <textarea id="imp-compose-message" name="message" rows="15" class="fixed"></textarea>
   </div>
  </form>
 </div>

 <div data-role="footer" class="ui-bar" data-position="fixed">
  <a href="#compose-submit"><?php echo _("Send Message") ?></a>
<?php if ($this->abook): ?>
  <a href="<?php echo $this->abook ?>" data-ajax="false"><?php echo _("Address Book") ?></a>
<?php endif; ?>
  <a href="#compose-cancel"><?php echo _("Cancel") ?></a>
 </div>
</div>

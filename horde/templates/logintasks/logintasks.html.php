<div class="modal-form">

 <form method="post" action="<?php echo $this->logintasks_url ?>" id="logintasks_confirm" name="logintasks_confirm">
  <input type="hidden" name="logintasks_page" value="1" />

  <div class="form">
   <div class="form-header"><?php echo $this->header ?></div>

<?php foreach ($this->tasks as $t): ?>
   <div class="logintasks-item">
<?php if ($this->confirm): ?>
    <input type="checkbox" class="checkbox" name="<?php echo $t['name'] ?>" id="<?php echo $t['name'] ?>"<?php if ($t['checked']) echo ' checked="checked"' ?> />
    <label for="<?php echo $t['name'] ?>">
<?php endif ?>
    <?php echo $t['descrip'] ?>
<?php if ($this->confirm): ?>
    </label>
<?php endif; ?>
   </div>
<?php endforeach; ?>

   <div>
<?php if ($this->confirm): ?>
    <input name="ok" type="submit" class="horde-default submit-button" value="<?php echo _("Perform Login Tasks") ?>" />
    <input id="logintasks_skip" style="display:none" type="button" class="submit-button" value="<?php echo _("Skip Login Tasks") ?>" />
<?php endif; ?>
<?php if ($this->agree): ?>
    <input name="ok" type="submit" name="agree" class="horde-default submit-button" value="<?php echo _("Yes, I Agree") ?>" />
    <input type="submit" name="not_agree" class="submit-button" value="<?php echo _("NO, I Do NOT Agree") ?>" />
<?php endif; ?>
<?php if ($this->notice): ?>
    <input name="ok" type="submit" class="submit-button" value="<?php echo _("Click to Continue") ?>" />
<?php endif; ?>
   </div>
  </div>
 </form>
</div>

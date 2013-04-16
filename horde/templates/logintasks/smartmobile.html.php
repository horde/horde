<div data-role="page" data-theme="a">
 <div data-role="header" data-nobackbtn="true">
 <h1><?php echo $this->title ?></h1>
 </div>

 <div data-role="content" style="max-width:480px; margin:0 auto;">

 <h3><?php echo $this->header ?></h3>

 <form method="post" action="<?php echo $this->logintasks_url ?>" id="logintasks_confirm" name="logintasks_confirm" data-ajax="false">
  <input type="hidden" name="logintasks_page" value="1" />

<?php foreach ($this->tasks as $t): ?>
  <div class="logintasks-item">
<?php if ($this->confirm): ?>
   <input type="checkbox" class="checkbox" name="<?php echo $t['name'] ?>" id="<?php echo $t['name'] ?>"<?php if ($t['checked']) echo ' checked="checked"' ?>>
   <label for="<?php echo $t['name'] ?>">
<?php endif; ?>
   <?php echo $t['descrip'] ?>
<?php if ($this->confirm): ?>
   </label>
<?php endif; ?>
  </div>
<?php endforeach; ?>

<?php if ($this->confirm): ?>
  <fieldset class="ui-grid-a horde-logintasks-buttons">
   <div class="ui-block-a">
    <button type="submit" data-theme="a" name="ok"><?php echo _("Run Login Tasks") ?></button>
   </div>
   <div class="ui-block-b">
    <span id="logintasks_skip" style="display:none">
     <button type="submit" data-theme="c"><?php echo _("Skip Login Tasks") ?></button>
    </span>
   </div>
  </fieldset>
<?php endif; ?>

<?php if ($this->agree): ?>
  <fieldset class="ui-grid-a horde-logintasks-buttons">
   <div class="ui-block-a">
    <button type="submit" data-theme="a" name="agree"><?php echo _("Yes, I Agree") ?></button>
   </div>
   <div class="ui-block-b">
    <button type="submit" data-theme="c" name="not_agree"><?php echo _("NO, I Do NOT Agree") ?></button>
   </div>
  </fieldset>
<?php endif; ?>

<?php if ($this->notice): ?>
  <fieldset class="horde-logintasks-buttons">
   <button type="submit" name="ok"><?php echo _("Click to Continue") ?></button>
  </fieldset>
<?php endif; ?>

 </form>
 </div>
</div>

<form name="newpage" method="post" action="<?php echo Wicked::url('NewPage') ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="page" value="NewPage" />
<input type="hidden" name="referrer" value="<?php echo htmlspecialchars($this->referrer()) ?>" />

<h1 class="header">
 <?php echo htmlspecialchars($this->pageName()) ?>
</h1>

<div class="headerbox" style="padding:5px">
<?php if ($search_results): ?>
 <p>
  <?php printf(_("%s does not exist, but maybe you were looking for one of the following pages?"), htmlspecialchars($this->referrer())) ?>
 </p>
 <table class="linedRow" style="width:100%" cellspacing="0">
  <tr class="item leftAlign">
   <th style="width:40%"><?php echo _("Page") ?></th>
   <th style="width:10%"><?php echo _("Version") ?></th>
   <th style="width:25%"><?php echo _("Author") ?></th>
   <th style="width:25%"><?php echo _("Creation Date") ?></th>
  </tr>
  <?php echo $search_results ?>
 </table>
 <br />
 <p>
  <?php echo _("Click on \"Create\" below if you want to create this page now and start editing.") ?>
 </p>
<?php else: ?>
 <p>
      <?php printf(_("%s does not exist. Click on \"Create\" below if you want to create this page now and start editing."), htmlspecialchars($this->referrer())) ?>
 </p>
<?php endif; ?>

 <p>
  <?php echo _("Page Template:") ?>
  <select name="template">
   <option value=""><?php echo _("(None)") ?></option>
   <?php
   foreach ($templates as $page) {
       echo '<option value="', htmlspecialchars($page['page_name']), '">',
           htmlspecialchars($page['page_name']), "</option>\n";
   }
   ?>
  </select>

  <input class="button" type="submit" value="<?php echo _("Create") ?>" />
  <a class="button" href="#" onclick="window.history.back();"><?php echo _("Cancel") ?></a>
  <?php echo Horde_Help::link('wicked', 'Templates') ?>
 </p>
</div>

</form>

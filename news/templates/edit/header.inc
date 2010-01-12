<div class="header"><?php echo $title ?></div>
<table class="striped sortable" id="news_list" style="width: 100%">
<thead>
<tr>
<th><?php echo _("Action") ?></th>
<th><?php echo _("Status") ?></th>
<th><?php echo _("Title") ?></th>
<th><?php echo _("Publish at") ?></th>
<th><?php echo _("User") ?></th>
<th><?php echo _("Reads") ?></th>
<?php
if ($has_comments) {
    echo '<th>' . _("Comments") . '</th>';
}
?>
</tr>
</thead>
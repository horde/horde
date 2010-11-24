<div class="singleps">
 <span class="headerLabel"><?php echo _("Date") ?>:</span>
 <span class="ago"><a title="<?php echo $readableDate ?>"><?php echo $commitDate ?></a></span>
</div>

<div class="singleps">
 <span class="headerLabel"><?php echo _("Author") ?>:</span> <?php echo $author ?>
</div>

<?php if (!empty($tags)): ?>
<div class="singleps">
 <span class="headerLabel"><?php echo _("Tags") ?>:</span> <?php echo implode(', ', array_map('htmlspecialchars', $tags)) ?>
</div>
<?php endif; ?>

<div class="singleps">
 <span class="headerLabel"><?php echo _("Description") ?>:</span>
</div>

<div class="fixed singlepslog"><?php echo $logMessage ?></div>

<div class="singleps">
 <span class="headerLabel"><?php echo _("Changes") ?>:</span>
</div>

<ul class="singlepsfiles">
<?php foreach ($files as $file): ?>
 <li><?php echo $file['file'] . (isset($file['added']) ? (' (<span class="diffadd">+' . $file['added'] . '</span>, <span class="diffdel">-' . $file['deleted'] . '</span>) ') : '') . ': ' . $file['from'] . ' -> ' . $file['to'] . ' ' . $file['diff'] ?></li>
<?php endforeach; ?>
</ul>

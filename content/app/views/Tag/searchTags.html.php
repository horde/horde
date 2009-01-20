<ul>
<?php foreach ($this->results as $tag_id => $tag_name): ?>
 <li value="<?= $this->escape($tag_id) ?>"><?= $this->escape($tag_name) ?></li>
<?php endforeach ?>
</ul>

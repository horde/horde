<ul>
<?php foreach ($this->tags as $tag_id => $tag_text): ?>
 <li value="<?= $this->escape($tag_id) ?>"><?= $this->escape($tag_text) ?></li>
<?php endforeach ?>
</ul>

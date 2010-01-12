<ul>
<?php foreach ($this->results as $tag): ?>
 <li value="<?= $this->escape($tag['tag_id']) ?>" created="<?= $this->escape($tag['created']) ?>"><?= $this->escape($tag['tag_name']) ?></li>
<?php endforeach ?>
</ul>

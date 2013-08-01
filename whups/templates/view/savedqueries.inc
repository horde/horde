<?php if ($header): ?>
<h1 class="header">
 <?php echo htmlspecialchars($this->_params['title']); ?>
</h1>
<?php endif; ?>
<table class="horde-table" width="100%">
<?php foreach ($this->_params['results'] as $id => $result): ?>
 <tr><td><?php echo Horde::link(Whups::urlFor('query', empty($result['slug']) ? array('id' => $id) : array('slug' => $result['slug']))) . htmlspecialchars($result['name']) ?></a></td></tr>
<?php endforeach; ?>
</table>

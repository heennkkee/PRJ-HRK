<h3>Taggar</h3>
<ul class="tag-list">
<?php
foreach ($tags as $tag) {
    echo '<li><a href="' . $this->di->url->create('questions/tag') . '/' . urlencode($tag) . '">' . $tag . '</a></li>';
}
?>
</ul>

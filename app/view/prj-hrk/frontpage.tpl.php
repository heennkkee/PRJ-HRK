<div class="left-side">
    <span class="frontpage-title">Senaste frågorna</span>
<?php foreach ($posts as $post) {
    $title = htmlentities($post->TITLE);
    $author = htmlentities($post->AUTHOR);
    $text = htmlentities($post->TEXT);
    $id = $post->ID;
?>
    <div class="allQuestions">
        <a href="<?=$this->di->url->create('questions/view/' . $id)?>">
            <h3><?=$title?></h3>
            <p><?=$author?>: <?=((strlen($text) > 75 ) ? substr($text, 0, 72) . '...' : $text)?></p>
        </a>
    </div>
<?php } ?>
</div>
<div class="right-side">
    <span class="frontpage-title">Taggar</span>
    <table class="tag-table">
        <tr><th>Årstid</th><th>Antal inlägg</th></tr>
<?php foreach ($tags as $tag) { ?>
        <tr><td><a href="<?=$this->di->url->create('questions/tag/' . urlencode($tag->DESCRIPTION))?>"><?=$tag->DESCRIPTION?></a></td><td><?=$tag->COUNT?></tr>
<?php }
$topUser = $topUser[0];
?>
    </table>
    <span class="frontpage-title">Vår bäste medlem</span>
    <a href="<?=$this->di->url->create('users/view/' . $topUser->ACRONYM)?>">
        <div class="top-member">
                <h3 style="display: inline-block;"><?=$topUser->NAME?></h3>
                <img style="display: block; margin: 0 auto;" src="<?=gravatar($topUser->GRAVATAR, 80)?>" width="80" height="80">
        </div>
    </a>
</div>
<br style="clear: both;">

<div class="allQuestions">
    <a href="<?=$this->di->url->create('questions/view/' . $id)?>">
        <div style="margin-left: 30px;">
            <h3><?=$title?></h3>
            <p><?=$author?>: <?=((strlen($text) > 75 ) ? substr($text, 0, 72) . '...' : $text)?></p>
            <?php
            if (strlen($tags[0]->TAGS) > 0) {
                echo '<span class="tag-line">';
                echo $tags[0]->TAGS;
                echo "</span>";
            }
            ?>
        </div>
    </a>
</div>

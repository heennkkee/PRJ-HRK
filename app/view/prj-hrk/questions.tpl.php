<?php
if (isset($score[0])) {
    $score = $score[0]->SCORE;
} else {
    $score = 0;
}
if (isset($replies[0])) {
    $replies = $replies[0]->REPLIES;
} else {
    $replies = 0;
}
?>
<div class="allQuestions">
    <a href="<?=$this->di->url->create('questions/view/' . $id)?>">
        <div style="postion: absolute; float: left; padding-top: 15px; text-align: center;">
            <span class="questions-score">Poäng<br><b><?=$score?></b></span><br><span class="questions-replies"><b><?=$replies?></b><br>Svar</span>
        </div>
        <div style="margin-left: 15px; display: inline-block;">
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

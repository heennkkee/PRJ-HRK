<a class="allQuestions" href="<?=$this->di->url->create('questions/view/' . $id)?>">
    <div>
        <h3><?=$title?></h3>
        <p><?=$author?>: <?=((strlen($text) > 75 ) ? substr($text, 0, 72) . '...' : $text)?></p>
    </div>
</a>

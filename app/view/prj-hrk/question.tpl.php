<div class="singleQuestion">
    <h2><?=$title?></h2>
    <span class="author"><?=$author?> <?=$created?></span><br>
    <p><?=$text?></p>
    <?php if (!is_null($edited)) : ?>
        <span class="edited">Redigerad <?=$edited?></span>
    <?php endif; ?>
</div>

<?php
foreach ($data as $subData) {
?>
    <div class="comment-comment">
        <p>
            <?=$this->di->textFilter->doFilter(htmlspecialchars($subData->TEXT), 'markdown')?>
            <span class="author"><?=$subData->AUTHOR?> <?=$subData->CREATED?></span>
        </p>
    </div>
<?php
}
?>

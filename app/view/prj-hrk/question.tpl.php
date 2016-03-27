<div class="singleQuestion">
    <div style="float: left; text-align: center;">
        <a class="no-dec arrow-link" href="<?=$this->di->url->create('questions/votequestion/' . $id . '/good' . '/' . $returnID)?>">
            <svg height="25" width="50"><polygon class="arrow-up<?=(($voted == 1) ? '-active' : '')?>" points="0,25 25,0 50,25"></svg>
        </a>
      <span class="score"><?=$score?></span>
      <a class="no-dec arrow-link" href="<?=$this->di->url->create('questions/votequestion/' . $id . '/bad' . '/' . $returnID)?>">
          <svg height="25" width="50"><polygon class="arrow-down<?=(($voted == -1) ? '-active' : '')?>" points="0,0 25,25 50,0"></svg>
      </a>
    </div>
    <div style="margin-left: 60px;">
        <h1><?=$title?></h1>
        <p><?=$text?></p>
        <span class="tags">
            <?php
            $test = array();
            foreach ($tags as $tag) {
                array_push($test, '<a href="' . $this->di->url->create('questions/tag/') . '/' . $tag->TAG_DESCR . '">' . $tag->TAG_DESCR . '</a>');
            }
            echo implode($test, ', ');
            ?>
        </span><br><br> 
        <span class="author"><?=$author?> <?=$created?></span><br>
    </div>
</div>

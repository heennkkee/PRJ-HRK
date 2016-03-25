<div class="reply">
    <div style="display: inline-block; height: 80px; border-right: 1px red solid; width: 80px; text-align: center;">
            <a class="no-dec" href="<?=$this->di->url->create('questions/votecomment/' . $id . '/good' . '/' . $returnID)?>">
                <svg height="25" width="50"><polygon class="arrow-up<?=(($voted == 1) ? '-active' : '')?>" points="0,25 25,0 50,25"></svg>
            </a><br>
          <span class="score"><?=$score?></span><br>
          <a class="no-dec" href="<?=$this->di->url->create('questions/votecomment/' . $id . '/bad' . '/' . $returnID)?>">
              <svg height="25" width="50"><polygon class="arrow-down<?=(($voted == -1) ? '-active' : '')?>" points="0,0 25,25 50,0"></svg>
          </a><br>
    </div>
    <div style="display: inline-block;">
        <p><?=$text?></p>
        <span class="author"><?=$author?> <?=$created?></span>
    </div>
</div>

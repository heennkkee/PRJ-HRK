<div class="reply">
    <div style="display: inline-block; height: 62px; width: 120px;">
        <div style="float: left; width: 50px; text-align: center;">
            <a class="no-dec arrow-link" href="<?=$this->di->url->create('questions/votecomment/' . $id . '/good' . '/' . $returnID)?>">
                <svg height="25" width="50"><polygon class="arrow-up<?=(($voted == 1) ? '-active' : '')?>" points="0,25 25,0 50,25"></svg>
            </a>
          <span class="score"><?=$score?></span>
          <a class="no-dec arrow-link" href="<?=$this->di->url->create('questions/votecomment/' . $id . '/bad' . '/' . $returnID)?>">
              <svg height="25" width="50"><polygon class="arrow-down<?=(($voted == -1) ? '-active' : '')?>" points="0,0 25,25 50,0"></svg>
          </a>
        </div>
        <img src="<?=$gravatar?>" height="60" width="60" style="float: right; border: 1px black solid; margin-top: 5px;">
    </div>
    <div style="display: inline-block; margin-left: 10px;">
        <p><?=$text?></p>
        <span class="author"><?=$author?> <?=$created?></span>
    </div>
</div>

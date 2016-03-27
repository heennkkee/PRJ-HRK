<div class="user-list">
    <a href="<?=$this->di->url->create('users/view/' . $data['ACRONYM'])?>">
        <img style="float: left; margin-right: 10px;" src="<?=gravatar($data['GRAVATAR'], 80)?>" width="80" height="80">
        <div style="float: left;">
            <h3><?=$data['NAME']?></h3>
            <span class="rep">Rykte: <?=$rep?></span>
        </div>
    </a>
</div>

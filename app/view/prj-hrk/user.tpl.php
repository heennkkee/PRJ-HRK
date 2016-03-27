<div style="display: inline-block;">
    <img src="<?=$gravatar?>" height="160" width="160" style="float: left;">
    <div style="float: left; margin-left: 10px;">
        <?php
        if ($edit) {
            echo '<a href="' . $this->di->url->create('users/edit/' . $id) . '">Redigera</a>';
        }
        ?>
        <h2><?=$name?></h2>
        <span class="rep">Rykte: <?=$rep?></span>
        <p><?=$description?></p>
    </div>
</div>

<div>
    <img src="<?=$gravatar?>" height="160" width="160" style="display: inline-block;">
    <div style="position: relative; left: 170px; top: -170px;">
        <h2><?=$name?></h2>
        <p><?=$description?></p>
        <?php
            if ($edit) {
                echo '<a href="' . $this->di->url->create('users/edit/' . $id) . '">Redigera</a>';
            }
        ?>
    </div>
</div>

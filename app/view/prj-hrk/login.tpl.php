<div class="login" style="border: 2px black solid; padding: 5px; display: inline-block; position: absolute; top: 0; right: 200px;">
    <?php
    echo $form;
    if (!isset($_SESSION['USER'])) {
        echo '<a href="' . $this->di->url->create('users/register') . '">Registrera dig</a>';
    }
    ?>
</div>

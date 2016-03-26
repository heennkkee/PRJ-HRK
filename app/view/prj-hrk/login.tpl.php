<div class="login" style="border: 2px black solid; padding: 5px; display: inline-block; position: absolute; top: 0; right: 200px;">
    <?php
    if (isset($_SESSION['USER'])) {
        echo '<span>Hej ' . $_SESSION['USER']['NAME'] . '</span>';
    }
    echo $form;
    if (!isset($_SESSION['USER'])) {
        echo '<a href="' . $this->di->url->create('users/register') . '">Registrera dig</a>';
    }
    ?>
</div>

<div class="login">
    <?php
    if (isset($_SESSION['USER'])) {
        echo '<p>Du är redan inloggad som: ' . $_SESSION['USER']['NAME'] . '</p>';
    }
    echo $form;
    if (!isset($_SESSION['USER'])) {
        echo '<a href="' . $this->di->url->create('users/register') . '">Registrera dig</a>';
    }
    ?>
</div>

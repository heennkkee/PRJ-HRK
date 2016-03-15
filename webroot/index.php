<?php

require __DIR__.'/config_with_app.php';


$app->router->handle();
$app->theme->render();

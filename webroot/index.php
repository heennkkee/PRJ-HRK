<?php

require __DIR__.'/config_with_app.php';

$app->router->add('', function () use ($app) {
    $app->views->add('prj-hrk/question', [
        'content' => '<h2>Index</h2>',
    ]);
});

$app->router->add('ask', function () use ($app) {
    $app->views->add('prj-hrk/question', [
        'content' => '<h2>Ställ en fråga</h2>',
    ]);
});

$app->router->add('rss', function () use ($app) {
    $app->rss->getRSS();
    die();
});

$app->router->handle();
$app->theme->render();

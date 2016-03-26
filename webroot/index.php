<?php

require __DIR__.'/config_with_app.php';
$app->session();

if (isset($_SESSION['USER'])) {

    $form = $app->form->create(['id' => 'login'], [
        'submit' => [
            'type'      => 'submit',
            'value'     => 'Logga ut',
            'label'     => 'Välkommen ' . $_SESSION['USER']['NAME'],
            'callback'  => function () {
                unset($_SESSION['USER']);
                return true;
            }
        ],
    ]);

    $status = $form->check();

    if ($status === true) {
        $url = $app->url->create('');
        $app->response->redirect($url);
    }
} else {
    $form = $app->form->create(['id' => 'login'], [
       'acronym' => [
           'type'        => 'text',
           'autofocus'   => true,
           'label'       => 'Inlogg',
           'required'    => true,
           'validation'  => ['not_empty'],
       ],
       'password' => [
           'type'        => 'password',
           'label'       => 'Lösenord',
           'required'    => true,
           'validation'  => ['not_empty'],
       ],
       'submit' => [
           'type'      => 'submit',
           'value'     => 'Logga in',
           'callback'  => function ($form) use ($app) {
               $res = $app->db->executeFetchAll('SELECT ACRONYM, NAME, PASSWORD FROM USERS WHERE ACRONYM = ?', [$form->Value('acronym')]);
               if (password_verify($form->Value('password'), $res[0]->PASSWORD)) {
                   $_SESSION['USER']['ACRONYM'] = $res[0]->ACRONYM;
                   $_SESSION['USER']['NAME'] = $res[0]->NAME;
                   return true;
               }
               return false;
           }
       ],
    ]);

    // Check the status of the form
    $status = $form->check();

    if ($status === true) {

        // What to do if the form was submitted?
        $url = $app->url->create('');
        $app->response->redirect($url);
    } else if ($status === false) {
        // What to do when form could not be processed?
        $form->AddOutput('<p class="warning">Login failed!</p>');
        $url = $app->request->getCurrentUrl();
        $app->response->redirect($url);
    }
}

$app->views->add('prj-hrk/login', ['form' => $form->getHTML(['use_fieldset' => false])], 'header');

if (!isset($_SESSION['USER'])) {
    $registering = $app->request->getRouteParts();
    if ($registering[0] === 'users' && $registering[1] === 'register') {
        $app->router->handle();
    } else {
        $app->views->add('error/403', [], 'main');
    }
    $app->theme->render();
    die();
}

$app->router->add('questions', function () use ($app) {

});

$app->router->add('setup', function () use ($app) {
    $app->db->dropTableIfExists('USER2COMMENTVOTE')->execute();
    $app->db->dropTableIfExists('USER2QUESTIONVOTE')->execute();


    $app->db->execute('CREATE TABLE USER2COMMENTVOTE (
        ACRONYM VARCHAR(20),
        ID INTEGER,
        SCORE INTEGER,
        CREATED DATETIME,
        UNIQUE(ACRONYM, ID) ON CONFLICT REPLACE
    )');

    $app->db->execute('CREATE TABLE USER2QUESTIONVOTE (
        ACRONYM VARCHAR(20),
        ID INTEGER,
        SCORE INTEGER,
        CREATED DATETIME,
        UNIQUE(ACRONYM, ID) ON CONFLICT REPLACE
    )');
});

$app->router->add('test', function () use ($app) {
    $res = $app->db->executeFetchAll('SELECT * FROM USER2QUESTIONVOTE');
    dump($res);
});

$app->router->add('questionSetup', function () use ($app) {
    $app->db->dropTableIfExists('QUESTIONS')->execute();
    $app->db->dropTableIfExists('COMMENTS')->execute();

    $app->db->createTable('COMMENTS', [
        'ID' => ['integer', 'primary key', 'not null', 'auto_increment'],
        'TEXT' => ['blob'],
        'CREATED' => ['datetime'],
        'AUTHOR' => ['varchar(80)'],
        'QUESTION_ID' => ['varchar(80)'],
        'COMMENT_ID' => ['varchar(80)']
    ])->execute();

    $app->db->createTable('QUESTIONS', [
        'ID' => ['integer', 'primary key', 'not null', 'auto_increment'],
        'TITLE' => ['varchar(80)', 'not null'],
        'TEXT' => ['blob'],
        'ANSWERED' => ['integer', 'DEFAULT 0'],
        'CREATED' => ['datetime'],
        'AUTHOR' => ['varchar(80)'],
    ])->execute();

});

$app->router->add('userSetup', function () use ($app) {

    $app->db->dropTableIfExists('USERS')->execute();

    $app->db->createTable('USERS', [
        'ID' => ['integer', 'primary key', 'not null', 'auto_increment'],
        'ACRONYM' => ['varchar(20)', 'unique', 'not null'],
        'NAME' => ['varchar(80)'],
        'PASSWORD' => ['varchar(255)'],
        'DESCRIPTION' => ['blob', 'DEFAULT ""'],
        'GRAVATAR' => ['varchar(255)', 'DEFAULT ""'],
        'REGISTERED' => ['datetime'],
        'REP' => ['INTEGER', 'DEFAULT 0']
    ])->execute();

    $app->db->insert(
        'USERS',
        ['ACRONYM', 'NAME', 'PASSWORD', 'REGISTERED', 'DESCRIPTION']
    );

    $now = gmdate('Y-m-d H:i:s');

    $app->db->execute([
        'admin',
        'Administrator',
        password_hash('admin', PASSWORD_DEFAULT),
        $now,
        'The admin of the all.'
    ]);

    $app->db->execute([
        'doe',
        'Doe McDoe',
        password_hash('doe', PASSWORD_DEFAULT),
        $now,
        'Welcome to the doe-family!'
    ]);
});


$app->router->add('', function () use ($app) {
    $app->views->add('prj-hrk/content', [
        'content' => '<h2>Välkommen till indexsidan</h2>',
    ]);
});

$app->router->add('rss', function () use ($app) {
    //$app->rss->clearRSS();
    //$app->rss->insertRSS(['LINK' => 'http://test.com', 'DESCRIPTION' => 'nytt rss "item"', 'TITLE' => 'Titel']);
    $app->rss->getRSS();
    die();
});

$app->router->handle();
$app->theme->render();

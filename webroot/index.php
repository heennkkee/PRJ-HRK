<?php

require __DIR__.'/config_with_app.php';
$app->session();

$app->router->add('userSetup', function () use ($app) {

    $app->db->dropTableIfExists('USERS')->execute();

    $app->db->createTable('USERS', [
        'ID' => ['integer', 'primary key', 'not null', 'auto_increment'],
        'ACRONYM' => ['varchar(20)', 'unique', 'not null'],
        'NAME' => ['varchar(80)'],
        'PASSWORD' => ['varchar(255)'],
        'REGISTERED' => ['datetime'],
        'REP' => ['INTEGER', 'DEFAULT 0']
    ])->execute();

    $app->db->insert(
        'USERS',
        ['ACRONYM', 'NAME', 'PASSWORD', 'REGISTERED', 'REP']
    );

    $now = gmdate('Y-m-d H:i:s');

    $app->db->execute([
        'admin',
        'Administrator',
        password_hash('admin', PASSWORD_DEFAULT),
        $now,
        1337
    ]);

    $app->db->execute([
        'user',
        'User McUser',
        password_hash('user', PASSWORD_DEFAULT),
        $now,
        0
    ]);
});

$app->router->add('logout', function () use ($app) {
    $form = $app->form->create([], [
        'submit' => [
            'type'      => 'submit',
            'value'     => 'Logga ut',
            'callback'  => function ($form) use ($app) {
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


    $app->views->add('default/page', [
        'title' => 'Logga ut',
        'content' => $form->getHTML()
    ]);
});

$userstatus = 'Hejsan';

$app->router->add('login', function () use ($app) {

    if (isset($_SESSION['USER'])) {
        $app->response->redirect($app->url->create('logout'));
    }

    $form = $app->form->create([], [
       'acronym' => [
           'type'        => 'text',
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

    $app->views->add('default/page', [
        'title' => 'Login',
        'content' => $form->getHTML()
    ]);
});

if (!isset($_SESSION['acronym'])) {
    die(include(ANAX_APP_PATH . 'view/error/403.tpl.php'));
} elseif (strlen($_SESSION['acronym']) < 2) {
    die(include(ANAX_APP_PATH . 'view/error/403.tpl.php'));
}

$app->router->add('', function () use ($app) {
    $app->views->add('prj-hrk/question', [
        'content' => '<h2>Välkommen till indexsidan</h2>',
    ]);
});

$app->router->add('ask', function () use ($app) {
    $form = $app->form->create([], [
       'name' => [
           'type'        => 'text',
           'label'       => 'Name of contact person:',
           'required'    => true,
           'validation'  => ['not_empty'],
       ],
       'email' => [
           'type'        => 'text',
           'required'    => true,
           'validation'  => ['not_empty', 'email_adress'],
       ],
       'phone' => [
           'type'        => 'text',
           'required'    => true,
           'validation'  => ['not_empty', 'numeric'],
       ],
       'submit' => [
           'type'      => 'submit',
           'callback'  => function ($form) {
               $form->AddOutput("<p><i>DoSubmit(): Form was submitted. Do stuff (save to database) and return true (success) or false (failed processing form)</i></p>");
               $form->AddOutput("<p><b>Name: " . $form->Value('name') . "</b></p>");
               $form->AddOutput("<p><b>Email: " . $form->Value('email') . "</b></p>");
               $form->AddOutput("<p><b>Phone: " . $form->Value('phone') . "</b></p>");
               $form->saveInSession = true;
               return true;
           }
       ],
       'submit-fail' => [
           'type'      => 'submit',
           'callback'  => function ($form) {
               $form->AddOutput("<p><i>DoSubmitFail(): Form was submitted but I failed to process/save/validate it</i></p>");
               return false;
           }
       ],
    ]);

    // Check the status of the form
    $status = $form->check();

    if ($status === true) {

        // What to do if the form was submitted?
        $form->AddOUtput("<p><i>Form was submitted and the callback method returned true.</i></p>");
        $url = $app->request->getCurrentUrl();
        $app->response->redirect($url);
    } else if ($status === false) {

        // What to do when form could not be processed?
        $form->AddOutput("<p><i>Form was submitted and the Check() method returned false.</i></p>");
        $url = $app->request->getCurrentUrl();
        $app->response->redirect($url);
    }

    $app->views->add('prj-hrk/question', [
        'content' => '<h2>Ställ en fråga</h2>' . $form->getHTML(),
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

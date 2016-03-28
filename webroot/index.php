<?php

require __DIR__.'/config_with_app.php';
$app->session();

if (!isset($_SESSION['USER'])) {
    $registering = $app->request->getRouteParts();
    $nonRegisteredSites = ['setup', 'userSetup', 'rss', 'about'];
    if (in_array($registering[0], $nonRegisteredSites)) {

    } else if ($registering[0] === 'users' && (isset($registering[1]) ? (($registering[1] === 'register') ? true : (($registering[1] === 'login') ? true : false)) : false)) {

    } else {
        $app->views->add('error/403', [], 'main');
        $app->theme->render();
        die();
    }
}

$app->router->add('questions', function () use ($app) {
    $app->dispatcher->forward([
        'controller' => 'questions',
        'action'     => 'view'
    ]);
});

$app->router->add('users', function () use ($app) {
    $app->dispatcher->forward([
        'controller'    => 'users',
        'action'        => 'view'
    ]);
});

$app->router->add('setup', function () use ($app) {
    $app->db->dropTableIfExists('USER2COMMENTVOTE')->execute();
    $app->db->dropTableIfExists('USER2QUESTIONVOTE')->execute();
    $app->db->dropTableIfExists('TAGS')->execute();
    $app->db->dropTableIfExists('TAGS2QUESTIONS')->execute();
    $app->db->execute('DROP VIEW IF EXISTS C_VIEW');
    $app->db->dropTableIfExists('QUESTIONS')->execute();
    $app->db->dropTableIfExists('COMMENTS')->execute();
    $app->db->dropTableIfExists('COM_COMMENTS')->execute();

    $app->db->createTable('COMMENTS', [
        'ID' => ['integer', 'primary key', 'not null', 'auto_increment'],
        'TEXT' => ['blob'],
        'CORRECT' => ['integer', 'DEFAULT 0'],
        'CREATED' => ['datetime'],
        'AUTHOR' => ['varchar(80)'],
        'QUESTION_ID' => ['varchar(80)']
    ])->execute();

    $app->db->createTable('COM_COMMENTS', [
        'ID' => ['integer', 'primary key', 'not null', 'auto_increment'],
        'TEXT' => ['blob'],
        'CREATED' => ['datetime'],
        'AUTHOR' => ['varchar(80)'],
        'COMMENT_ID' => ['varchar(80)']
    ])->execute();

    $app->db->createTable('QUESTIONS', [
        'ID' => ['integer', 'primary key', 'not null', 'auto_increment'],
        'TITLE' => ['varchar(80)', 'not null'],
        'TEXT' => ['blob'],
        'CREATED' => ['datetime'],
        'AUTHOR' => ['varchar(80)'],
    ])->execute();

    $app->db->execute('CREATE TABLE USER2COMMENTVOTE (
    ACRONYM VARCHAR(20),
    ID INTEGER,
    SCORE INTEGER,
    CREATED DATETIME,
    UNIQUE(ACRONYM, ID) ON CONFLICT REPLACE
    )');

    $app->db->execute('CREATE VIEW C_VIEW AS
    SELECT C.ID,
    C.TEXT,
    C.CORRECT,
    C.CREATED,
    C.AUTHOR,
    C.QUESTION_ID,
    COALESCE(SUM(U2C.SCORE), 0) AS "SCORE"
    FROM COMMENTS C LEFT JOIN USER2COMMENTVOTE U2C ON C.ID = U2C.ID
    GROUP BY C.ID, C.TEXT, C.CORRECT, C.CREATED, C.AUTHOR, C.QUESTION_ID');


    $app->db->execute('CREATE TABLE USER2QUESTIONVOTE (
        ACRONYM VARCHAR(20),
        ID INTEGER,
        SCORE INTEGER,
        CREATED DATETIME,
        UNIQUE(ACRONYM, ID) ON CONFLICT REPLACE
    )');

    $app->db->execute('CREATE TABLE TAGS2QUESTIONS (
        TAG_DESCR VARCHAR(80),
        QUESTION_ID INTEGER,
        UNIQUE(TAG_DESCR, QUESTION_ID) ON CONFLICT IGNORE
    )');

    $app->db->createTable('TAGS', [
        'ID' =>['integer', 'primary key', 'not null', 'auto_increment'],
        'DESCRIPTION' => ['varchar(80)', 'unique']
    ])->execute();

    $app->db->insert('TAGS', ['DESCRIPTION']);
    $app->db->execute(['Vår']);
    $app->db->execute(['Sommar']);
    $app->db->execute(['Höst']);
    $app->db->execute(['Vinter']);
});

$app->router->add('tags', function () use ($app) {
    $app->dispatcher->forward([
        'controller' => 'questions',
        'action'     => 'tag'
    ]);
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
    $posts = $app->db->executeFetchAll('SELECT * FROM QUESTIONS ORDER BY CREATED DESC LIMIT 3');
    $tags = $app->db->executeFetchAll('SELECT T.DESCRIPTION, COUNT(T2Q.QUESTION_ID) AS COUNT FROM TAGS T LEFT JOIN TAGS2QUESTIONS T2Q ON T.DESCRIPTION = T2Q.TAG_DESCR GROUP BY T.DESCRIPTION');
    $users = $app->db->executeFetchAll('SELECT ACRONYM FROM USERS');
    $temp = ['', 0];
    foreach ($users as $user) {
        $rep = $app->dispatcher->forward([
            'controller' => 'Users',
            'action' => 'rep',
            'params' => [$user->ACRONYM]
        ]);
        if ($rep >= $temp[1]) {
            $temp = [$user->ACRONYM, $rep];
        }
    }
    $topUser = $app->db->executeFetchAll('SELECT * FROM USERS WHERE ACRONYM = ?', [$temp[0]]);
    $app->views->add('prj-hrk/frontpage', [
        'posts' => $posts,
        'tags'  => $tags,
        'topUser' => $topUser
    ]);
});

$app->router->add('about', function () use ($app) {
    $app->views->add('prj-hrk/about');
});

$app->router->add('rss', function () use ($app) {
    $app->rss->getRSS();
    die();
});

$app->router->handle();
$app->theme->render();

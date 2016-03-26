<?php

namespace Anax\Users;

/**
 * A controller for users and admin related events.
 *
 */
class CUsersController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

/**
 * Initialize the controller.
 *
 * @return void
 */
    public function initialize()
    {
        $this->users = new \Anax\Users\USERS();
        $this->users->setDI($this->di);
    }

    public function viewAction($id)
    {
        $res = $this->users->find($id);
        if (!$res) {
            echo 'No user found, oopsie!<br><a href="' . $this->di->url->create('') . '">Till startsidan</a>';
            die();
        }
        $res = $res->getProperties();

        $name = htmlspecialchars($res['NAME']);
        $description = $this->di->textFilter->doFilter(htmlspecialchars($res['DESCRIPTION']), 'markdown');
        $gravatar = gravatar($res['GRAVATAR'], 160);

        $edit = ($_SESSION['USER']['ACRONYM'] == $res['ACRONYM']) ? true : false;
        $id = $res['ID'];

        $this->di->views->add('prj-hrk/user', ['name' => $name, 'description' => $description, 'gravatar' => $gravatar, 'id' => $id, 'edit' => $edit]);

        $activity = $this->di->db->executeFetchAll('SELECT "QUESTION" AS "TYPE", ID, TITLE AS "TEXT", CREATED, "" AS SCORE FROM QUESTIONS WHERE AUTHOR = ?
                                                    UNION ALL
                                                    SELECT CASE WHEN COMMENT_ID IS NULL THEN "COMMENT_QUESTION" ELSE "COMMENT_COMMENT" END, COALESCE(COMMENT_ID, QUESTION_ID), COMMENTS.TEXT, COMMENTS.CREATED, "" FROM COMMENTS WHERE AUTHOR = ?
                                                    UNION ALL
                                                    SELECT "VOTED_Q", Q.ID, Q.TITLE, U2Q.CREATED, U2Q.SCORE FROM USER2QUESTIONVOTE U2Q, QUESTIONS Q WHERE U2Q.ACRONYM = ? AND U2Q.ID = Q.ID
                                                    UNION ALL
                                                    SELECT "VOTED_C", C.ID, C.TEXT, U2C.CREATED, U2C.SCORE FROM USER2COMMENTVOTE U2C, COMMENTS C WHERE U2C.ACRONYM = ? AND U2C.ID = C.ID',
        [$res['ACRONYM'], $res['ACRONYM'], $res['ACRONYM'], $res['ACRONYM']]);
        $activity = json_decode(json_encode($activity), true);
        if(count($activity) > 0) {
            sksort($activity, 'CREATED');
        }

        $this->di->views->add('prj-hrk/activities', ['activities' => $activity]);

    }

    public function registerAction()
    {
        $form = $this->di->form->create(['id' => 'register'], [
           'acronym' => [
               'type'        => 'text',
               'label'       => 'Inloggning',
               'validation'  => ['not_empty'],
           ],
           'name' => [
               'type'    => 'text',
               'label'   => 'Namn',
               'validation' => ['not_empty'],
           ],
           'gravatar' => [
               'type'   => 'text',
               'label'  => 'Gravatar (e-mail)',
           ],
           'description' => [
             'type' => 'textarea',
           ],
           'password' => [
               'type'        => 'password',
               'label'       => 'Lösenord',
               'required'    => true,
               'validation'  => ['not_empty'],
           ],
           'submit' => [
               'type'      => 'submit',
               'callback'  => function ($form) {
                   $this->di->db->insert(
                       'USERS',
                       ['ACRONYM', 'NAME', 'PASSWORD', 'REGISTERED', 'GRAVATAR', 'DESCRIPTION']
                   );
                   $now = gmdate('Y-m-d H:i:s');

                   $bool = $this->di->db->execute([
                       $form->Value('acronym'),
                       $form->Value('name'),
                       password_hash($form->Value('password'), PASSWORD_DEFAULT),
                       $now,
                       $form->Value('gravatar'),
                       $form->Value('description')
                   ]);

                   return $bool;
               }
           ],
        ]);

        $status = $form->check();

        if ($status === true) {

            // What to do if the form was submitted?
            $url = $this->di->url->create('');
            $this->di->response->redirect($url);
        } else if ($status === false) {
            // What to do when form could not be processed?
            $form->AddOutput('<p class="warning">Något gick fel!</p>');
            $url = $this->di->request->getCurrentUrl();
            $this->di->response->redirect($url);
        }

        $this->di->views->add('prj-hrk/content', ['content' => $form->getHTML(['use_fieldset' => false])]);
    }

    public function editAction($id)
    {
        $res = $this->users->find($id);
        if (!$res) {
            echo 'No user found, oopsie!<br><a href="' . $this->di->url->create('') . '">Till startsidan</a>';
            die();
        }

        $res = $res->getProperties();

        if ($_SESSION['USER']['ACRONYM'] <> $res['ACRONYM']) {
            $url = $this->di->url->create('');
            $this->di->response->redirect($url);
        }

        $form = $this->di->form->create(['id' => 'editUser'], [
           'acronym' => [
               'type'        => 'hidden',
               'validation'  => ['not_empty'],
               'value'       => $_SESSION['USER']['ACRONYM'],
           ],
           'name' => [
               'type'    => 'text',
               'label'   => 'Namn',
               'validation' => ['not_empty'],
               'value'   => $res['NAME'],
           ],
           'gravatar' => [
               'type'   => 'text',
               'label'  => 'Gravatar (e-mail)',
               'value'  => $res['GRAVATAR'],
           ],
           'description' => [
             'type' => 'textarea',
             'value' => $res['DESCRIPTION'],
           ],
           'newPassword' => [
               'type' => 'password',
               'label' => 'Nytt lösenord',
           ],
           'password' => [
               'type'        => 'password',
               'label'       => 'Bekräfta med ditt lösenord',
               'required'    => true,
               'validation'  => ['not_empty'],
           ],
           'submit' => [
               'type'      => 'submit',
               'callback'  => function ($form) {
                   $confirm = $this->di->db->executeFetchAll('SELECT PASSWORD FROM USERS WHERE ACRONYM = ?', [$form->Value('acronym')]);
                   $form->saveInSession = false;
                   if (password_verify($form->Value('password'), $confirm[0]->PASSWORD)) {

                       $name = $form->Value('name');
                       $description = $form->Value('description');
                       $gravatar = $form->Value('gravatar');
                       $password = empty($form->Value('newPassword')) ? $form->Value('password') : $form->Value('newPassword');
                       $password = password_hash($password, PASSWORD_DEFAULT);
                       $acronym = $form->Value('acronym');
                       $this->db->execute('UPDATE USERS SET NAME = ?, DESCRIPTION = ?, GRAVATAR = ?, PASSWORD = ? WHERE ACRONYM = ?', [$name, $description, $gravatar, $password, $acronym]);
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
            $url = $this->di->url->create('users/view/' . $id);
            $this->di->response->redirect($url);
        } else if ($status === false) {
            // What to do when form could not be processed?
            $form->AddOutput('<p class="warning">Password didnt match!</p>');
            $url = $this->di->request->getCurrentUrl();
            $this->di->response->redirect($url);
        }
        $this->di->views->add('prj-hrk/content', ['content' => $form->getHTML(['use_fieldset' => false])]);
    }
}

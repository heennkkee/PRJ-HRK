<?php

namespace Anax\Questions;

/**
 * A controller for users and admin related events.
 *
 */
class CQuestionsController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

/**
 * Initialize the controller.
 *
 * @return void
 */
    public function initialize()
    {
        $this->questions = new \Anax\Questions\QUESTIONS();
        $this->questions->setDI($this->di);
    }
/**
 * Add a new question
 *
 * @return void
 */
    public function addAction()
    {
        $form = $this->di->form->create(['id' => 'question'], [
           'title' => [
               'type'        => 'text',
               'label'       => 'Title',
               'validation'  => ['not_empty'],
           ],
           'text' => [
               'type'        => 'textarea',
               'label'       => '',
               'validation'  => ['not_empty'],
           ],
           'author' => [
               'type'        => 'hidden',
               'value'       => $_SESSION['USER']['ACRONYM'],
           ],
           'submit' => [
               'type'      => 'submit',
               'callback'  => function ($form) {
                   $now = gmdate('Y-m-d H:i:s');
                   $this->di->db->insert('QUESTIONS', ['TITLE', 'TEXT', 'CREATED', 'AUTHOR']);
                   $sqlBool = $this->di->db->execute([$form->Value('title'), $form->Value('text'), $now, $form->Value('author')]);
                   if ($sqlBool) {
                       $form->saveInSession = false;
                       return true;
                   } else {
                       $form->saveInSession = true;
                       return false;
                   }
               }
           ],
        ]);

        // Check the status of the form
        $status = $form->check();

        if ($status === true) {
            // What to do if the form was submitted?
            $url = $this->di->url->create('questions/view/' . $this->di->db->lastInsertId());
            $this->di->response->redirect($url);
        } else if ($status === false) {

            // What to do when form could not be processed?
            $form->AddOutput("<p>Något gick fel.</p>");
            $url = $this->di->request->getCurrentUrl();
            $this->di->response->redirect($url);
        }

        $this->di->views->add('prj-hrk/ask', [
            'content' => '<h2>Ställ en fråga</h2>' . $form->getHTML(),
        ]);
    }

/**
 * Casts a vote on a comment
 *
 * @return void
 */
    public function votecommentAction($id, $reaction, $returnID = null)
    {
        if (is_null($id)) {
            return;
        }

        switch ($reaction) {
            case 'good':
                $score = 1;
                break;
            case 'bad':
                $score = -1;
                break;
            default:
                return;
        }
        $sql = "INSERT INTO USER2COMMENTVOTE (ACRONYM, ID, SCORE) VALUES(?, ?, ?)";
        $params = [$_SESSION['USER']['ACRONYM'], $id, $score];
        $this->di->db->execute($sql, $params);

        $url = $this->di->url->create('questions/view/' . $returnID);

        $this->di->response->redirect($url);
    }

/**
 * View a specific question
 *
 * @return void
 */
    public function viewAction($id = null)
    {
        if (is_null($id)) {
            $res = $this->questions->findAll();
        } else {
            $res = $this->questions->find($id);
        }

        if (!$res) {
            return;
        }

        if (is_array($res)) {
            foreach($res as $subRes) {
                $title = htmlspecialchars($subRes->TITLE);
                $text = strip_tags($this->di->textFilter->doFilter($subRes->TEXT, 'markdown'));
                $created = $subRes->CREATED;
                $edited = $subRes->EDITED;
                $author = htmlspecialchars($subRes->AUTHOR);
                $id = $subRes->ID;

                $this->di->views->add('prj-hrk/questions', ['title' => $title, 'text' => $text, 'created' => $created, 'edited' => $edited, 'author' => $author, 'id' => $id]);
            }
        } else {
            $res = $res->getProperties();

            $title = htmlspecialchars($res['TITLE']);
            $text = $this->di->textFilter->doFilter(htmlspecialchars($res['TEXT']), 'markdown');
            $created = $res['CREATED'];
            $edited = $res['EDITED'];
            $author = htmlspecialchars($res['AUTHOR']);
            $questionID = $res['ID'];

            $this->di->views->add('prj-hrk/question', ['title' => $title, 'text' => $text, 'created' => $created, 'edited' => $edited, 'author' => $author]);

            //Load eventual comments
            $res = $this->di->db->executeFetchAll('SELECT * FROM COMMENTS WHERE QUESTION_ID = ?', [$questionID]);

            foreach ($res as $comment) {
                $commentID = $comment->ID;
                $text = $this->di->textFilter->doFilter(htmlspecialchars($comment->TEXT), 'markdown');
                $created = $comment->CREATED;
                $author = htmlspecialchars($comment->AUTHOR);

                $score = $this->di->db->executeFetchAll('SELECT COALESCE(SUM(SCORE), 0) AS SCORE FROM USER2COMMENTVOTE WHERE ID = ? GROUP BY ID', [$commentID]);

                if (count($score) > 0) {
                    $score = $score[0]->SCORE;
                } else {
                    $score = 0;
                }

                $voted = $this->di->db->executeFetchAll('SELECT COALESCE(SCORE, 0) AS SCORE FROM USER2COMMENTVOTE WHERE ACRONYM = ? AND ID = ?', [$_SESSION['USER']['ACRONYM'], $commentID]);

                if (count($voted) > 0) {
                    $voted = $voted[0]->SCORE;
                } else {
                    $voted = 0;
                }

                $this->di->views->add('prj-hrk/replies', ['text' => $text, 'created' => $created, 'author' => $author, 'score' => $score, 'id' => $commentID, 'returnID' => $questionID, 'voted' => $voted]);
            }

            $form = $this->di->form->create(['id' => 'reply'], [
                'text' => [
                    'type'        => 'textarea',
                    'label'       => '',
                    'validation'  => ['not_empty'],
                ],
                'author' => [
                    'type'        => 'hidden',
                    'value'       => $_SESSION['USER']['ACRONYM'],
                ],
                'submit' => [
                    'type'      => 'submit',
                    'callback'  => function ($form) use ($questionID) {
                        $now = gmdate('Y-m-d H:i:s');
                        $this->di->db->insert('COMMENTS', ['TEXT', 'CREATED', 'AUTHOR', 'QUESTION_ID']);
                        $sqlBool = $this->di->db->execute([$form->Value('text'), $now, $form->Value('author'), $questionID]);
                        if ($sqlBool) {
                            $form->saveInSession = false;
                            return true;
                        } else {
                            $form->saveInSession = true;
                            return false;
                        }
                    }
                ],
             ]);

             // Check the status of the form
             $status = $form->check();

             if ($status === true) {
                 // What to do if the form was submitted?
                 $url = $this->di->request->getCurrentUrl();
                 $this->di->response->redirect($url);
             } else if ($status === false) {

                 // What to do when form could not be processed?
                 $form->AddOutput("<p>Något gick fel.</p>");
                 $url = $this->di->request->getCurrentUrl();
                 $this->di->response->redirect($url);
             }

             $this->di->views->add('prj-hrk/reply', ['form' => $form->getHTML(['use_fieldset' => false])]);

        }


    }
}

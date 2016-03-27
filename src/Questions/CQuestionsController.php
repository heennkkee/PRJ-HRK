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
        $tags = $this->di->db->executeFetchAll('SELECT * FROM TAGS');
        $values = array();

        foreach ($tags as $tag) {
            array_push($values, $tag->DESCRIPTION);
        }

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
           'tags' => [
               'type' => 'checkbox-multiple',
               'values' => $values
           ],
           'submit' => [
               'type'      => 'submit',
               'callback'  => function ($form) {
                   $now = gmdate('Y-m-d H:i:s');

                   $this->di->db->insert('QUESTIONS', ['TITLE', 'TEXT', 'CREATED', 'AUTHOR']);
                   $sqlBool = $this->di->db->execute([$form->Value('title'), $form->Value('text'), $now, $form->Value('author')]);

                   $questionID = $this->di->db->lastInsertId();
                   $tags = $form->Value('tags');

                   $this->di->db->insert('TAGS2QUESTIONS', ['TAG_DESCR', 'QUESTION_ID']);
                   foreach($tags as $tag) {
                       $this->di->db->execute([$tag, $questionID]);
                   }
                   if ($sqlBool) {
                       $form->saveInSession = false;
                       return [true, $questionID];
                   } else {
                       $form->saveInSession = true;
                       return [false];
                   }
               }
           ],
        ]);

        // Check the status of the form
        $status = $form->check();

        if ($status[0] === true) {
            // What to do if the form was submitted?
            $url = $this->di->url->create('questions/view/' . $status[1]);
            $this->di->response->redirect($url);
        } else if ($status[0] === false) {

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
        $now = gmdate('Y-m-d H:i:s');
        $sql = "INSERT INTO USER2COMMENTVOTE (ACRONYM, ID, SCORE, CREATED) VALUES(?, ?, ?, ?)";
        $params = [$_SESSION['USER']['ACRONYM'], $id, $score, $now];
        $this->di->db->execute($sql, $params);

        $url = $this->di->url->create('questions/view/' . $returnID);

        $this->di->response->redirect($url);
    }

    public function votequestionAction($id, $reaction, $returnID = null)
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
        $now = gmdate('Y-m-d H:i:s');
        $sql = "INSERT INTO USER2QUESTIONVOTE (ACRONYM, ID, SCORE, CREATED) VALUES(?, ?, ?, ?)";
        $params = [$_SESSION['USER']['ACRONYM'], $id, $score, $now];
        $this->di->db->execute($sql, $params);

        $url = $this->di->url->create('questions/view/' . $returnID);

        $this->di->response->redirect($url);
    }

    public function tagAction($tag = null) {
        if (is_null($tag)) {
            $res = $this->di->db->executeFetchAll('SELECT * FROM TAGS');
            $tags = array();
            foreach ($res as $subRes) {
                array_push($tags, $subRes->DESCRIPTION);
            }
            $this->di->views->add('prj-hrk/tags', ['tags' => $tags]);
        } else {
            $res = $this->di->db->executeFetchAll('SELECT * FROM QUESTIONS WHERE ID IN (SELECT QUESTION_ID FROM TAGS2QUESTIONS WHERE TAG_DESCR = ?) ORDER BY CREATED DESC', [(urldecode($tag))]);
            $this->di->views->add('prj-hrk/content', ['content' => '<h3>Visar frågor på taggen ' . strip_tags(urldecode($tag)) . '</h3>']);
            if (count($res) > 0) {
                foreach($res as $subRes) {
                    $this->listPosts($subRes);
                }
            } else {
                $this->di->views->add('prj-hrk/content', ['content' => '<h4>Inga träffar..</h4>']);
            }
        }
    }
/**
 * View a specific question
 *
 * @return void
 */
    public function viewAction($id = null)
    {
        if (is_null($id)) {
            $res = $this->di->db->executeFetchAll('SELECT * FROM QUESTIONS ORDER BY CREATED DESC');
        } else {
            $res = $this->questions->find($id);
        }

        if (!$res) {
            return;
        }

        if (is_array($res)) {
            $this->di->views->add('prj-hrk/content', ['content' => '<h3>Visar alla frågor</h3>']);
            foreach($res as $subRes) {
                $this->listPosts($subRes);
            }
        } else {
            $res = $res->getProperties();

            $title = htmlspecialchars($res['TITLE']);
            $text = $this->di->textFilter->doFilter(htmlspecialchars($res['TEXT']), 'markdown');
            $created = $res['CREATED'];
            $author = htmlspecialchars($res['AUTHOR']);
            $questionID = $res['ID'];

            $score = $this->di->db->executeFetchAll('SELECT COALESCE(SUM(SCORE), 0) AS SCORE FROM USER2QUESTIONVOTE WHERE ID = ? GROUP BY ID', [$questionID]);

            if (count($score) > 0) {
                $score = $score[0]->SCORE;
            } else {
                $score = 0;
            }

            $voted = $this->di->db->executeFetchAll('SELECT COALESCE(SCORE, 0) AS SCORE FROM USER2QUESTIONVOTE WHERE ACRONYM = ? AND ID = ?', [$_SESSION['USER']['ACRONYM'], $questionID]);

            if (count($voted) > 0) {
                $voted = $voted[0]->SCORE;
            } else {
                $voted = 0;
            }

            $tags = $this->di->db->executeFetchAll('SELECT TAG_DESCR FROM TAGS2QUESTIONS WHERE QUESTION_ID = ?',[$questionID]);

            $this->di->views->add('prj-hrk/question', ['title' => $title, 'text' => $text, 'created' => $created, 'author' => $author, 'score' => $score, 'voted' => $voted, 'returnID' => $questionID, 'id' => $questionID, 'tags' => $tags]);

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

                $gravatar = $this->di->db->executeFetchAll('SELECT GRAVATAR FROM USERS, COMMENTS WHERE USERS.ACRONYM = COMMENTS.AUTHOR AND COMMENTS.ID = ?', [$commentID]);
                $gravatar = gravatar($gravatar[0]->GRAVATAR, 60);

                $this->di->views->add('prj-hrk/replies', ['text' => $text, 'created' => $created, 'author' => $author, 'score' => $score, 'id' => $commentID, 'returnID' => $questionID, 'voted' => $voted, 'gravatar' => $gravatar]);
            }

            $form = $this->di->form->create(['id' => 'reply'], [
                'text' => [
                    'type'        => 'textarea',
                    'label'       => 'Skriv en kommentar:',
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

    private function listPosts($subRes) {
        $title = htmlspecialchars($subRes->TITLE);
        $text = strip_tags($this->di->textFilter->doFilter($subRes->TEXT, 'markdown'));
        $created = $subRes->CREATED;
        $author = htmlspecialchars($subRes->AUTHOR);
        $id = $subRes->ID;

        $score = $this->di->db->executeFetchAll('SELECT SUM(SCORE) AS SCORE FROM USER2QUESTIONVOTE WHERE ID = ?', [$id])[0]->SCORE;
        $tags = $this->di->db->executeFetchAll('SELECT GROUP_CONCAT(TAG_DESCR) AS TAGS FROM TAGS2QUESTIONS WHERE QUESTION_ID = ?',[$id]);

        $this->di->views->add('prj-hrk/questions', ['title' => $title, 'text' => $text, 'created' => $created, 'author' => $author, 'id' => $id, 'score' => $score, 'tags' => $tags]);
    }
}

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
                       $this->di->rss->insertRSS(['LINK' => $this->di->url->create('questions/view/' . $questionID), 'DESCRIPTION' => substr(strip_tags($this->di->textFilter->doFilter($form->Value('text'), 'markdown')), 0, 50), 'TITLE' => strip_tags($form->Value('title'))]);
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
    public function viewAction($id = null, $commentSort = null)
    {
        if (is_null($id)) {
            $res = $this->di->db->executeFetchAll('SELECT * FROM QUESTIONS ORDER BY CREATED DESC');
        } else {
            $res = $this->questions->find($id);
        }



        if (is_array($res)) {
            $this->di->views->add('prj-hrk/newPost');
            $this->di->views->add('prj-hrk/content', ['content' => '<h3>Visar alla frågor</h3>']);
            foreach($res as $subRes) {
                $this->listPosts($subRes);
            }
            if (!$res) {
                $this->di->views->add('prj-hrk/content', ['content' => '<h4>Inga frågor att visa...</h4>']);
            }
        } else {
            if (!$res) {
                die('Ingen fråga med det IDt..');
            }
            $res = $res->getProperties();
            $title = htmlspecialchars($res['TITLE']);
            $text = $this->di->textFilter->doFilter(htmlspecialchars($res['TEXT']), 'markdown');
            $created = $res['CREATED'];
            $questionAuthor = htmlspecialchars($res['AUTHOR']);
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

            $this->di->views->add('prj-hrk/question', ['title' => $title, 'text' => $text, 'created' => $created, 'author' => $questionAuthor, 'score' => $score, 'voted' => $voted, 'returnID' => $questionID, 'id' => $questionID, 'tags' => $tags]);

            //AVailable sorting parameters, date and score.
            $availableSorts = ['date', 'score'];
            if (in_array($commentSort, $availableSorts)) {
                switch ($commentSort) {
                    case 'date':
                        $sortVariable = 'ORDER BY CREATED ASC';
                        break;
                    case 'score':
                        $sortVariable = 'ORDER BY SCORE DESC';
                        break;
                }
            } else {
                $sortVariable = 'ORDER BY CORRECT DESC, CREATED ASC';
            }

            //Load eventual comments
            $res = $this->di->db->executeFetchAll('SELECT * FROM C_VIEW WHERE QUESTION_ID = ? ' . $sortVariable, [$questionID]);

            foreach ($res as $comment) {
                $commentID = $comment->ID;
                $text = $this->di->textFilter->doFilter(htmlspecialchars($comment->TEXT), 'markdown');
                $created = $comment->CREATED;
                $author = htmlspecialchars($comment->AUTHOR);
                $correct = $comment->CORRECT;

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

                $commentForm = $this->di->form->create(['id' => 'comment_' . $commentID], [
                    'text' => [
                        'type'        => 'textarea',
                        'label'       => 'Skriv en kommentar:',
                        'validation'  => ['not_empty'],
                    ],
                    'author' => [
                        'type'        => 'hidden',
                        'value'       => $_SESSION['USER']['ACRONYM'],
                    ],
                    'commentID' => [
                        'type'  => 'hidden',
                        'value' => $commentID
                    ],
                    'submit' => [
                        'type'      => 'submit',
                        'callback'  => function ($commentForm) {
                            $now = gmdate('Y-m-d H:i:s');
                            $this->di->db->insert('COM_COMMENTS', ['TEXT', 'CREATED', 'AUTHOR', 'COMMENT_ID']);
                            $sqlBool = $this->di->db->execute([$commentForm->Value('text'), $now, $commentForm->Value('author'), $commentForm->Value('commentID')]);
                            if ($sqlBool) {
                                $commentForm->saveInSession = false;
                                return true;
                            } else {
                                $commentForm->saveInSession = true;
                                return false;
                            }
                        }
                    ],
                ]);


                $status = $commentForm->check();

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

                $commentHTML = $commentForm->getHTML(['use_fieldset' => false]);

                $canAccept = ($questionAuthor === $_SESSION['USER']['ACRONYM']) ? true : false;

                if ($canAccept) {
                    $buttonText = ($correct > 0) ? 'Avmarkera' : 'Markera som rätt';

                    $acceptForm = $this->di->form->create(['id' => 'acceptForm_' . $commentID], [
                        'questionID' => [
                            'type'        => 'hidden',
                            'value'       => $questionID,
                        ],
                        'commentID' => [
                            'type'  => 'hidden',
                            'value' => $commentID
                        ],
                        'submit' => [
                            'type'      => 'submit',
                            'value'     => $buttonText,
                            'callback'  => function ($acceptForm) {
                                $currAccept = $this->di->db->executeFetchAll('SELECT ID FROM COMMENTS WHERE CORRECT = 1 AND QUESTION_ID = ?', [$acceptForm->Value('questionID')]);
                                if (isset($currAccept[0])) {
                                    $currAccept = (isset($currAccept[0]->ID)) ? $currAccept[0]->ID : null;
                                } else {
                                    $currAccept = null;
                                }
                                $this->di->db->execute('UPDATE COMMENTS SET CORRECT = 0 WHERE QUESTION_ID = ?', [$acceptForm->Value('questionID')]);
                                if ($currAccept == $acceptForm->Value('commentID')) {

                                } else {
                                    $this->di->db->execute('UPDATE COMMENTS SET CORRECT = 1 WHERE ID = ?', [$acceptForm->Value('commentID')]);
                                }
                                $acceptForm->saveInSession = false;
                                return true;
                            }
                        ],
                    ]);


                    $status = $acceptForm->check();

                    if ($status === true) {
                        // What to do if the form was submitted?
                        $url = $this->di->request->getCurrentUrl();
                        $this->di->response->redirect($url);
                    } else if ($status === false) {
                        // What to do when form could not be processed?
                        $acceptForm->AddOutput("<p>Något gick fel.</p>");
                        $url = $this->di->request->getCurrentUrl();
                        $this->di->response->redirect($url);
                    }
                    $acceptHTML = $acceptForm->getHTML(['use_fieldset' => false]);
                }

                $commentComments = $this->di->db->executeFetchAll('SELECT * FROM COM_COMMENTS WHERE COMMENT_ID = ? ORDER BY CREATED', [$commentID]);

                $this->di->views->add('prj-hrk/replies', ['text' => $text, 'correct' => $correct, 'created' => $created, 'author' => $author, 'score' => $score, 'id' => $commentID, 'returnID' => $questionID, 'voted' => $voted, 'gravatar' => $gravatar]);
                if ($canAccept) {
                    $this->di->views->add('prj-hrk/markAsReplied', ['form' => $acceptHTML]);
                }
                $this->di->views->add('prj-hrk/comments', ['data' => $commentComments]);
                $this->di->views->add('prj-hrk/commentsComment', ['form' => $commentHTML]);


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

        $replies = $this->di->db->executeFetchAll('SELECT COUNT(*) AS REPLIES FROM COMMENTS WHERE QUESTION_ID = ?', [$id]);
        $score = $this->di->db->executeFetchAll('SELECT COALESCE(SUM(SCORE), 0) AS SCORE FROM USER2QUESTIONVOTE WHERE ID = ? GROUP BY ID', [$id]);
        $tags = $this->di->db->executeFetchAll('SELECT GROUP_CONCAT(TAG_DESCR) AS TAGS FROM TAGS2QUESTIONS WHERE QUESTION_ID = ?',[$id]);

        $this->di->views->add('prj-hrk/questions', ['title' => $title, 'replies' => $replies, 'text' => $text, 'created' => $created, 'author' => $author, 'id' => $id, 'score' => $score, 'tags' => $tags]);
    }
}

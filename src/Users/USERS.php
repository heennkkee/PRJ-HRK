<?php

namespace Anax\Users;

/**
 * Model for Users.
 *
 */
class USERS extends \Anax\MVC\CDatabaseModel
{
    public function getRep($acronym, $includeScore = true)
    {
        $multiplier = 2;

        $votesCast = $this->di->db->executeFetchAll('SELECT COUNT(*) AS SCORE FROM USER2QUESTIONVOTE WHERE ACRONYM = ?', [$acronym])[0]->SCORE;
        $votesCast += $this->di->db->executeFetchAll('SELECT COUNT(*) AS SCORE FROM USER2COMMENTVOTE WHERE ACRONYM = ?', [$acronym])[0]->SCORE;

        $comments = $this->di->db->executeFetchAll('SELECT COUNT(*)*' . $multiplier . ' AS SCORE FROM COMMENTS WHERE AUTHOR = ?', [$acronym])[0]->SCORE;
        $comComments = $this->di->db->executeFetchAll('SELECT COUNT(*) AS SCORE FROM COM_COMMENTS WHERE AUTHOR = ?', [$acronym])[0]->SCORE;
        $questions = $this->di->db->executeFetchAll('SELECT COUNT(*)*' . $multiplier . ' AS SCORE FROM QUESTIONS WHERE AUTHOR = ?', [$acronym])[0]->SCORE;

        if ($includeScore) {
            $questionScore = $this->di->db->executeFetchAll('SELECT SUM(SCORE) AS SCORE FROM USER2QUESTIONVOTE WHERE ID IN (SELECT ID FROM QUESTIONS WHERE AUTHOR = ?)', [$acronym])[0]->SCORE;
            $commentScore = $this->di->db->executeFetchAll('SELECT SUM(SCORE) AS SCORE FROM USER2COMMENTVOTE WHERE ID IN (SELECT ID FROM COMMENTS WHERE AUTHOR = ?)', [$acronym])[0]->SCORE;
        } else {
            $questionScore = 0;
            $commentScore = 0;
        }

        $rep = $votesCast + $comComments + $comments + $questions + $questionScore + $commentScore;

        return $rep;
    }
}

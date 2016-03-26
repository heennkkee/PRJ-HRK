<?php
if (count($activities) > 0) {
    echo '<ul class="activity-list">';
    foreach ($activities as $activity) {
        extract($activity);
        switch ($TYPE) {
            case 'COMMENT_QUESTION':
                echo '<li><small>' . $CREATED . '</small>: Kommenterade en fråga med <b>' . strip_tags($this->di->textFilter->doFilter($TEXT, 'markdown')) . '</b></li>';
                break;
            case 'COMMENT_COMMENT':
                echo '<li><small>' . $CREATED . '</small>: Svarade på en kommentar med <b>' . strip_tags($this->di->textFilter->doFilter($TEXT, 'markdown')) . '</b></li>';
                break;
            case 'QUESTION':
                echo '<li><small>' . $CREATED . '</small>: Frågade <a href="' . $this->di->url->create('questions/view/' . $ID ) . '">' . $TEXT . '</a></li>';
                break;
            case 'VOTED_Q':
                echo '<li><small>' . $CREATED . '</small>: Röstade <i>' . $SCORE . '</i> på <a href="' . $this->di->url->create('questions/view/' . $ID ) . '">' . $TEXT . '</a></li>';
                break;
            case 'VOTED_C':
                echo '<li><small>' . $CREATED . '</small>: Röstade <i>' . $SCORE . '</i> på kommentaren <b>' . strip_tags($this->di->textFilter->doFilter($TEXT, 'markdown')) . '</b></li>';
                break;
            default:
        }
    }
    echo '</ul>';
} else {
    echo '<p>Ingen aktivitet att visa här...</p>';
}
?>

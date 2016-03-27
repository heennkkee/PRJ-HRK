<span class='sitetitle'><?=isset($siteTitle) ? $siteTitle : "Superquestions"?></span>
<?php
    if (isset($_SESSION['USER'])) {
?>
        <span class="top-login"><a href="<?=$this->di->url->create('users/view/' . $_SESSION['USER']['ID'])?>">Din profil</a><br><a href="<?=$this->di->url->create('users/login')?>">Logga ut</a></span>
<?php
    } else {
?>
        <span class="top-login"><a href="<?=$this->di->url->create('users/login')?>">Logga in </a><br><a href="<?=$this->di->url->create('users/register')?>">registrera dig</a></span>
<?php
    }
?>
<span class='siteslogan'><?=isset($siteTagline) ? $siteTagline : "Questions about a superdupersubject"?></span>

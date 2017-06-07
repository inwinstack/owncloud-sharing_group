<?php
    /** @var $_ array*/
?>

<ul id="group-menu">
    <li id="my-sharegroups">
	<a class="nav-icon-mysharegroup svg" href="#">
	    <span class="groups"><?php p($l->t('My share groups'))?></span>
	</a>
    </li>
    <li id="joined-sharegroups">
        <a class="icon-joined svg" href="#">
            <span class="groups"><?php p($l->t('I joined the share groups'))?></span>
        </a>
    </li>
    <li id="favorite-sharegroups">
        <a class="icon-favorite svg" href="favorite">
        <span class="groups"><?php p($l->t('My favorite share groups'))?></span>
        </a>
    </li>
</ul>


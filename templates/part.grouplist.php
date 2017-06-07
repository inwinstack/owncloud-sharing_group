<?php
    /** @var $_ array*/
?>

<!--<ul id="group-list" data-sort-groups="">
	
	<li id="everyone-group" data-gid="_everyone" data-usercount="" class="isgroup">
		<a href="#">
            <?php if(\OC_Config::getValue('sharing_group_mode') == 'Friend_mode'){ ?>
                <span class="group-name"><?php p($l->t('Friend List')); ?></span>
            <?php } else {?>
                <span class="group-name"><?php p($l->t('Everyone')); ?></span>
            <?php } ?>
        </a>
		<span class="utils">
			<span class="user-count" id="everyone-count"><?php  p($_['everyone']) ?></span>
		</span>
	</li>
</ul>-->

<ul id="group-menu">
    <li id=my-sharegroups">
	<a class="nav-icon-mysharegroup svg" href="#">
	    <span class="groups"><?php p($l->t('My share groups'))?></span>
	</a>
    </li>
    <li id="joined-sharegroups">
        <a class="nav-icon-joinedgroup svg" href="#">
            <span class="groups"><?php p($l->t('I joined the share groups'))?></span>
        </a>
    </li>
    <li id="favorite-sharegroups">
        <a class="nav-icon-stared svg" href="#">
        <span class="groups"><?php p($l->t('My favorite share groups'))?></span>
        </a>
    </li>
</ul>
<?php
    /** @var $_ array*/
?>

<ul id="group-list" data-sort-groups="">
	<!-- Add new group -->
	<li id="newgroup-init">
		<a href="#">
			<span><?php p($l->t('Add Group'))?></span>
		</a>
	</li>
	
    <li id="newgroup-form" style="display: none">
	    <input type="text" id="newgroup-name" placeholder="<?php p($l->t('Group')); ?>..." />
		<input type="submit" class="button icon-add svg" value="" />
	</li>

	<!-- Everyone -->
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
</ul>

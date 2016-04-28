<div id="controls">
    <div class="sg-toolbar">
        <div class="sg-dropdown" id="sg-dropdown-checkuser">
            <div type="button" class="sg-dropdown-toggle" id="toggle-checkbox">
                <input type="checkbox" class="icon" id="checkuser">
                <span class="caret" id="caret-checkbox"></span>
            </div>

            <ul class="sg-dropdown-menu checkuser">
                <li><a href="#" id="check-all"><?php p($l->t('All'))?></a></li>
                <li><a href="#" id="clear-all"><?php p($l->t('None'))?></a></li>
                <li><a href="#" id="inverse"><?php p($l->t('Inverse'))?></a></li>
            </ul>
        </div>

        <div class="sg-dropdown" id="sg-dropdown-group" style="display:none">
            <div type="button" class="sg-dropdown-toggle" id="toggle-group">
                <span><?php p($l->t('Groups'))?></span>
                <span class="caret" id="caret-group"></span>
            </div>

            <div class="sg-dropdown-menu group">
                <div class="sg-dropdown-body">
                    <div class="sg-dropdown-scrollable sg-dropdown-checkable">
                    </div>
                </div>
                <div class="sg-dropdown-footer">
                    <div class="btn-group btn-group-justified">
                        <div type="button" class="btn btn-flat" id="cancel">
                            <?php p($l->t('Cancel'))?>
                        </div>
                        <div type="button" class="btn btn-flat" id="multi-group-select">
                            <?php p($l->t('Add'))?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-listed">
            <?php if(\OC_Config::getValue('sharing_group_mode') == 'Friend_mode'){ ?>
                <button type="button" id="sg-addfriend">
                    <?php p($l->t('Add friends'))?>
                </button>
                <button type="button" id="sg-deletefriend" disabled="disabled">
                    <?php p($l->t('Delete friends'))?>
                </button>

                <div id="sg-dialog" title="<?php p($l->t('Add friends')) ?>">
                    <form class="sg-searchbox" action="" method="GET">
                        <input id="sg-friend-searchbox" placeholder="test@email.com" type="search" tabindex="5">
                        <input type="submit" class="sg-searchbox-submit" value="<?php p($l->t('Search')) ?>">
                    </form>
                    <span class='sg-friend-name'></span>
                </div>
            <?php } ?>
            

            <div class="sg-dropdown hidden" id="sg-dropdown-load">
                <div type="button" class="sg-dropdown-toggle" id="toggle-load">
                    <span><?php p($l->t('Load'))?></span>
                    <span class="caret" id="caret-load"></span>
                </div>
                <ul class="sg-dropdown-menu load" >
                    <li class="load-part-users"><a href="#"><?php p($l->t('Load next 100 users'))?></a></li>
                    <li class="load-all-users"><a href="#"><?php p($l->t('Load all users'))?></a></li>
                </ul>
            </div>


            <button type="button" class="load-part-users hidden" disabled="disabled">
                <?php p($l->t('Load next 100 users'))?>
            </button>

            <button type="button" class="load-all-users hidden" disabled="disabled">
                <?php p($l->t('Load all users'))?>
            </button>

            <span> <?php  p($l->t('Has been shown'))?></span>
            <span class="users-offset">?</span>
            <span> / </span>
            <span class="all-users-count">?</span>
        </div>
    </div>
</div>

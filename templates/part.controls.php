<?php
    $gid = str_replace("gid=", "", $_SERVER[QUERY_STRING]);
    if ($gid != "") {
        $result = \OCA\Sharing_Group\Data::getCreatedGroups();

        foreach($result as $key => $attribute){
            if ($attribute["gid"] == $gid)
                $group_name = $key;
        }
    }
    
    
    if ($_SERVER[QUERY_STRING] != '' ) {
        $insert = 'User'; 
    } else {
        $insert = 'Group';
    }
?>

<div id="controls">
    <div class="breadcrumb">
        <div class="crumb svg ui-droppable">
            <a>
                <img src="../../../core/img/places/home.svg" alt="home" class="svg">
            </a>
        </div>
        
        <?php if($_SERVER[QUERY_STRING] != '' && $_SERVER[QUERY_STRING] != 'favorite=1') : ?>
            <div class="crumb svg last">
                <span><?php echo $group_name ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="actions creatable">
        <div id="newgroup-initial">
		    <a>
                <input type="submit" class="button icon-add svg" style="position: relative; top: 0px;" value="" />
		    </a>
	    </div>
        <div id="newgroup-form"  style="display: none">
	        <input type="text" id="newgroup-name" class="form-in-control" style="width: 200px" placeholder="<?php p($l->t($insert)); ?>..." />
            <input type="submit" class="button icon-add svg" style="position: relative; top: 0px;" value="" />
	    </div>
    </div>
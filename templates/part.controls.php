<div id="controls">
    <div class="breadcrumb">
        <div class="crumb svg last">
            <a href="">
                <img src="../../../core/img/places/home.svg" alt="home" class="svg">
            </a>
        </div>
    </div>

    <div class="actions creatable">
        <div id="newgroup-initial">
		    <a href="#">
			    <!--<span><?php p($l->t('Add Group'))?></span>-->
                <input type="submit" class="button icon-add svg" style="position: relative" value="" />
		    </a>
	    </div>
        <div id="newgroup-form"  style="display: none">
	        <input type="text" id="newgroup-name" class="form-in-control" style="width: 200px" placeholder="<?php p($l->t('Group')); ?>..." />
            <input type="submit" class="button icon-add svg" style="position: relative" value="" />
	    </div>
    </div>
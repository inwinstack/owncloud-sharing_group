<?php

script('sharing_group', [
    'users',
    'groups',
    'filter',
    'jquery.tristate'
]);

script('files',[
    'jquery.fileupload'
]);

script('core', [
    'oc-dialogs',
    'multiselect',
    'singleselect'
]);
style('sharing_group', [
    'sharing_group',
    'style',
    'dropdown'
]);

?>

<div id="app">
	<div id="app-navigation">
		<?php print_unescaped($this->inc('part.grouplist')); ?>
		<?php print_unescaped($this->inc('part.settings')); ?>
	</div>

	<div id="app-content">
		<div id="app-content-wrapper">
			<?php print_unescaped($this->inc('part.controls')); ?>
			<?php print_unescaped($this->inc('part.userlist')); ?>
		</div>
	</div>
</div>

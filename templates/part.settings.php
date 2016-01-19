<div id="app-settings">
	<div id="app-settings-header">
		<button class="settings-button"
				data-apps-slide-toggle="#app-settings-content"
		></button>
	</div>
	<div id="app-settings-content">
        <!-- Your settings in here -->
        <button class="export" id="export"><?php p($l->t('export'))?></button>
        <button class="import" id="import"><?php p($l->t('import'))?></button>
        <input type='file' class="upload" name="fileToUpload" id="upload" style="display:none"/>
    </div>
</div>

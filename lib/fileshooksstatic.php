<?php
namespace OCA\Sharing_Group;

use OCP\Util;

class FilesHooksStatic {
	public static function register() {
		Util::connectHook('OCP\Share', 'post_shared', 'OCA\Sharing_Group\FilesHooksStatic', 'share');
	}
    

    static protected function getHooks() {
		$app = new AppInfo\Application();
		return $app->getContainer()->query('Hooks');
	}
    
   	public static function share($params) {
		self::getHooks()->share($params);
	}

}

?>

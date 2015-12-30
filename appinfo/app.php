<?php
/**
 * ownCloud - sharing_group
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Eric <eric.y@inwinstack.com>
 * @copyright Eric 2015
 */

namespace OCA\Sharing_Group\AppInfo;

use OCP\AppFramework\App;

$app = new Application();
$container = $app->getContainer();

$container->query('UserHooks')->register();

$container->query('OCP\INavigationManager')->add(function () use ($container) {
	$urlGenerator = $container->query('OCP\IURLGenerator');
	$l10n = \OC::$server->getL10N('sharing_group');
	return [
		// the string under which your app will be referenced in owncloud
		'id' => 'sharing_group',

		// sorting weight for the navigation. The higher the number, the higher
		// will it be listed in the navigation
		'order' => 10,

		// the route that will be shown on startup
		'href' => $urlGenerator->linkToRoute('sharing_group.page.index'),

		// the icon that will be shown in the navigation
		// this file needs to exist in img/
		'icon' => $urlGenerator->imagePath('sharing_group', 'app.svg'),

		// the title of your application. This will be used in the
		// navigation or on the settings page of your app
		'name' => $l10n->t('Sharing Group'),
	];
});

if(defined('\OCP\Share::SHARE_TYPE_SHARING_GROUP')) {
    
    \OCA\Sharing_Group\FilesHooksStatic::register();
    
    \OC::$server->getActivityManager()->registerExtension(function() {
            return new \OCA\Sharing_Group\Activity(
                \OC::$server->query('L10NFactory'),
                \OC::$server->getURLGenerator(),
                \OC::$server->getActivityManager()
            );
    });
}


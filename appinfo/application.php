<?php

namespace OCA\Sharing_Group\AppInfo;

use OCA\Sharing_Group\Hooks\UserHooks;
use OCA\Sharing_Group\FilesHooks;
use OCA\Sharing_Group\Data;
use OCA\Sharing_Group\Controller\SharingGroupsController;
use OCA\Sharing_Group\Activity;
use OCA\Sharing_Group\Controller\UserController;
use \OC\Settings\Controller\UsersController;
use \OCP\AppFramework\App;
use OCP\IContainer;
use OC\Files\View;
use OC\Settings\Controller\AppSettingsController;
use OC\Settings\Controller\CertificateController;
use OC\Settings\Controller\CheckSetupController;
use OC\Settings\Controller\EncryptionController;
use OC\Settings\Controller\GroupsController;
use OC\Settings\Controller\LogSettingsController;
use OC\Settings\Controller\MailSettingsController;
use OC\Settings\Controller\SecuritySettingsController;
use OC\Settings\Factory\SubAdminFactory;
use OC\Settings\Middleware\SubadminMiddleware;
use \OCP\Util;


class Application extends App {
	public function __construct (array $urlParams = array()) {
		parent::__construct('sharing_group', $urlParams);
		$container = $this->getContainer();
        
        $container->registerService('UserHooks', function($c) {
            return new UserHooks(
                $c->query('ServerContainer')->getUserManager()
            );
        });
        
        $container->registerService('ActivityApplication', function($c){
                return new \OCA\Activity\AppInfo\Application();
        });

        $container->registerService('Hooks', function(IContainer $c) {
            /** @var \OC\Server $server */
            $server = $c->query('ServerContainer');

			return new FilesHooks(
				$server->getActivityManager(),
				$c->query('ActivityApplication')->getContainer()->query('ActivityData'),
				$c->query('ActivityApplication')->getContainer()->query('UserSettings'),
                $server->getGroupManager(),
				new View(''),
				$server->getDatabaseConnection(),
				$c->query('CurrentUID')
			);
		});


        $container->registerService('GroupData', function(IContainer $c) {
			return new Data();
		});

        $container->registerService('CurrentUID', function(IContainer $c) {
			$server = $c->query('ServerContainer');

			$user = $server->getUserSession()->getUser();
			return ($user) ? $user->getUID() : '';
		});
        
		$container->registerService('SharingGroupsController', function(IContainer $c) {
			return new SharingGroupsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('GroupData'),
				$c->query('CurrentUID')
			);
		});

        $container->registerService('SettingsApplication', function(IContainer $c) {
            return new \OC\Settings\Application();
        });

        $container->registerService('SharingGroupL10N', function(IContainer $c) {
			return $c->query('ServerContainer')->getL10N('sharing_group');
		});


        $container->registerService('UserController', function( IContainer $c) { 
            return new UserController(
                $c->query('AppName'),
                $c->query('Request'),
                $c->query('UserManager'),
				$c->query('GroupManager'),
				$c->query('UserSession'),
				$c->query('Config'),
				$c->query('IsAdmin'),
				$c->query('L10N'),
				$c->query('Logger'),
				$c->query('Defaults'),
				$c->query('Mailer'),
				$c->query('DefaultMailAddress'),
				$c->query('URLGenerator'),
				$c->query('OCP\\App\\IAppManager'),
				$c->query('SubAdminFactory'),
                $c->query('SharingGroupsController')
            );
        });
    
        /**
		 * Core class wrappers
		 */
		$container->registerService('Config', function(IContainer $c) {
			return $c->query('ServerContainer')->getConfig();
		});
		$container->registerService('L10N', function(IContainer $c) {
			return $c->query('ServerContainer')->getL10N('settings');
		});
		$container->registerService('GroupManager', function(IContainer $c) {
			return $c->query('ServerContainer')->getGroupManager();
		});
		$container->registerService('UserManager', function(IContainer $c) {
			return $c->query('ServerContainer')->getUserManager();
		});
		$container->registerService('UserSession', function(IContainer $c) {
			return $c->query('ServerContainer')->getUserSession();
		});
		/** FIXME: Remove once OC_User is non-static and mockable */
		$container->registerService('IsAdmin', function(IContainer $c) {
			return \OC_User::isAdminUser(\OC_User::getUser());
		});
		/** FIXME: Remove once OC_SubAdmin is non-static and mockable */
		$container->registerService('IsSubAdmin', function(IContainer $c) {
			return \OC_Subadmin::isSubAdmin(\OC_User::getUser());
		});
		/** FIXME: Remove once OC_SubAdmin is non-static and mockable */
		$container->registerService('SubAdminFactory', function(IContainer $c) {
			return new SubAdminFactory();
		});
		$container->registerService('Mailer', function(IContainer $c) {
			return $c->query('ServerContainer')->getMailer();
		});
		$container->registerService('Defaults', function(IContainer $c) {
			return new \OC_Defaults;
		});
		$container->registerService('DefaultMailAddress', function(IContainer $c) {
			return Util::getDefaultEmailAddress('no-reply');
		});
		$container->registerService('Logger', function(IContainer $c) {
			return $c->query('ServerContainer')->getLogger();
		});
		$container->registerService('URLGenerator', function(IContainer $c) {
			return $c->query('ServerContainer')->getURLGenerator();
		});
		$container->registerService('ClientService', function(IContainer $c) {
			return $c->query('ServerContainer')->getHTTPClientService();
		});
		$container->registerService('INavigationManager', function(IContainer $c) {
			return $c->query('ServerContainer')->getNavigationManager();
		});
		$container->registerService('IAppManager', function(IContainer $c) {
			return $c->query('ServerContainer')->getAppManager();
		});


    }
}

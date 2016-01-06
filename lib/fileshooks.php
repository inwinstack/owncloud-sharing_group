<?php

namespace OCA\Sharing_Group; 

use OC\Files\Filesystem;
use OC\Files\View;
use OCA\Activity\Extension\Files;
use OCA\Activity\Extension\Files_Sharing;
use OCP\Activity\IExtension;
use OCP\DB;
use OCP\Share;
use OCP\Util;

class FilesHooks extends \OCA\Activity\FilesHooks {
    
    protected $activityData;

	/** @var \OCA\Activity\UserSettings */
	protected $userSettings;

	/** @var string|false */
	protected $currentUser;

    

    public function __construct(\OCA\Activity\Data $activityData, \OCA\Activity\UserSettings $userSettings, $currentUser) {
		$this->activityData = $activityData;
		$this->userSettings = $userSettings;
		$this->currentUser = $currentUser;
	}

    public function share($params) {
        if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {
            if($params['shareType'] === Share::SHARE_TYPE_SHARING_GROUP) {
                $this->shareFileOrFolderWithGroup($params);
            }
        }
    }


    protected function shareFileOrFolderWithGroup($params) {
		// User performing the share
        $subject = 'shared_sharing_group_self';
		$this->shareNotificationForSharer($subject, $params['shareWith'], $params['fileSource'], $params['itemType']);

		// Members of the new group
		$affectedUsers = array();
		$usersInGroup = Data::readGroupUsers($params['shareWith']);
		foreach ($usersInGroup as $user) {
			$affectedUsers[$user] = $params['fileTarget'];
		}

		// Remove the triggering user, we already managed his notifications
		unset($affectedUsers[$this->currentUser]);

		if (empty($affectedUsers)) {
			return;
		}

		$filteredStreamUsersInGroup = $this->userSettings->filterUsersBySetting($usersInGroup, 'stream', Files_Sharing::TYPE_SHARED);
		$filteredEmailUsersInGroup = $this->userSettings->filterUsersBySetting($usersInGroup, 'email', Files_Sharing::TYPE_SHARED);

		// Check when there was a naming conflict and the target is different
		// for some of the users
		$query = DB::prepare('SELECT `share_with`, `file_target` FROM `*PREFIX*share` WHERE `parent` = ? ');
		$result = $query->execute(array($params['id']));
		if (DB::isError($result)) {
			Util::writeLog('OCA\Activity\Hooks::shareFileOrFolderWithGroup', DB::getErrorMessage($result), Util::ERROR);
		} else {
			while ($row = $result->fetchRow()) {
				$affectedUsers[$row['share_with']] = $row['file_target'];
			}
		}
        
		foreach ($affectedUsers as $user => $path) {
			if (empty($filteredStreamUsersInGroup[$user]) && empty($filteredEmailUsersInGroup[$user])) {
				continue;
			}
            
			$this->addNotificationsForUser(
				$user, 'shared_with_by', array($path, $this->currentUser),
				$path, ($params['itemType'] === 'file'),
				!empty($filteredStreamUsersInGroup[$user]),
				!empty($filteredEmailUsersInGroup[$user]) ? $filteredEmailUsersInGroup[$user] : 0
			);
		}
	}

    protected function addNotificationsForUser($user, $subject, $subjectParams, $path, $isFile, $streamSetting, $emailSetting, $type = Files_Sharing::TYPE_SHARED, $priority = IExtension::PRIORITY_MEDIUM) {
		if (!$streamSetting && !$emailSetting) {
			return;
		}

		$selfAction = $user === $this->currentUser;
		$app = $type === Files_Sharing::TYPE_SHARED ? 'sharing_group' : 'files';
		$link = Util::linkToAbsolute('files', 'index.php', array(
			'dir' => ($isFile) ? dirname($path) : $path,
		));

		// Add activity to stream
		if ($streamSetting && (!$selfAction || $this->userSettings->getUserSetting($this->currentUser, 'setting', 'self'))) {
			$this->activityData->send($app, $subject, $subjectParams, '', array(), $path, $link, $user, $type, $priority);
		}

		// Add activity to mail queue
		if ($emailSetting && (!$selfAction || $this->userSettings->getUserSetting($this->currentUser, 'setting', 'selfemail'))) {
			$latestSend = time() + $emailSetting;
			$this->activityData->storeMail($app, $subject, $subjectParams, $user, $type, $latestSend);
		}
	}


}

?>

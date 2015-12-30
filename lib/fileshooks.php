<?php

namespace OCA\Sharing_Group; 

use OC\Files\Filesystem;
use OC\Files\View;
use OCA\Activity\Extension\Files;
use OCA\Activity\Data;
use OCA\Activity\UserSettings;
use OCA\Activity\Extension\Files_Sharing;
use OCP\Activity\IManager;
use OCP\Files\Mount\IMountPoint;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\Share;
use OCP\Util;
class FilesHooks extends \OCA\Activity\FilesHooks {
    
  	protected $manager;

	/** @var \OCA\Activity\Data */
	protected $activityData;

	/** @var \OCA\Activity\UserSettings */
	protected $userSettings;

	/** @var \OCP\IGroupManager */
	protected $groupManager;

	/** @var \OCP\IDBConnection */
	protected $connection;

	/** @var \OC\Files\View */
	protected $view;

	/** @var string|false */
	protected $currentUser;

	/**
	 * Constructor
	 *
	 * @param IManager $manager
	 * @param Data $activityData
	 * @param UserSettings $userSettings
	 * @param IGroupManager $groupManager
	 * @param View $view
	 * @param IDBConnection $connection
	 * @param string|false $currentUser
	 */
	public function __construct(IManager $manager, Data $activityData, UserSettings $userSettings, IGroupManager $groupManager, View $view, IDBConnection $connection, $currentUser) {
		$this->manager = $manager;
		$this->activityData = $activityData;
		$this->userSettings = $userSettings;
		$this->groupManager = $groupManager;
		$this->view = $view;
		$this->connection = $connection;
		$this->currentUser = $currentUser;
	}

    public function share($params) {
        if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {
            if($params['shareType'] === Share::SHARE_TYPE_SHARING_GROUP) {
                $this->shareFileOrFolderWithGroup($params['shareWith'], (int) $params['fileSource'], $params['itemType'], $params['fileTarget'], (int) $params['id']);
            }
        }
    }


    protected function shareFileOrFolderWithGroup($shareWith, $fileSource, $itemType, $fileTarget, $shareId) {
	    // Members of the new group
		$affectedUsers = array();

		// User performing the share
		$this->shareNotificationForSharer('shared_sharing_group_self', $shareWith, $fileSource, $itemType);
		$this->shareNotificationForOriginalOwners($this->currentUser, 'reshared_sharing_group_by', $shareWith, $fileSource, $itemType);


		$usersInGroup = \OCA\Sharing_Group\Data::readGroupUsers($shareWith);
		foreach ($usersInGroup as $user) {
			$affectedUsers[$user] = $fileTarget;
		}

		// Remove the triggering user, we already managed his notifications
		unset($affectedUsers[$this->currentUser]);

		if (empty($affectedUsers)) {
			return;
		}

		$filteredStreamUsersInGroup = $this->userSettings->filterUsersBySetting(array_keys($affectedUsers), 'stream', Files_Sharing::TYPE_SHARED);
		$filteredEmailUsersInGroup = $this->userSettings->filterUsersBySetting(array_keys($affectedUsers), 'email', Files_Sharing::TYPE_SHARED);

		$affectedUsers = $this->fixPathsForShareExceptions($affectedUsers, $shareId);
		foreach ($affectedUsers as $user => $path) {
			if (empty($filteredStreamUsersInGroup[$user]) && empty($filteredEmailUsersInGroup[$user])) {
				continue;
			}

			$this->addNotificationsForUser(
				$user, 'shared_with_by', array($path, $this->currentUser),
				$fileSource, $path, ($itemType === 'file'),
				!empty($filteredStreamUsersInGroup[$user]),
				!empty($filteredEmailUsersInGroup[$user]) ? $filteredEmailUsersInGroup[$user] : 0
			);
		}
	}



    protected function addNotificationsForUser($user, $subject, $subjectParams, $fileId, $path, $isFile, $streamSetting, $emailSetting, $type = Files_Sharing::TYPE_SHARED) {
		if (!$streamSetting && !$emailSetting) {
			return;
		}

		$selfAction = $user === $this->currentUser;
		$app = $type === Files_Sharing::TYPE_SHARED ? 'sharing_group' : 'files';
		$link = Util::linkToAbsolute('files', 'index.php', array(
			'dir' => ($isFile) ? dirname($path) : $path,
		));

		$objectType = ($fileId) ? 'files' : '';

		$event = $this->manager->generateEvent();
		$event->setApp($app)
			->setType($type)
			->setAffectedUser($user)
			->setAuthor($this->currentUser)
			->setTimestamp(time())
			->setSubject($subject, $subjectParams)
			->setObject($objectType, $fileId, $path)
			->setLink($link);

		// Add activity to stream
		if ($streamSetting && (!$selfAction || $this->userSettings->getUserSetting($this->currentUser, 'setting', 'self'))) {
			$this->activityData->send($event);
		}

		// Add activity to mail queue
		if ($emailSetting && (!$selfAction || $this->userSettings->getUserSetting($this->currentUser, 'setting', 'selfemail'))) {
			$latestSend = time() + $emailSetting;
			$this->activityData->storeMail($event, $latestSend);
		}
	}



}

?>

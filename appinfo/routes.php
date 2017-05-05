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

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Sharing_Group\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */

namespace OCA\Sharing_Group\AppInfo;

$application = new Application();

$application->registerRoutes($this,[
    'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	   ['name' => 'user#index', 'url' => '/user', 'verb' => 'GET'],
	   ['name' => 'user#getFriendList', 'url' => '/getFriendList', 'verb' => 'GET'],
	   ['name' => 'user#searchFriends', 'url' => '/search', 'verb' => 'GET'],
	   ['name' => 'user#countFriends', 'url' => '/countFriends', 'verb' => 'GET'],
	   ['name' => 'user#addFriend', 'url' => '/addFriend', 'verb' => 'GET'],
	   ['name' => 'user#deleteFriends', 'url' => '/deleteFriends', 'verb' => 'GET'],
	   ['name' => 'user#renameNickname', 'url' => '/rename', 'verb' => 'GET'],
	   ['name' => 'user#getUserByUID', 'url' => '/getUser', 'verb' => 'GET'],
	   ['name' => 'SharingGroups#fetch', 'url' => '/fetch', 'verb' => 'GET'],
	   ['name' => 'SharingGroups#fetchAll', 'url' => '/fetchAll', 'verb' => 'GET'],
	   ['name' => 'SharingGroups#getAllGroupsInfo', 'url' => '/getAllGroupsInfo', 'verb' => 'GET'],
	   ['name' => 'SharingGroups#create', 'url' => '/create', 'verb' => 'POST'],
	   ['name' => 'SharingGroups#controlGroupUser', 'url' => '/controlGroupUser', 'verb' => 'POST'],
	   ['name' => 'SharingGroups#renameGroup', 'url' => '/renameGroup', 'verb' => 'POST'],
	   ['name' => 'SharingGroups#deleteGroup', 'url' => '/deleteGroup', 'verb' => 'POST'],
	   ['name' => 'SharingGroups#importGroup', 'url' => '/importGroup', 'verb' => 'POST'],
	   ['name' => 'SharingGroups#export', 'url' => '/export', 'verb' => 'GET'],
           
           ['name' => 'SharingGroups#joinGroup', 'url' => '/joinGroup', 'verb' => 'POST'],
           ['name' => 'SharingGroups#getJoinedGroups', 'url' => '/getJoinedGroups', 'verb' => 'GET'],
           ['name' => 'SharingGroups#addFavoriteGroup', 'url' => '/addFavoriteGroup', 'verb' => 'POST'],
           ['name' => 'SharingGroups#leaveGroup', 'url' => '/leaveGroup', 'verb' => 'POST'],
           ['name' => 'SharingGroups#leaveFavoriteGroup', 'url' => '/leaveFavoriteGroup', 'verb' => 'POST'],
           ['name' => 'SharingGroups#getFavoriteGroups', 'url' => '/getFavoriteGroups', 'verb' => 'GET'],
           ['name' => 'SharingGroups#getCreatedGroups', 'url' => '/getCreatedGroups', 'verb' => 'GET'],
           ['name' => 'SharingGroups#renameGroupPassword', 'url' => '/renameGroupPassword', 'verb' => 'POST'],
           ['name' => 'SharingGroups#copyGroup', 'url' => '/copyGroup', 'verb' => 'POST'],
           ['name' => 'SharingGroups#getUsersInGroup', 'url' => '/getUsersInGroup', 'verb' => 'GET'],
    ]
]);

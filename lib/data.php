<?php
namespace OCA\Sharing_Group;

use OC\Files\Filesystem;
use OCP\DB;
use OCP\User;
use OCP\Util;

class Data{
    
    /**
     * check the user exist in the friend list or not.
     * 
     * @param string $uid
     * @return true|false
     */
    public static function checkUserExist($uid) {
        $user = User::getUser();
        $sql = 'SELECT * FROM `*PREFIX*sharing_group_friend` WHERE `uid` = ? AND `owner` = ?';
        $query = DB::prepare($sql);
        $result = $query->execute(array($uid ,$user));
        $row = $result->fetchRow();
        if(!$row) {
            
            return false;
        }

        return true;
    }
    
    /**
     * read all user in the friend list.
     * 
     * @return function getUsersFriendQueryResult
     */
    public static function readUsersFriends() {
        $user = User::getUser();
        $sql = 'SELECT * FROM `*PREFIX*sharing_group_friend` WHERE `owner` = ?';
        $query = DB::prepare($sql);
        $result = $query->execute(array($user));
        
        return self::getUsersFriendQueryResult($result);
    }

    /**
     * delete users from friend list
     *
     * @param array $uids
     * @return success|error
     */
    public static function deleteUsersFromFriend($uids) {
        $user = User::getUser();
        $sql = 'DELETE FROM `*PREFIX*sharing_group_friend` WHERE `owner` = ? AND (`uid` = ?';
        for($i = 1 ; $i < count($uids); $i++) {
            $sql .= 'OR `uid` = ?';
        }
        $sql .= ')';
        $query = DB::prepare($sql);
        $params = array($user);
        $params = array_merge($params,$uids);
        $result = $query->execute($params);
        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }
        
        \OCP\Util::emitHook('OCA\Sharing_group', 'post_delete', $uids);
        return 'success';
    }
    
    /**
     * add users to friend list
     *
     * @param array $uids 
     * @return success|error
     */
    public static function addUsersToFriend($uids) {
        $user = User::getUser();
        $sql = 'INSERT INTO `*PREFIX*sharing_group_friend`(`owner`, `uid`, `nickname`) VALUES';
        $sqlarr = [];
        $checkuid = self::readUsersFriends();
        $nicknames = self::getUidsDisplayname($uids);
        foreach($uids as $uid) {
            if(in_array($uid,$checkuid)) {
                continue;
            }
            if(!$nicknames[$uid]) {
                $nickname = mb_substr($uid,1,NULL,"UTF-8");
            }
            else {
                $nickname = mb_substr($nicknames[$uid],1,NUll,"UTF-8");
            }
            $sql .='(?, ? , ?) ,';
            array_push($sqlarr, $user, $uid, $nickname); 
        }
        if(!empty($sqlarr)) {
            $sql = substr($sql,0,-1);
            $query = DB::prepare($sql);
            $result = $query->execute($sqlarr);
        }   
        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }

        return 'success';
    }

    /**
     * add the user to friend list
     *
     * @param string $uid
     * @param string $nickname
     * @return success|error
     */
    public static function addUserToFriend($uid, $nickname) {
        $user = User::getUser();
        $sql = 'INSERT INTO `*PREFIX*sharing_group_friend`(`owner`, `uid`, `nickname`) VALUES (?,?,?)';
        $query = DB::prepare($sql);
        $result = $query->execute(array($user, $uid, $nickname));
        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }

        return 'success';
    }

    /**
     * get all friends
     *
     * @param int $limit
     * @param int $offset
     * @return function getFriendsListQueryResult 
     */
    public static function getAllFriends($limit,$offset) {
        $user = User::getUser(); 
        $sql = 'SELECT `uid`,`nickname` FROM `*PREFIX*sharing_group_friend` WHERE `owner` = ?';
        $query = DB::prepare($sql,$limit,$offset);
        $result = $query->execute(array($user));
        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }

        return self::getFriendsListQueryResult($result);
    }
    
    /**
     * get uids's displayname
     *
     * @param array $uids
     * @return function getDisplayNameQueryResult
     */
    public static function getUidsDisplayname($uids) {
        $user = User::getUser(); 
        $sql = 'SELECT `uid`,`displayname` FROM `*PREFIX*users` WHERE `uid` = ?';
        for($i = 1 ; $i < sizeof($uids); $i++) {
            $sql .= 'OR `uid` = ?';
        }
        $query = DB::prepare($sql);
        $result = $query->execute($uids);
        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }

        return self::getDisplayNameQueryResult($result);
    }

    /**
     * rename nickname 
     *
     * @param String $nickname
     * @param String $uid
     * @return success|error
     */
    public static function renameNickname($uid, $nickname) {
        $user = User::getUser();
        $sql = 'UPDATE `*PREFIX*sharing_group_friend` SET `nickname` = ? WHERE `owner` = ? AND `uid` = ?';
        $query = DB::prepare($sql);
        $result = $query->execute(array($nickname,$user,$uid));
        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }

        return 'success';
    }
    
    /**
     *  Add users to group or remove users from group
     *
     *  @param  $data 
     *  @return succees|error
     */
    public function controlGroupUser($data) {
        $user = User::getUser();
        $sql_add = 'INSERT INTO `*PREFIX*sharing_group_user` (`gid`, `uid`, `owner`) VALUES';
        $sql_share = 'DELETE FROM `*PREFIX*share` WHERE (`parent` = ? ';
        $sql_remove = 'DELETE FROM `*PREFIX*sharing_group_user` WHERE (`gid` = ? ';
        $add_arr = [];
        $share_arr = [];
        $gids = [];
        $remove_arr = [];
        foreach($data as $gid => $action) {
            $checkuid = self::readGroupUsers($gid);
            $add = isset($action['add']) ? $action['add'] : [];
            $remove = isset($action['remove']) ? $action['remove'] : [];
            
            foreach($add as $uid){
                if(!in_array($uid,$checkuid)) {
                    $sql_add .= '(?, ?, ?) ,';
                    array_push($add_arr,$gid,$uid,$user);
                }
            }
            
            if(!empty($remove)) {
                $sql = 'SELECT `id` FROM `*PREFIX*share` WHERE `share_type` = ? AND `share_with` = ? AND `item_type` = ?';
                $query = DB::prepare($sql);
                $check = $query->execute(array('7', $gid, 'folder'));
                $share_check = self::getSharingQueryResult($check); 
                
                if(!empty($share_check)) {
                    array_push($share_arr,$share_check[0]['id']);
                    for($i = 1; $i < count($share_check); $i++){
                        array_push($share_arr,$share_check[$i]['id']);
                        $sql_share .= 'OR `parent` = ?';
                    }
                }
                array_push($gids,$gid);
            }
        }
        
        if(!empty($add_arr)) {
            $sql = substr($sql_add,0,-1);
            $query = DB::prepare($sql);
            $result_add = $query->execute($add_arr);
        }
        
        if(!empty($remove)) {
            if(!empty($share_arr)) {
                $sql_share .= ') AND (`share_with` = ?';
                for($i = 1; $i < count($remove); $i++) {
                    $sql_share .= ' OR `share_with` = ?';
                }
                $sql_share .= ')';
                $share_arr = array_merge($share_arr, $remove);
                $query = DB::prepare($sql_share);
                $query->execute($share_arr);
            }
            for($i = 1; $i < count($gids); $i++) {
                $sql_remove .= ' OR `gid` = ?';
            }
            $sql_remove .= ') AND ( `uid` = ?';

            for($i = 1; $i < count($remove); $i++) {
                $sql_remove .= ' OR `uid` = ?';
            }
            $sql_remove .= ')';
            $remove_arr = array_merge($gids, $remove);
            $query = DB::prepare($sql_remove);
            $result_remove = $query->execute($remove_arr);

        }
        /*
        if(DB::isError($result_remove) || DB::isError($result_add)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result_remove), Util::ERROR);
			Util::writeLog('SharingGroup', DB::getErrorMessage($result_add), Util::ERROR);
            
            return 'error';
        }
        */
        return 'success';

    }

    /**
     *  remove users from group
     *
     *  @param array $uids 
     *  @return success|error
     */
    public static function removeUserFromGroup($uids) {
        $user = User::getUser();
        $sql = 'DELETE FROM `*PREFIX*sharing_group_user` WHERE `owner` = ? AND (`uid` = ?';
        for($i = 1 ; $i < count($uids); $i++) {
            $sql .= 'OR `uid` = ?';
        }
        $sql .= ')';
        $query = DB::prepare($sql);
        $params = array($user);
        $params = array_merge($params,$uids);
        $result = $query->execute($params);
        
        if(DB::isError($result) ) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }

        return 'success';
    }

    /**
     *  Remove sharing group's user when Owner has been deleted
     *
     *  @param String $uid  the Owner's name
     *  @return success|error
     */
    public static function removeUserFromOwner($uid) {
        $query = DB::prepare('DELETE `*PREFIX*sharing_groups`, `*PREFIX*sharing_group_user` FROM `*PREFIX*sharing_groups` INNER JOIN `*PREFIX*sharing_group_user` WHERE `*PREFIX*sharing_groups`.uid = `*PREFIX*sharing_group_user`.owner AND `*PREFIX*sharing_groups`.uid= ?');
        $result = $query->execute(array($uid));
        if(DB::isError($result) ) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }

        return 'success';
    }
    
    /**
     *  Add user to group
     *
     *  @param String $gid the sharing group name
     *  @param String $uids the username
     *  @return success|error
     */
    public static function addUserToGroup($gid, $uids) {
        $user = User::getUser();
        $sql = 'INSERT INTO `*PREFIX*sharing_group_user` (`gid`, `uid`, `owner`) VALUES';
        $sqlarr = [];
        $checkuid = self::readGroupUsers($gid);
        foreach($uids as $uid) {
            if(in_array($uid,$checkuid)) {
                continue;
            }
            $sql .='(?, ? , ?) ,';
            array_push($sqlarr, $gid, $uid, $user); 
        }
        if(!empty($sqlarr)) {
            $sql = substr($sql,0,-1);
            $query = DB::prepare($sql);
            $result = $query->execute($sqlarr);
        }     
        if(DB::isError($result) ) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }
        return 'success';
    }
    
    /**
     *  Get the user's sharing groups
     *
     *  @param String|null $user
     *  @param String|null $filter
     *  @return function getGroupsQueryResult
     */
    public static function readGroups($user = '', $filter = '') {

        $user = $user !== '' ? $user : User::getUser();
        $query = DB::prepare('SELECT `id`, `name` FROM `*PREFIX*sharing_groups` WHERE `uid` = ?');
        $result = $query->execute(array($user));

        return self::getGroupsQueryResult($result, $filter);
    }
    
    /**
     *  create groups
     *
     *  @param  String $name the sharing group name
     *  @return success|error
     */
    public static function createGroups($name) {
	    $groups = self::findGroupByName($name);
        if(empty($groups)) {
            $user = User::getUser();
            $sql = 'INSERT INTO `*PREFIX*sharing_groups` (`name`, `uid`) VALUES(?, ?)';
            $query = DB::prepare($sql);
            $result = $query->execute(array($name, $user));
        
            if (DB::isError($result)) {
			    Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
                return 'error';
            }
    
            return 'success';
        }
    }
    
    /**
     *  delete group
     *
     *  @param  int $gid the sharing group id 
     *  @return success|error
     */
    public static function deleteGroup($gid) {
        $user = User::getUser();
        
        $sql = 'DELETE FROM `*PREFIX*sharing_group_user` WHERE `gid` = ?';
        $query = DB::prepare($sql);
        $delete_userInGroup = $query->execute(array($gid));
                                
        $sql = 'DELETE FROM `*PREFIX*sharing_groups` WHERE `id` = ?';
        $query = DB::prepare($sql);
        $delete = $query->execute(array($gid));
        
        if(!DB::isError($delete)) {
            $sql = 'SELECT `id` FROM `*PREFIX*share` WHERE `share_type` = ? AND `share_with` = ?';
            $query = DB::prepare($sql);
            $check = $query->execute(array('7', $gid));
            $result = self::getSharingQueryResult($check); 
            
            foreach($result as $row) {
                $id = $row['id'];
                $sql = 'DELETE FROM `*PREFIX*share` WHERE `id` = ? OR `parent` = ?';
                $query = DB::prepare($sql);
                $result = $query->execute(array($id, $id));
            }
        }

        if (DB::isError($delete)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($delete), Util::ERROR);
            
            return 'error';
        }
        
        return 'success';
    }
    
    /**
     *  rename group
     *
     *  @param  $gid the sharing group id
     *  @param  $newname the sharing group name
     *  @return success|error
     */
    public static function renameGroup($gid, $newname) {
        $user = User::getUser();
        $sql = 'UPDATE `*PREFIX*sharing_groups` SET `name` = ? WHERE `id` = ? AND `uid` = ?';
        $query = DB::prepare($sql);
        $result = $query->execute(array($newname, $gid, $user));
        
        if (DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }
        
        return 'success';
    }
    
    /**
     *  Use group name to find group id
     *
     *  @param  $name the sharing group name
     *  @return function getGroupIdQueryResult
     */
    public static function findGroupByName($name) {
        $user = User::getUser();
        $sql = 'SELECT `id` FROM `*PREFIX*sharing_groups` WHERE `name` = ? AND `uid` = ?';
        $query = DB::prepare($sql);
        $result = $query->execute(array($name, $user));
        
        return self::getGroupIdQueryResult($result);
    }
    
    /**
     *  import group for current user
     *
     *  @param  $files the csv file
     *  @param  $type 
     *  @return Array $gids
     */
    public static function importGroup($files, $type = 'ignore') {
        $user = User::getUser();
        $importdata = self::importDataHanlder($files); 
        $length = sizeof($importdata);
        $gids = [];
        $sql = 'SELECT `name` FROM `*PREFIX*sharing_groups` WHERE `uid` = ?';
        $query = DB::prepare($sql);
        $result = $query->execute(array($user));
        $allgroup = self::getAllGroupsQueryResult($result);

        if ($type == 'ignore') {
                 
            for($i = 0; $i < $length; $i++) {
                if(in_array($importdata[$i]['group'], $allgroup)) {
                    continue;
                }
                self::createGroups($importdata[$i]['group']);
                $gid = self::findGroupByName($importdata[$i]['group']);
                if(!in_array($gid, $gids)) {
                    array_push($gids,$gid);
                }
                if (array_key_exists('uid', $importdata[$i])) {
                    self::addUsersToFriend($importdata[$i]['uid']);
                    self::addUserToGroup($gid, $importdata[$i]['uid']);
                }
             }
             
             return $gids;
        }
        
        if($type == 'merge') {
            for($i = 0; $i < $length; $i++) { 
                if(in_array($importdata[$i]['group'], $allgroup)) {
                    $gid = self::findGroupByName($importdata[$i]['group']);
                    if($importdata[$i]['uid'] != '\N') {
                        self::addUserToGroup($gid, $importdata[$i]['uid']);
                    }
                    continue;
                }
                
                self::createGroups($importdata[$i]['group']);
                $gid = self::findGroupByName($importdata[$i]['group']);
                if($importdata[$i]['uid'] != '\N'){
                    self::addUserToGroup($gid, $importdata[$i]['uid']);
                }
                
            }
        }
        
        if($type == 'cover'){
            for($i = 0; $i < $length; $i++){
                 if(in_array($importdata[$i]['group'], $allgroup)) {
                    $gid = self::findGroupByName($importdata[$i]['group']);
                    self::deleteGroup($gid); 
                }
                
                self::createGroups($importdata[$i]['group']);
                $gid = self::findGroupByName($importdata[$i]['group']);
                if($importdata[$i]['uid'] != '\N'){
                    self::addUserToGroup($gid[0], $importdata[$i]['uid']);
                }
            
            }
        }
    }
    
    /**
     *  get all sharing group for current user
     *
     *  @return 
     */
    public static function queryAllGroupsByUser() {
        $user = User::getUser();
        $sql = 'SELECT id, name , *PREFIX*sharing_group_user.uid FROM *PREFIX*sharing_groups LEFT OUTER JOIN *PREFIX*sharing_group_user ON *PREFIX*sharing_groups.id = *PREFIX*sharing_group_user.gid WHERE *PREFIX*sharing_groups.uid = ?';
        $query = DB::prepare($sql);

        return  $query->execute(array($user));
    }
    
    /**
     *  export sharing group and group user
     *
     *  @return string $string
     */
    public static function export() {
        $result = self::queryAllGroupsByUser();
        $string = "";
        
        if (DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }
        while ($row = $result->fetchRow()) {
            if($row['uid'] != NULL){
                $string .= $row['id'] . ',' . $row['name'] . ',' . $row['uid'] . "\n" ;
            }
            else {
                $string .= $row['id'] . ',' . $row['name'] . ',' . "\n" ;
            }
        }
        
        return $string;
    }
    
    /**
     *  get all sharing group and group user
     *
     *  @return array $data contains sharing group and group user
     */
    public static function getAllGroupsInfo() {
        $result = self::queryAllGroupsByUser();
        $data = [];
        
        if (DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }

        while ($row = $result->fetchRow()) {

            if(!array_key_exists($row['name'], $data)) {

                $data[$row['name']] = array('id' => $row['id'], 'name' => $row['name'],
                    'count' => 0, 'user' => '');
            }
            
            foreach($data as $value) {
                if($value['id'] === $row['id'] && $row['uid'] != NULL) {
                    $data[$row['name']]['count']++;
                    $data[$row['name']]['user'] .= $row['uid'].',';
                    break;
                }
            }
        }
        ksort($data,SORT_NATURAL | SORT_FLAG_CASE);   
        
        return $data;
    }
   
    /**
     *  find sharing group name by sharing group id
     *  
     *  @param int $id sharing group id
     *  @param string|null $user the sharing group owner
     *  @return function getGroupsQueryResult
     */
    public static function findGroupById($id = '', $user = '') {
        $user = ($user !== '') ? $user : User::getUser();
        $sql = $id ? 'SELECT `id` ,`name` FROM `*PREFIX*sharing_groups` WHERE `id` = ?' : 'SELECT `id` ,`name` FROM `*PREFIX*sharing_groups` WHERE `uid` = ?';
        
        $query = DB::prepare($sql);
        $input = $id ? $id : $user;
        $result = $query->execute(array($input));

        return self::getGroupsQueryResult($result, '');
    }
    
    /**
     *  find all sharing group 
     *  
     *  @return function getGroupsQueryResult
     */
    public static function findAllGroup() {
        $query = DB::prepare('SELECT `id` ,`name` FROM `*PREFIX*sharing_groups`');
        $result = $query->execute();
        
        return self::getGroupsQueryResult($result, '');
    }

    /**
     *  count all users 
     *  
     *  @return function getEveryoneCountQueryResult
     */
    public static function countAllUsers() {
        $user = User::getUser();

        if(\OC_Config::getValue('sharing_group_mode') == 'Friend_mode') {
            $sql = 'SELECT COUNT(uid) FROM `*PREFIX*sharing_group_friend` WHERE `owner` = ?';
            $query = DB::prepare($sql);
            $result = $query->execute(array($user));
        }    
        else {
            $sql = 'SELECT COUNT(uid) FROM `*PREFIX*users`';
            $query = DB::prepare($sql);
            $result = $query->execute();
        }
                
        return self::getEveryoneCountQueryResult($result); 
    }
    
    /**
     *  get all users 
     *  
     *  @return function getGroupUserQueryResult
     */
    public static function readAllUsers() {
        $sql = 'SELECT `uid` FROM `*PREFIX*users`';
        $query = DB::prepare($sql);
        $result = $query->execute();
        
        return self::getGroupUserQueryResult($result); 
    }
    
    /**
     *  get group user and group user's Display name by shraing group id
     *  
     *  @param int $id the sharing group id
     *  @param int $limit
     *  @param int $offset
     *  @return function getGroupUsersInfoQueryResult
     */
    public static function getGroupUsersInfo($id, $limit = null, $offset = null) {
        if(\OC_Config::getValue('sharing_group_mode') == 'Friend_mode') {
            $sql = 'SELECT `*PREFIX*sharing_group_user`.`uid` , `*PREFIX*sharing_group_friend`.`nickname` FROM `*PREFIX*sharing_group_user` INNER JOIN `*PREFIX*sharing_group_friend` ON `*PREFIX*sharing_group_user`.`uid`=`*PREFIX*sharing_group_friend`.`uid` WHERE `*PREFIX*sharing_group_user`.`gid` = ? ORDER BY `*PREFIX*sharing_group_user`.`uid` ASC';
        }
        else {
            $sql = 'SELECT `*PREFIX*sharing_group_user`.`uid` , `*PREFIX*users`.`displayname` FROM `*PREFIX*sharing_group_user` INNER JOIN `*PREFIX*users` ON `*PREFIX*sharing_group_user`.`uid`=`*PREFIX*users`.`uid` WHERE `*PREFIX*sharing_group_user`.`gid` = ? ORDER BY `*PREFIX*sharing_group_user`.`uid` ASC';
        }
        $query = DB::prepare($sql,$limit,$offset);
        $result = $query->execute(array($id));
        
        return self::getGroupUsersInfoQueryResult($result); 
    }

    /**
     *  get group user by shraing group id
     *  
     *  @param int $id the sharing group id
     *  @return function getGroupUserQueryResult
     */
    public static function readGroupUsers($id) {
        $sql = 'SELECT `uid` FROM `*PREFIX*sharing_group_user` WHERE `gid` = ?';
        $query = DB::prepare($sql);
        $result = $query->execute(array($id));
        
        return self::getGroupUserQueryResult($result); 
    }

    /**
     *  get sharing group by shraing group user
     *  
     *  @param string $user the sharing group user
     *  @return function getUserGroupQueryResult
     */
    public static function readUserGroups($user) {
        $query = DB::prepare('SELECT `gid` FROM `*PREFIX*sharing_group_user` WHERE `uid` = ?');
        $result = $query->execute(array($user));
        
        return self::getUserGroupQueryResult($result);
    }
    
    /**
     *  Get sharing group name by shraing group id
     *  
     *  @param int $id the sharing group id
     *  @return string|null
     */
    public static function getGroupName($id) {
        $query = DB::prepare('SELECT `name` FROM `*PREFIX*sharing_groups` WHERE `id` = ?');
        $result = $query->execute(array($id));

        if(DB::isError($result)) {
		    Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            return;
        } 

        $row = $result->fetch();
        
        return $row !== null ? $row['name'] : null;
    }
    
    /**
     *  Check the user in the sharing group or not
     *  
     *  @param int $gid the sharing group id
     *  @param string $uid the sharing group user 
     *  @return bool
     */
    public static function inGroup($uid, $gid) {
        $query = DB::prepare('SELECT `uid` FROM `*PREFIX*sharing_group_user` WHERE `gid` = ? AND `uid` = ?');
        $result = $query->execute(array($gid,$uid));

        if(DB::isError($result)) {
		    Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            return false;
        }

        return $result !== null;
    }
    
    /**
     *  Process the result and return the sharing group name
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @return array|null
     */
    private static function getAllGroupsQueryResult($result) {
        $data = [];

        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);

            return;
        }

        while($row = $result->fetch()) {
            $data[] = $row['name'];
        }
        return $data;
    }
    
    /**
     *  Process the result and return the sharing group id
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @return array|error
     */
    private static function getGroupIdQueryResult($result) {
        $data = '';
        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);

            return 'error';
        }

        while($row = $result->fetch()) {
            $data = $row['id'];
        }

        return $data;
    }
    
    /**
     *  Process the result and return the sharing group user and user's DisplayName
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @return array|null
     */
    private static function getGroupUsersInfoQueryResult($result) {
        $data = [];

        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);

            return;
        }

        while($row = $result->fetch()) {
            if(\OC_Config::getValue('sharing_group_mode') == 'Friend_mode') {
                $data[$row['uid']] = $row['nickname'];
            }
            else {
                $data[$row['uid']] = $row['displayname'];
            }
        }
        natcasesort($data);
        return $data;
    }

    /**
     *  Process the result and return the sharing group user
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @return array|null
     */
    private static function getGroupUserQueryResult($result) {
        $data = [];

        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);

            return;
        }

        while($row = $result->fetch()) {
            $data[] = $row['uid'];
        }
        natcasesort($data);
        return $data;
    }
    
    /**
     *  Process the result and return the key(user id) and the value(user displayname)
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @return array|null
     */
    private static function getDisplayNameQueryResult($result) {
        $data = [];

        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);

            return;
        }
        while($row = $result->fetch()) {
            $data[$row['uid']] = $row['displayname'];
        }
        
        return $data;
    }
 
    /**
     *  Process the result and return the sharing group friend id
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @return array|null
     */
    private static function getUsersFriendQueryResult($result) {
        $data = [];

        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);

            return;
        }
        while($row = $result->fetch()) {
            $data[] = $row['uid'];
        }

        return $data;
    }
 
    /**
     *  Process the result and return the sharing group id
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @return array|null
     */
    private static function getUserGroupQueryResult($result) {
        $data = [];

        if(DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);

            return;
        }

        while($row = $result->fetch()) {
            $data[] = $row['gid'];
        }

        return $data;
    }
    
    /**
     *  Process the result and return the sharing groups
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @param string $filter
     *  @return array|null
     */
    private static function getGroupsQueryResult($result, $filter) {
        $data = [];

        if (DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return;
        }


        while ($row = $result->fetchRow()) {
            $group = array('id' => $row['id'], 'name' => $row['name']);
            $filter ? strstr($row['name'], $filter) && array_push($data, $group) : array_push($data, $group);
        }
        
        return $data;
    }
    
    /**
     *  Process the result and return the users count
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @return int|null
     */
    private static function getEveryoneCountQueryResult($result) {
    
        if (DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return;
        }

        while ($row = $result->fetchRow()) {
            
            if(\OC_Config::getValue('sharing_group_mode') == 'Friend_mode') {
                $data = $row['COUNT(uid)'];
            }
            else {
                $data = $row['COUNT(uid)'] - 1 ;
            }
        }

        return $data;
    }
    
    /**
     *  Process the result and return the sharing group friends list
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @return array|null
     */
    private static function getFriendsListQueryResult($result) {
        $data = [];

        if (DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return;
        } 

        while ($row = $result->fetchRow()) {
            $data[$row['uid']]= $row['nickname'];
        }
        
        return $data;
    }    

    /**
     *  Process the result and return the sharing group id
     *  
     *  @param \OC_DB_StatementWrapper $result
     *  @return array|null
     */
    private static function getSharingQueryResult($result) {
        $data = [];

        if (DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return;
        } 

        while ($row = $result->fetchRow()) {
            $share = array('id' => $row['id']);
            array_push($data, $share);
        }
        
        return $data;
    }
    
    /**
     *  Process the csv file and return an array contains sharing group and group user 
     *  
     *  @param  $file csv file
     *  @return array
     */
    private static function importDataHanlder($files) {
        $result = [];
        $users = self::readAllUsers(); 

        if(\OC_Config::getValue('sharing_group_mode') == 'Friend_mode') {
            $handle = Filesystem::fopen($files,"r");
        }
        else {
            $handle = fopen($files['tmp_name'],"r");
        }
        
        while(($data = fgetcsv($handle, 0, ",")) !== FALSE) {
            
            $temp = []; 
            if($data[0] == '') { 
                continue; 
            }
            $temp['id'] = $data[0];
            $temp['group'] = $data[1];
            if (in_array($data[2],$users)) {
                if($data[2] == User::getUser()) {
                    $temp['uid'] = '';
                }
                else {
                    $temp['uid'] = $data[2] == '' ? '' : array($data[2]);
                }
                for($j = 0; $j < count($result); $j++) {
                    if($data[1] == $result[$j]['group'] && $data[2] != NULL) {
                        $result[$j]['uid'][] = $data[2];
                        break;
                    }
                }
            }
            $result[] = $temp;
        }
       
       return $result;
    }

}


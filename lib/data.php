<?php
namespace OCA\Sharing_Group;

use OCP\DB;
use OCP\User;
use OCP\Util;

class Data{
    
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
                    /*
                    for($i = 0; $i < count($share_check); $i++){
                        array_push($share_arr,$share_check[$i]['id']);
                    }
                    */
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

    public static function removeUserFromGroup($uid) {
        $query = DB::prepare('DELETE FROM `*PREFIX*sharing_group_user` WHERE `uid`= ?');
        
        $query->execute(array($uid));

    }

    public static function removeUserFromOwner($uid) {
        $query = DB::prepare('DELETE `*PREFIX*sharing_groups`, `*PREFIX*sharing_group_user` FROM `*PREFIX*sharing_groups` INNER JOIN `*PREFIX*sharing_group_user` WHERE `*PREFIX*sharing_groups`.uid = `*PREFIX*sharing_group_user`.owner AND `*PREFIX*sharing_groups`.uid= ?');
        
        $query->execute(array($uid));

    }


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

    public static function readGroups($user = '', $filter = '') {

        $user = $user !== '' ? $user : User::getUser();
        $query = DB::prepare('SELECT `id`, `name` FROM `*PREFIX*sharing_groups` WHERE `uid` = ?');
        $result = $query->execute(array($user));

        return self::getGroupsQueryResult($result, $filter);
    }
    
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
    
    public static function deleteGroup($gid) {
        $user = User::getUser();
        
        
        $sql = 'DELETE FROM `*PREFIX*sharing_group_user` WHERE `gid` = ?';
        $query = DB::prepare($sql);
        $delete_userInGroup = $query->execute(array($gid));
                                
        $sql = 'DELETE FROM `*PREFIX*sharing_groups` WHERE `id` = ?';
        $query = DB::prepare($sql);
        $delete = $query->execute(array($gid));
        
        /*
        $sql = 'DELETE `*PREFIX*sharing_groups`, `*PREFIX*sharing_group_user` FROM `*PREFIX*sharing_groups` INNER JOIN `*PREFIX*sharing_group_user` WHERE `*PREFIX*sharing_groups`.id = `*PREFIX*sharing_group_user`.gid AND `id` = ?';
        $query = DB::prepare($sql);
        $delete = $query->execute(array($gid));
        */
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

    public static function findGroupByName($name) {
        $user = User::getUser();
        $sql = 'SELECT `id` FROM `*PREFIX*sharing_groups` WHERE `name` = ? AND `uid` = ?';
        $query = DB::prepare($sql);
        $result = $query->execute(array($name, $user));
        
        return self::getGroupIdQueryResult($result);
    }
    
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
    
    public static function queryAllGroupsByUser() {
        $user = User::getUser();
        $sql = 'SELECT id, name , *PREFIX*sharing_group_user.uid FROM *PREFIX*sharing_groups LEFT OUTER JOIN *PREFIX*sharing_group_user ON *PREFIX*sharing_groups.id = *PREFIX*sharing_group_user.gid WHERE *PREFIX*sharing_groups.uid = ?';
        $query = DB::prepare($sql);

        return  $query->execute(array($user));
    }
    
    public static function export() {
        $result = self::queryAllGroupsByUser();
        //$result = $query->execute(array($user));
        $string = "";
        
        if (DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return 'error';
        }
        while ($row = $result->fetchRow()) {
            if($row['uid'] != NULL){
                $string .= '"' . $row['id'] . '", "' . $row['name'] . '", "' . $row['uid'] . '"' . "\n" ;
            }
            else {
                $string .= '"' . $row['id'] . '", "' . $row['name'] . '",' . "\n" ;
            }
        }
        
        return $string;
    }
    
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
   
    public static function findGroupById($id = '', $user = '') {
        $user = ($user !== '') ? $user : User::getUser();
        $sql = $id ? 'SELECT `id` ,`name` FROM `*PREFIX*sharing_groups` WHERE `id` = ?' : 'SELECT `id` ,`name` FROM `*PREFIX*sharing_groups` WHERE `uid` = ?';
        
        $query = DB::prepare($sql);
        $input = $id ? $id : $user;
        $result = $query->execute(array($input));

        return self::getGroupsQueryResult($result, '');
    }
    
    public static function findAllGroup() {
        $query = DB::prepare('SELECT `id` ,`name` FROM `*PREFIX*sharing_groups`');
        $result = $query->execute();
        
        return self::getGroupsQueryResult($result, '');
    }

    
    public static function countAllUsers() {
        $sql = 'SELECT COUNT(uid) FROM `*PREFIX*users`';
        $query = DB::prepare($sql);
        $result = $query->execute();
        
        return self::getEveryoneCountQueryResult($result); 
    }

    public static function readAllUsers() {
        $sql = 'SELECT `uid` FROM `*PREFIX*users`';
        $query = DB::prepare($sql);
        $result = $query->execute();
        
        return self::getGroupUserQueryResult($result); 
    }

    public static function readGroupUsers($id) {
        $sql = 'SELECT `uid` FROM `*PREFIX*sharing_group_user` WHERE `gid` = ?';
        $query = DB::prepare($sql);
        $result = $query->execute(array($id));
        
        return self::getGroupUserQueryResult($result); 
    }

    public static function readUserGroups($user) {
        $query = DB::prepare('SELECT `gid` FROM `*PREFIX*sharing_group_user` WHERE `uid` = ?');
        $result = $query->execute(array($user));
        
        return self::getUserGroupQueryResult($result);
    }

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

    public static function inGroup($uid, $gid) {
        $query = DB::prepare('SELECT `uid` FROM `*PREFIX*sharing_group_user` WHERE `gid` = ? AND `uid` = ?');
        $result = $query->execute(array($gid,$uid));

        if(DB::isError($result)) {
		    Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            return false;
        }

        return $result !== null;
    }
    
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

    private static function getEveryoneCountQueryResult($result) {
    
        if (DB::isError($result)) {
			Util::writeLog('SharingGroup', DB::getErrorMessage($result), Util::ERROR);
            
            return;
        }

        while ($row = $result->fetchRow()) {
            $data = $row['COUNT(uid)'] - 1 ;     
        }
        return $data;
    }
    
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

    private static function importDataHanlder($files) {
        $result = [];
        $users = self::readAllUsers(); 
        $handle = fopen($files['tmp_name'],"r");
        while(($data = fgetcsv($handle, 0, ",")) !== FALSE) {
            
            $temp = []; 
            if($data[0] == '') { 
                continue; 
            }
            $temp['id'] = $data[0];
            $temp['group'] = $data[1];
            if (in_array($data[2],$users)) {
                $temp['uid'] = $data[2] == '' ? '' : array($data[2]);
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


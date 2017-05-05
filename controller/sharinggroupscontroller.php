<?php
namespace OCA\Sharing_Group\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCA\Sharing_Group\Data;
use OCP\IRequest;
use OCP\User;
use OC\Files\Filesystem;

class SharingGroupsController extends Controller{
    
    /** @var Data */
   	protected $data;
    /** @var String*/
    protected $user;
    
    /**
     * @param String $appName
     * @param Data $data
     * @param IRequest $request
     * @param String $user CurrentUser 
     */
	public function __construct($appName, IRequest $request, Data $data, $user) {
		parent::__construct($appName, $request);
		$this->data = $data;
		$this->user = $user;
	}
    
    /**
     * @NoAdminRequired
     *
     * @param String $filter
     * @param DataResponse
     */
    public function getCategory($filter = '') {
        $result = $this->data->readForSearchlist($this->user , $filter);

        return new DataResponse($result); 
    }

    /**
     * @NoAdminRequired
     *
     * Add users to group or remove users from group
     * @param array $multigroup
     * @return JSONResponse
     */
    public function controlGroupUser($multigroup) {
        foreach($multigroup as $gid => $action) {
            $temp = [];
            $action = explode(':',$action);
            $users = explode(',',$action[1]);
            $temp[$action[0]] = $users;
            $multigroup[$gid] = $temp;
        }
        $result = $this->data->controlGroupUser($multigroup);

        return new JSONResponse(array('status' => $result));
    }
       
    /**
     * @NoAdminRequired
     *
     * create sharing group by name
     * @param String $name
     * @return JSONResponse
     */
    public function create($name,$password='') {
        $response = array();
        $response['status'] = $this->data->createGroups($name,$password);
        if($response['status'] == 'success') {
            $response['gid'] = $this->data->findGroupByName($name);
        }

        return new JSONResponse($response);
    }
   
    /**
     * @NoAdminRequired
     *  
     * Delete sharing group by sharing group id
     * @param int $gid
     * @return JSONResponse
     */
    public function deleteGroup($gid) {
        $result = $this->data->deleteGroup($gid);
        
        return new JSONResponse(array('status'=>$result));
    }
     
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * Import sharing group from csv file
     * @param csv file $data
     * @return DataResponse
     */
    public function importGroup($path) {
        if(\OC_Config::getValue('sharing_group_mode') == 'Friend_mode') {

            if(Filesystem::getMimeType($path) != 'text/csv') {
                
                return new DataResponse(['status' => 'error','msg' => 'Please select a CSV file.']);
            }
             
            $handle = Filesystem::fopen($path,"r");
            while(($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                $is_numeric = !is_numeric($data[0]);
                $check_group = preg_match("/(?=^\.|^_)|(?=\W+)(?!\.)/",$data[1]);
                $check_user = preg_match("/(?=^\.|^_|^\@)|(?=\W+)(?!\.)(?!\@)/",$data[2]);
                if($data[0] == '') {
                    continue;
                }
                
                if($is_numeric || $check_group || $check_user) {
                    
                    return new DataResponse(['status' => 'error','msg' => 'This is not a vaild CSV file.']);
                }
                
            }
            $gids = $this->data->importGroup($path);
            $msg = 'Importing groups and friend list successfully.';
        }
        else {
            $files = $this->request->getUploadedFile('fileToUpload'); 
            $gids = $this->data->importGroup($files);
            $msg = 'Importing groups and user successfully.';
        }
        
        return new DataResponse(['gids' => $gids, 'status' => 'success', 'msg' => $msg],Http::STATUS_OK);
    } 
    
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * Export sharing group to csv file
     * @return csv file $Download
     */
    public function export() {
        $data = $this->data->export();
        $fileName = User::getUser() . ".csv";
        $config = \OC::$server->getSystemConfig(); 

        if($config->getValue('sharing_group_mode') == 'Friend_mode') {
            \OC\Files\Filesystem::file_put_contents('/'.$fileName, $data);
        
            return new DataResponse(array('status' => 'success'));
        }
        else {
            $Download = new DataDownloadResponse($data , $fileName, 'text/csv');
        
            return $Download;
        }
    }
    
    /**
     * @NoAdminRequired
     * 
     * Rename sharing group by sharing group id
     * @param int $gid
     * @param String $newname
     * @return JSONResponse
     */
    public function renameGroup($gid, $newname) {
        $check = $this->data->findGroupByName($newname);
        if($check == '') {
            $result = $this->data->renameGroup($gid, $newname);
            return new JSONResponse(array('status' => $result));
        }
        return new JSONResponse(array('status' => 'error'));
    }
    
    /**
     * @NoAdminRequired
     * 
     * Get all sharing groups info
     * @return JSONResponse
     */
    public function getAllGroupsInfo() {
        $result = $this->data->getAllGroupsInfo(); 
        return new JSONResponse(array('data' => $result, 'status' => 'success'));
    }
    
    /**
     * @NoAdminRequired
     *
     * Get sharing group by sharing group id
     * @param int $id
     * @return JSONResponse
     */
    public function fetch($id = '') {
        $result = $this->data->findGroupById($id, $this->user);
        
        return new JSONResponse($result);
    }

    /**
     * @NoAdminRequired
     *
     * Get all sharing group
     * @return JSONResponse
     */
    public function fetchAll() {
        $result = $this->data->findAllGroup();
        
        return new JSONResponse($result);
    }

    /**
     * @NoAdminRequired
     *
     * Join sharing group
     * @return DataResponse
     */
    public function joinGroup($user,$groupId,$owner,$password=''){
        if (!$this->data->inGroup($user, $groupId)){
            return new DataResponse(array('message'=>"This user already joined the group." ,'status'=> 'error'));
        }
        
        if (User::getUser() !== $owner){
            if (!$this->data->checkPassword($groupId,$password)){
                return new DataResponse(array('message'=>"This password is not correct." ,'status'=> 'error'));
            }
        }
        
        $result = $this->data->joinGroup($user,$groupId,$owner);
        return new DataResponse(array('status' => $result));
    }

    /**
     * @NoAdminRequired
     *
     * Get all joined sharing groups
     * @return JSONResponse
     */
    public function getJoinedGroups(){
        $result = $this->data->getJoinedGroups();
        return new JSONResponse(array('data' => $result, 'status' => 'success'));
    }

    /**
     * @NoAdminRequired
     *
     * Add favorite sharing group
     * @return DataResponse
     */
    public function addFavoriteGroup($user,$groupId){
        $result = $this->data->addFavoriteGroup($user,$groupId);
        return new DataResponse(array('status' => $result));
    }
    
    /**
     * @NoAdminRequired
     *
     * Leave sharing group
     * @return DataResponse
     */
    public function leaveGroup($user,$groupId){
        if (!$this->data->inGroup($user, $groupId)){
            return new DataResponse(array('message'=>"This user not existed in the group." ,'status'=> 'error'));
        }
        $result = $this->data->leaveGroup($user,$groupId);
        return new DataResponse(array('status' => $result));
    }

    /**
     * @NoAdminRequired
     *
     * Leave favorite sharing group
     * @return DataResponse
     */
    public function leaveFavoriteGroup($user,$groupId){
        $result = $this->data->leaveFavoriteGroup($user,$groupId);
        return new DataResponse(array('status' => $result));
    }

    /**
     * @NoAdminRequired
     *
     * Get all favorite sharing groups by user
     * @return JSONResponse
     */
    public function getFavoriteGroups(){
        $result = $this->data->getFavoriteGroups();
        return new JSONResponse(array('data' => $result, 'status' => 'success'));
    }

    /**
     * @NoAdminRequired
     *
     * Get all joined sharing groups
     * @return JSONResponse
     */
    public function getCreatedGroups(){
        $result = $this->data->getCreatedGroups();
        return new JSONResponse(array('data' => $result, 'status' => 'success'));
    }
  
    /**
     * @NoAdminRequired
     *
     * Rename sharing group by sharing group id
     * @param int $gid
     * @param String $newname
     * @return JSONResponse
     */
    public function renameGroupPassword($gid, $newPasswd) {
        $result = $this->data->renameGroupPassword($gid, $newPasswd);
        return new JSONResponse(array('status' => $result));
    }

    /**
     * @NoAdminRequired
     *
     * Get all users in sharing group
     * @return JSONResponse
     */
    public function copyGroup($oldGid,$newGroupName) {
        $result = $this->data->copyGroup($oldGid,$newGroupName);
        return new JSONResponse(array('data' => $result, 'status' => 'success'));
    }

    /**
     * @NoAdminRequired
     *
     * Get all users in sharing group
     * @return JSONResponse
     */
    public function getUsersInGroup($gid) {
        $result = $this->data->getUsersInGroup($gid);
        return new JSONResponse(array('data' => $result, 'status' => 'success'));
    }
}

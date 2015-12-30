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

class SharingGroupsController extends Controller{

   	protected $data;
    protected $user;

	public function __construct($appName, IRequest $request, Data $data, $user) {
		parent::__construct($appName, $request);
		$this->data = $data;
		$this->user = $user;
	}
    
    /**
     * @NoAdminRequired
     */
    public function getCategory($filter = '') {
        $result = $this->data->readForSearchlist($this->user , $filter);

        return new DataResponse($result); 
    }

    /**
     * @NoAdminRequired
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
     */
    public function create($name) {
        $response = array();
        $response['status'] = $this->data->createGroups($name);
        if($response['status'] == 'success') {
            $response['gid'] = $this->data->findGroupByName($name);
        }

        return new JSONResponse($response);
    }
    
    /**
     * @NoAdminRequired
     */
    public function deleteGroup($gid) {
        $result = $this->data->deleteGroup($gid);
        
        return new JSONResponse(array('status'=>$result));
    }
     
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function importGroup($data) {
        $files = $this->request->getUploadedFile('fileToUpload'); 
        $gids = $this->data->importGroup($files);

        return new DataResponse(['gids' => $gids, 'status' => 'success'],Http::STATUS_OK);
    } 
    
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function export() {
        $data = $this->data->export();
        $fileName = User::getUser() . ".csv";
        $Download = new DataDownloadResponse($data , $fileName, 'text/csv');
        
        return $Download;
    }
    
    /**
     * @NoAdminRequired
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
     */
    public function getAllGroupsInfo() {
        $result = $this->data->getAllGroupsInfo(); 
        return new JSONResponse(array('data' => $result, 'status' => 'success'));
    }
    
    /**
     * @NoAdminRequired
     */
    public function fetch($id = '') {
        $result = $this->data->findGroupById($id, $this->user);
        
        return new JSONResponse($result);
    }

    /**
     * @NoAdminRequired
     */
    public function fetchAll() {
        $result = $this->data->findAllGroup();
        
        return new JSONResponse($result);
    }
}

<?php
namespace OCA\Sharing_Group\API;

use OCP\AppFramework\Http;
use OCP\Contacts\IManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IURLGenerator;
use OCP\Share;
use OCA\Sharing_Group\Data;

class Sharees extends \OCA\Files_Sharing\API\Sharees {
    /** @var array */
	protected $result = [
		'exact' => [
			'users' => [],
			'groups' => [],
            'sharing_groups' => [],
			'remotes' => [],
		],
		'users' => [],
		'groups' => [],
        'sharing_groups' => [],
		'remotes' => [],
	];
    
    /**
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param IManager $contactsManager
	 * @param IConfig $config
	 * @param IUserSession $userSession
	 * @param IURLGenerator $urlGenerator
	 * @param IRequest $request
	 * @param ILogger $logger
	 */
	public function __construct(IGroupManager $groupManager,
								IUserManager $userManager,
								IManager $contactsManager,
								IConfig $config,
								IUserSession $userSession,
								IURLGenerator $urlGenerator,
								IRequest $request,
								ILogger $logger) {

    
        parent::__construct($groupManager, $userManager, $contactsManager, $config, $userSession, $urlGenerator, $request, $logger);

    }
    
    protected function getSharingGroups($search) {
        $this->result['sharing_groups'] = $this->result['exact']['sharing_groups'] = [];
        $groups = Data::readGroups('', $search, $this->limit, $this->offset);
        foreach($groups as $group) {
            if(strtolower($group['name']) === $search) {
                $this->result['exact']['sharing_groups'][] = [
                    'label' => $search,
                    'value' => [
                        'shareType' => Share::SHARE_TYPE_SHARING_GROUP,
                        'shareWith' => $group['id'],
                    ],
                ];
            } else {
                $this->result['sharing_groups'][] = [
                    'label' => $group['name'],
                    'value' => [
                        'shareType' => Share::SHARE_TYPE_SHARING_GROUP,
                        'shareWith' => $group['id'],
                    ],
                ];
            }  
        }


    
    }

    /**
	 * @return \OC_OCS_Result
	 */
	public function search() {
		$search = isset($_GET['search']) ? (string) $_GET['search'] : '';
		$itemType = isset($_GET['itemType']) ? (string) $_GET['itemType'] : null;
		$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
		$perPage = isset($_GET['perPage']) ? (int) $_GET['perPage'] : 200;

		if ($perPage <= 0) {
			return new \OC_OCS_Result(null, Http::STATUS_BAD_REQUEST, 'Invalid perPage argument');
		}
		if ($page <= 0) {
			return new \OC_OCS_Result(null, Http::STATUS_BAD_REQUEST, 'Invalid page');
		}

		$shareTypes = [
			Share::SHARE_TYPE_USER,
			Share::SHARE_TYPE_GROUP,
			Share::SHARE_TYPE_REMOTE,
            Share::SHARE_TYPE_SHARING_GROUP,
		];
		if (isset($_GET['shareType']) && is_array($_GET['shareType'])) {
			$shareTypes = array_intersect($shareTypes, $_GET['shareType']);
			sort($shareTypes);

		} else if (isset($_GET['shareType']) && is_numeric($_GET['shareType'])) {
			$shareTypes = array_intersect($shareTypes, [(int) $_GET['shareType']]);
			sort($shareTypes);
		}

		if (in_array(Share::SHARE_TYPE_REMOTE, $shareTypes) && !$this->isRemoteSharingAllowed($itemType)) {
			// Remove remote shares from type array, because it is not allowed.
			$shareTypes = array_diff($shareTypes, [Share::SHARE_TYPE_REMOTE]);
		}

		$this->shareWithGroupOnly = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
		$this->shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$this->limit = (int) $perPage;
		$this->offset = $perPage * ($page - 1);

		return $this->searchSharees(strtolower($search), $itemType, $shareTypes, $page, $perPage);
	} 

    /**
	 * Testable search function that does not need globals
	 *
	 * @param string $search
	 * @param string $itemType
	 * @param array $shareTypes
	 * @param int $page
	 * @param int $perPage
	 * @return \OC_OCS_Result
	 */
	protected function searchSharees($search, $itemType, array $shareTypes, $page, $perPage) {
		// Verify arguments
		if ($itemType === null) {
			return new \OC_OCS_Result(null, Http::STATUS_BAD_REQUEST, 'Missing itemType');
		}

		// Get users
		if (in_array(Share::SHARE_TYPE_USER, $shareTypes)) {
			$this->getUsers($search);
		}

		// Get groups
		if (in_array(Share::SHARE_TYPE_GROUP, $shareTypes)) {
			$this->getGroups($search);
		}

		// Get remote
		if (in_array(Share::SHARE_TYPE_REMOTE, $shareTypes)) {
			$this->getRemote($search);
		}

        // Get sharing_groups
		if (in_array(Share::SHARE_TYPE_SHARING_GROUP, $shareTypes)) {
			$this->getSharingGroups($search);
		}

		$response = new \OC_OCS_Result($this->result);
		$response->setItemsPerPage($perPage);

		if (sizeof($this->reachedEndFor) < 3) {
			$response->addHeader('Link', $this->getPaginationLink($page, [
				'search' => $search,
				'itemType' => $itemType,
				'shareType' => $shareTypes,
				'perPage' => $perPage,
			]));
		}

		return $response;
	}

}

?>

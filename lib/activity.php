<?php

namespace OCA\Sharing_Group;

use OCP\Activity\IExtension;
use OC\L10N\Factory;

class Activity implements IExtension {

    const SUBJECT_SHARED_SHARING_GROUP_SELF = 'shared_sharing_group_self';
    const TYPE_SHARED = 'shared';
     
    protected $languageFactory;
    protected $groups = array();


    public function __construct(Factory $languageFactory) {
		$this->languageFactory = $languageFactory;

        foreach(Data::findAllGroup() as $group) {
            $this->groups[$group['id']] = $group['name']; 
        }
	}
    

    public function getNotificationTypes($languageCode) {
        $l = $this->getL10N($languageCode);

        return [self::TYPE_SHARED => (string) $l->t('A file or folder has been <strong>shared</strong>'),];
    }

	protected function getL10N($languageCode = null) {
		return $this->languageFactory->get('sharing_group', $languageCode);
	}
    public function getDefaultTypes($method) {
       return [self::TYPE_SHARED,];
       
    }

    public function getTypeIcon($type) {
        return 'icon-share'; 
    }

	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		$l = $this->getL10N($languageCode);
                
        if($app === 'sharing_group') {
            if($text ===  self::SUBJECT_SHARED_SHARING_GROUP_SELF) {
                if($this->handleparam($params[1])) {
                    $params[1] = $this->handleParam($params[1]); 
                    return (string) $l->t('You shared %1$s with sharing group %2$s', $params);
                } else {
                    return (string) $l->t('You shared %1$s with a sharing group which has been deleted',$params);
                }
            }
        }

    }

    public function getSpecialParameterList($app, $text) {
        if ($app === 'sharing_group') {
            if($text === self::SUBJECT_SHARED_SHARING_GROUP_SELF) {
                return [
                    0 => 'file',
                    //1 => 'group', Group does not exist yet
                ];
            }
        }
    }
    public function getGroupParameter($activity) {
        if ($activity['app'] === 'files') {
             if($activity['subject'] === self::SUBJECT_SHARED_SHARING_GROUP_SELF) {
                return 1;
             }
        }

        return false;
    }

    public function isFilterValid($filterValue) {
		return $filterValue === 'shares';
	}

    public function getNavigation() {
        return false;
    }

    public function filterNotificationTypes($types, $filter) {
        switch ($filter) {
			case 'shares':
				return array_intersect([self::TYPE_SHARED,], $types);
		}
		return false;

    }

    public function getQueryForFilter($filter) {
        if ($filter === 'shares') {
			return [
				'`app` = ?',
				['shares',],
			];
		}
    }

    protected function handleParam($param) {
        if(isset($this->groups[strip_tags($param)])) {
            $param = '<strong>'.$this->groups[strip_tags($param)].'</strong>';
            return $param;
        } 
           
        return false;
    }
}

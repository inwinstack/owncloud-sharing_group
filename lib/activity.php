<?php

namespace OCA\Sharing_Group;

use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;

class Activity implements IExtension {

    const SUBJECT_SHARED_SHARING_GROUP_SELF = 'shared_sharing_group_self';
    const SUBJECT_RESHARED_SHARING_GROUP_BY = 'reshared_sharing_group_by';
    const TYPE_SHARED = 'shared';
	const SUBJECT_SHARED_WITH_BY = 'shared_with_by';
     
    protected $languageFactory;
    protected $URLGenerator;
	protected $activityManager;
    protected $groups = array();


    public function __construct(IFactory $languageFactory, IURLGenerator $URLGenerator, IManager $activityManager) {
		$this->languageFactory = $languageFactory;
        $this->URLGenerator = $URLGenerator;
        $this->activityManager = $activityManager;
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
		if ($app !== 'sharing_group') {
			return false;
		}

		$l = $this->getL10N($languageCode);

		if ($this->activityManager->isFormattingFilteredObject()) {
			$translation = $this->translateShort($text, $l, $params);
			if ($translation !== false) {
				return $translation;
			}
		}

		return $this->translateLong($text, $l, $params);
	}

    protected function translateLong($text, IL10N $l, array $params) {
		switch ($text) {
            case self::SUBJECT_SHARED_WITH_BY:
				return (string) $l->t('%2$s shared %1$s with you', $params);

           	case self::SUBJECT_RESHARED_SHARING_GROUP_BY:
                if($this->handleParam($params[2])) {
                    $params[2] = $this->handleParam($params[2]);
                    return (string) $l->t('%2$s shared %1$s with sharing group %3$s (from %2$s)', $params);
                } else {
                    return (string) $l->t('%2$s shared %1$s with a sharing group which has been deleted',$params);
                }

            case self::SUBJECT_SHARED_SHARING_GROUP_SELF:
                if($this->handleParam($params[1])) {
                    $params[1] = $this->handleParam($params[1]);
				    return (string) $l->t('You shared %1$s with sharing group %2$s', $params);
                } else {
                    return (string) $l->t('You shared %1$s with a sharing group which has been deleted',$params);
                }
        }

        return false;
    }
    

    protected function translateShort($text, IL10N $l, array $params) {
		switch ($text) {
            case self::SUBJECT_SHARED_WITH_BY:
				return (string) $l->t('Shared by %2$s', $params);

           	case self::SUBJECT_SHARED_SHARING_GROUP_SELF:
                if($this->handleParam($params[1])) {
                    $params[1] = $this->handleParam($params[1]);
				    return (string) $l->t('Shared with sharing group %2$s', $params);
                } else {
                    return (string) $l->t('Shared with a sharing group which has been deleted');
                }

           	case self::SUBJECT_RESHARED_SHARING_GROUP_BY:
                if($this->handleParam($params[2])) {
                    $params[2] = $this->handleParam($params[2]);
                    return (string) $l->t('Shared with sharing group %3$s by %2$s', $params);
                } else {
                    return (string) $l->t('Shared with a sharing group which has been deleted by %2$s',$params);
                }
        }
        return false;

    }

    public function getSpecialParameterList($app, $text) {
        if ($app === 'sharing_group') {
            switch($text) {
                case self::SUBJECT_SHARED_SHARING_GROUP_SELF:
                    return [
                        0 => 'file',
                        1 => 'group' 
                    ];
                case self::SUBJECT_SHARED_WITH_BY:
					return [0 => 'file', 1 => 'username'];
               case self::SUBJECT_RESHARED_SHARING_GROUP_BY:
					return [
						0 => 'file',
						1 => 'username',
						2 => 'group',
					];
            }
        }
    }
    public function getGroupParameter($activity) {
        if ($activity['app'] === 'sharing_group') {
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
				['sharing_group',],
			];
		}
        return false;
    }

    protected function handleParam($param) {
        if(isset($this->groups[strip_tags($param)])) {
            $param = '<strong>'.$this->groups[strip_tags($param)].'</strong>';
            return $param;
        } 
           
        return false;
    }
}

<?php
/**
 * ownCloud - sharing_group 
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author simon <simon.l@inwinstack.com>
 * @copyright simon 2015
 */

namespace OCA\Sharing_Group\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCA\Sharing_Group\Data;

class PageController extends Controller {

    public function __construct($AppName, IRequest $request, $UserId){
        parent::__construct($AppName, $request);
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $params = ['everyone' => Data::countAllUsers()];
        return new TemplateResponse('sharing_group', 'main', $params);  // templates/main.php
    }
}

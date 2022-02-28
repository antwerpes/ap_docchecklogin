<?php

namespace Antwerpes\ApDocchecklogin;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 antwerpes ag <opensource@antwerpes.de>
 *  All rights reserved
 *
 *  The TYPO3 Extension ap_docchecklogin is licensed under the MIT License
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Service 'DocCheckAuthenticationService' for the 'ap_docchecklogin' extension.
 *
 * @author    Lukas Domnick <lukas.domnick@antwerpes.de>
 *
 * @method fetchUserRecord($dummyUserName)
 */

use Antwerpes\ApDocchecklogin\Utility\OauthUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;


class DocCheckAuthenticationService extends \TYPO3\CMS\Core\Authentication\AuthenticationService
{
    protected $extConf = [];

    public function __construct()
    {
        $this->extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ap_docchecklogin'];
    }

    public function initAuth($mode, $loginData, $authInfo, $pObj)
    {
        $authInfo['db_user']['checkPidList'] = $this->extConf['dummyUserPid'];
        $authInfo['db_user']['check_pid_clause'] = ' AND pid = '.$authInfo['db_user']['checkPidList'].' ';

        parent::initAuth($mode, $loginData, $authInfo, $pObj);
    }

    /**
     * Bypass login for crawling.
     *
     * @throws \Exception
     */
    public function bypassLoginForCrawling()
    {
        // No crawling.
        if (!$this->extConf['crawlingEnable']) {
            return;
        }

        // Crawler IP
        $crawlingIP = $this->extConf['crawlingIP'];
        if (!$crawlingIP) {
            throw new \Exception('DocCheck Authentication: No DocCheck Crawler IP specified in Extension settings');
        }

        // IP not matching
        if ($_SERVER['REMOTE_ADDR'] !== $crawlingIP) {
            return;
        }

        // Crawler user.
        $crawlingUserName = $this->extConf['crawlingUser'];
        if (!$crawlingUserName) {
            $crawlingUserName = $this->extConf['dummyUser'];
        }

        $GLOBALS['TSFE']->fe_user->createUserSession([]);
        $GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->getRawUserByName($crawlingUserName);
        $GLOBALS['TSFE']->fe_user->fetchGroupData();
        $GLOBALS['TSFE']->loginUser = 1;
    }

    /**
     * Helper function to get the generic dummy user record.
     *
     * @throws \Exception
     */
    private function getDummyUser()
    {
        $dummyUserName = $this->extConf['dummyUser'];

        if (!$dummyUserName) {
            throw new \Exception('DocCheck Authentication: No Dummy User specified in Extension settings');
        }

        $user = $this->fetchUserRecord($dummyUserName);

        if (!$user) {
            throw new \Exception('DocCheck Authentication: Dummy User '.$dummyUserName.' was not found on the Page with the ID '.$this->extConf['dummyUserPid']);
        }

        return $user;
    }

    /**
     * Fetch or create a unique user.
     *
     * @param $uniqKey string
     * @param $dcVal string for routing, if wanted
     *
     * @throws \Exception
     *
     * @return array user array
     */
    protected function getUniqueUser($uniqKey, $dcVal)
    {
        if (!$this->isValidMd5($uniqKey)) {
            throw new \Exception('DocCheck Authentication: unique key is not valid.');
        }
        $group = $this->getUniqueUserGroupId($dcVal);

        // try and fetch the user
        $username = $this->generateUserNameFromUniqueKey($uniqKey);
        $userObject = $this->fetchUserRecord($username);

        if ($userObject) {
            // cool, we know you already? nice!
            return $userObject;
        }
        // else: we dont have a record for this user yet

        $userObject = $this->createUserRecord($username, $group, $this->extConf['dummyUserPid']);
        if ($userObject) {
            // cool, you were created.
            return $userObject;
        }

        throw new \Exception('DocCheck Authentication: Could not find or create an automated fe_user');
    }

    /**
     * @param $username string the username (generated)
     * @param $group int group id
     * @param $pid int page id where the user record is created
     *
     * @return array User or FALSE
     */
    protected function createUserRecord($username, $group, $pid)
    {
        $dbUser = $this->db_user;
        $insertArray = [];

        $insertArray[$dbUser['username_column']] = $username;
        $insertArray['pid'] = $pid;
        $insertArray[$dbUser['usergroup_column']] = $group;
        $insertArray['crdate'] = $insertArray['tstamp'] = time();

        // add a salted random password
        $insertArray[$dbUser['userident_column']] = md5(rand().time().$username.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);

        // $res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($dbUser['table'], $insertArray);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($dbUser['table']);
        $res = $queryBuilder
        ->insert($dbUser['table'])
        ->values($insertArray)
        ->execute();

        // get the newly created user
        return $this->fetchUserRecord($username);
    }

    /**
     * generate a user name for this unique key. Just adds a prefix, actually, for now.
     *
     * @param $uniqKey string
     *
     * @return string
     */
    protected function generateUserNameFromUniqueKey($uniqKey)
    {
        return 'dc_'.$uniqKey;
    }

    /**
     * If DocCheck Personal parameters are detected, add them to the user object.
     *
     * @param $user array the user record
     *
     * @return array the updated user record
     */
    protected function augmentDcPersonal($user)
    {
        $paramMapping = [
            // dc => typo3
            'dc_titel'   => 'title',
            'dc_vorname' => 'first_name',
            'dc_name'    => 'last_name',
            'dc_strasse' => 'address',
            'dc_plz'     => 'zip',
            'dc_ort'     => 'city',
            'dc_land'    => 'country',
            'dc_email'   => 'email',
            // doccheck profession and discipline: see the official technical documentation at https://crm.doccheck.com/
            'dc_beruf'      => 'tx_apdocchecklogin_prof',
            'dc_fachgebiet' => 'tx_apdocchecklogin_disc',
        ];

        $updateArr = [];
        foreach ($paramMapping as $dcFieldname => $typo3Fieldname) {
            // only touch the fields that have been provided by dcPersonal
            if ($_GET[$dcFieldname]) {
                $val = utf8_encode($_GET[$dcFieldname]);
                $user[$typo3Fieldname] = $val;
                $updateArr[$typo3Fieldname] = $val;
            }
        }

        if (count($updateArr) > 0) {
            // save the changes to db
            // $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->db_user['table'], 'uid=' . $user['uid'], $updateArr);
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->db_user['table']);
            $queryBuilder
                ->update($this->db_user['table'])
                ->where(
                    $queryBuilder->expr()->eq('uid', $user['uid']) // if 120 would be a user parameter, use $queryBuilder->createNamedParameter($param) for security reasons
            );
            foreach($updateArr as $updKey => $updVal) {
                $queryBuilder->set($updKey, $updVal);
            }
            $queryBuilder->execute();
        }

        return $user;
    }

    /**
     * get the group, into which generated users are supposed to be added. this can be a static configured group, or
     * - in combination with the routing feature, a resolved group id.
     *
     * @param $dcVal
     *
     * @throws \Exception
     *
     * @return int group id
     */
    protected function getUniqueUserGroupId($dcVal)
    {

        // is routing enabled?
        if ($this->extConf['routingEnable']) {
            $grp = $this->getRoutedGroupId($dcVal);
            if (!$grp) {
                // error, because no group is set to match the given $_GET['dc'] parameter.
                throw new \Exception('DocCheck Authentication: No suitable routing found.');
            }
        } else {
            $grp = $this->extConf['uniqueKeyGroup'];
            if (!$grp) {
                throw new \Exception('DocCheck Authentication: No uniqueKeyGroup set.');
            }
        }

        // cast as int
        $grp = intval($grp, 10);

        if (null === $this->fetchGroupRecord($grp, $this->extConf['dummyUserPid'])) {
            // whoops, no group found
            throw new \Exception('DocCheck Authentication: Could not find front end user group '.$grp);
        }

        return $grp;
    }

    /**
     * Fetch the group record for a given id, on a specific PID.
     *
     * @param $groupId int
     * @param $pid int Page ID where the group is stored
     *
     * @return array group record, or null if no matching group was found
     */
    protected function fetchGroupRecord($groupId, $pid)
    {
        if (!is_int($groupId) || 0 === $groupId) {
            return null;
        }

        $group = null;

        $dbGroups = $this->db_groups;

        $groupIdClause = 'uid = '.intval($groupId, 10).' AND pid = '.intval($pid).' AND deleted = 0 AND hidden = 0';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($dbGroups['table']);
        $statement = $queryBuilder->select('*')
            ->from($dbGroups['table'])
            ->where($groupIdClause)
            ->execute();
        $group = $statement->fetch();

        return $group;
    }

    /**
     * Read the routing map and find a suitable group id for this user.
     *
     * @param $dcVal string
     *
     * @return int ID of the associated group, or null if none found
     */
    protected function getRoutedGroupId($dcVal)
    {
        // first, explode the route map
        $routingMapStr = $this->extConf['routingMap'];
        $routingMapStr = explode(',', $routingMapStr);
        foreach ($routingMapStr as $routeItem) {
            list($grp, $dcParam) = explode('=', $routeItem);
            if ($dcParam === $dcVal) {
                return $grp;
            }
        }

        return null;
    }

    protected function isValidMd5($md5)
    {
        return !empty($md5) && preg_match('/^[a-f0-9]{32}$/', $md5);
    }

    /**
     * Retrieve the Dummy User whenever we come from the DocCheck Service.
     *
     * @throws \Exception
     *
     * @return mixed Array of all users matching current IP
     */
    public function getUser()
    {
        $dcVal = $_GET['dc'];
        mail('sabrina.zwirner@antwerpes.de', 'Global', print_r( time(), 1));

        // if no dc param is given - let's not even bother getting the dummy user
        if (!$dcVal || strlen($dcVal) === 0) {
            return null;
        }

        // if we are not using uniquekey feature, just get the dummy user...
        if (!$this->extConf['uniqueKeyEnable']) {
            $user = $this->getDummyUser();
        } else {
            $user = $this->getUniqueUser($_GET['uniquekey'], $dcVal);
            if ($this->extConf['dcPersonalEnable']) {
                $user = $this->augmentDcPersonal($user);
            }
        }

        return $user;
    }

    /**
     * Authenticate a user
     * Return 200 if the DocCheck Login is okay. This means that no more checks are needed. Otherwise authentication may fail because we may don't have a password.
     *
     * @param $user array Data of user.
     *
     * @return bool|200|100
     */
    public function authUser(array $user): int
    {
        // return values:
        // 200 - authenticated and no more checking needed - useful for IP checking without password
        // 100 - Just go on. User is not authenticated but there's still no reason to stop.
        // false - this service was the right one to authenticate the user but it failed
        // true - this service was able to authenticate the user

        $dcVal = $_GET['dc'];

        //Check if needed Parameter for oauth are given
        //Else try to auth the Dummyuser
        if($_GET['code'] && $this->extConf['clientSecret'] && $this->extConf['uniqueKeyEnable']){
            $ok = $this->authUniqueUser($user, $dcVal);
        }else{
            $ok = $this->authDummyUser($user, $dcVal);
        }

        // cool, some auth method thought it's fine. Quickly configure the redirect feature.
        if ($ok === 200) {
            if ($this->extConf['useFeLoginRedirect'] === '1') {
                // TODO: Find a better place to store this bit of information
                $GLOBALS['ap_docchecklogin_do_redirect'] = true;

                $hookParams = ['user' => $user, 'ok' => $ok];
                $ok = $hookParams['ok'];
            }
        }

        return $ok;
    }

    /**
     * Check whether
     * ... the given user is the dummy user
     * ... the dummy may sign in with this dc-param.
     *
     * @param $user
     * @param string
     *
     * @return bool|100|200
     */
    protected function authDummyUser($user, $dcVal)
    {
        if (!$this->isDummyUser($user)) {
            // oops, not the dummy user. Try other auth methods.
            return 100;
        }

        // now check whether we have the valid dc param

        if (strlen($dcVal) > 0 && $dcVal === $this->extConf['dcParam']) {
            return 200;
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    protected function authUniqueUser($user, $dcVal)
    {
        if (!$this->isUniqueUser($user)) {
            // not a unique user, try other auth methods.
            return 100;
        }
        // find the correct group
        $expectedGroupId = $this->getUniqueUserGroupId($dcVal);
        $actualGroupId = intval($user[$this->db_user['usergroup_column']]);

        // the given dcval does not match any configured group id
        if (!$actualGroupId) {
            return false;
        }

        // is the unqiueUser in the expected group?
        if ($expectedGroupId !== $actualGroupId) {
            // nope.
            return false;
        }

        //Authenticate the User via Dc Login
        $oauth = new OauthUtility();
        $authenticateUser = $oauth->generateToken($_GET['login_id'], $this->extConf['clientSecret'], $_GET['code']);
        if($authenticateUser){
            return 200;
        }else{
            return false;
        }
    }

    /**
     * Find out whether a given user is the dummy (non-unique).
     *
     * @param $user
     *
     * @return bool
     */
    protected function isDummyUser($user)
    {
        // wait, are we supposed to use unique key? then how can this be a dummy user?
        if ($this->extConf['uniqueKeyEnable']) {
            return false;
        }

        return (int) $user['pid'] === (int) $this->extConf['dummyUserPid']
            && $user['username'] === $this->extConf['dummyUser'];
    }

    /**
     * Detect whether a given user has been generated by this extension.
     *
     * @param $user
     *
     * @return bool
     */
    protected function isUniqueUser($user)
    {
        // if uniquekey is not even enabled, this can't be a unique key user.
        if (!$this->extConf['uniqueKeyEnable']) {
            return false;
        }

        // if the pid is incorrect, break
        if ((int) $user['pid'] !== (int) $this->extConf['dummyUserPid']) {
            return false;
        }

        // match the user name pattern
        if (!preg_match('/^dc_[0-9a-f]{32}$/i', $user[$this->db_user['username_column']])) {
            return false;
        }

        return true;
    }
}

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
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;


/**
 * Service 'DocCheckAuthenticationService' for the 'ap_docchecklogin' extension.
 *
 * @author	Lukas Domnick <lukas.domnick@antwerpes.de>
 */

class DocCheckAuthenticationService extends \TYPO3\CMS\Sv\AbstractAuthenticationService {
	protected $extConf = array();

	public function __construct() {
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ap_docchecklogin']);

	}

	public function initAuth($mode, $loginData, $authInfo, $pObj) {
		$authInfo['db_user']['checkPidList'] = $this->extConf['dummyUserPid'];
		$authInfo['db_user']['check_pid_clause'] = ' AND pid = '. $authInfo['db_user']['checkPidList'] .' ';

		parent::initAuth($mode, $loginData, $authInfo, $pObj );
	}

	/**
	 * Helper function to get the generic dummy user record.
	 */
	private function getDummyUser() {
		$dummyUserName = $this->extConf['dummyUser'];

		if(!$dummyUserName) {
			throw new \Exception('DocCheck Authentication: No Dummy User specified in Extension settings');
		}

		$user = $this->fetchUserRecord( $dummyUserName );

		if ( !$user ) {
			throw new \Exception('DocCheck Authentication: Dummy User ' . $dummyUserName . ' was not found on the Page with the ID ' . $this->extConf['dummyUserPid'] );
		}

		return $user;
	}

	/**
	 * Fetch or create a unique user.
	 *
	 * @param $uniqKey
	 * @param $dcVal for routing, if wanted
	 */
	private function getUniqueUser( $uniqKey, $dcVal ) {
		if( !$this->isValidMd5($uniqKey)) {
			throw new \Exception('DocCheck Authentication: unique key is not valid.');
		}
		$group = $this->getUniqueUserGroupId($dcVal);

		// try and fetch the user
		$username = $this->generateUserNameFromUniqueKey($uniqKey);
		$userObject = $this->fetchUserRecord($username);

		if( $userObject ) {
			// cool, we know you already? nice!
			return $userObject;
		}
		// else: we dont have a record for this user yet

		$userObject = $this->createUserRecord($username, $group, $this->extConf['dummyUserPid']);
		if( $userObject ) {
			// cool, we know you already? nice!
			return $userObject;
		}

		throw new \Exception('DocCheck Authentication: Could not find or create an automated fe_user' );
	}

	private function createUserRecord($username, $group, $pid ) {
		$dbUser = $this->db_user;
		$insertArray = array();

		$insertArray[ $dbUser['username_column']] = $username;
		$insertArray['pid'] = $pid;
		$insertArray[ $dbUser['usergroup_column']] = $group;
		$insertArray['crdate'] = $insertArray['tstamp'] = time();

		// add a salted random password
		$insertArray[ $dbUser['userident_column']] = md5( rand() . time() . $username . $GLOBALS["TYPO3_CONF_VARS"]["SYS"]["encryptionKey"]);

		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($dbUser['table'], $insertArray);

		// get the newly created user
		$user = $this->fetchUserRecord($username);
		return $user;
	}

	/**
	 * generate a user name for this unique key. Just adds a prefix, actually, for now.
	 *
	 * @param $uniqKey
	 */
	private function generateUserNameFromUniqueKey($uniqKey) {
		return 'dc_' . $uniqKey;
	}


	/**
	 * If DocCheck Personal parameters are detected, add them to the user object.
	 *
	 * @param $user User
	 */
	private function augmentDcPersonal($user) {

		$paramMapping =  array(
			// dc => typo3
			'dc_titel' => 'title',
			'dc_vorname' => 'first_name',
			'dc_name' => 'last_name',
			'dc_strasse' => 'address',
			'dc_plz' => 'zip',
			'dc_ort' => 'city',
			'dc_land' => 'country',
			'dc_email' => 'email'
		);

		$updateArr = array();
		foreach( $paramMapping as $dcFieldname => $typo3Fieldname) {
			// only touch the fields that have been provided by dcPersonal
			if( $_GET[$dcFieldname] ) {
				$val = utf8_encode($_GET[$dcFieldname]);
				$user[$typo3Fieldname] = $val;
				$updateArr[$typo3Fieldname] = $val;
			}
		}

		if( count($updateArr) > 0 ) {
			// save the changes to db
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->db_user['table'], 'uid=' . $user['uid'], $updateArr);
		}

	/*		[dc] => 299
		[logintype] => login
		[dc_anrede] => Herr
		[dc_gender] => m
		[dc_titel] =>
		[dc_name] => Domnick
		[dc_strasse] => Vogelsanger Stra�e 144
		[dc_plz] => 50823
		[dc_ort] => K�ln
		[dc_land] => DE
		[dc_beruf] => 61
		[dc_fachgebiet] => 1045
		[dc_email] => lukas.domnick@antwerpes.de
		[uniquekey] => 1178a74d08e7e027ee35e905ab927b26
		[dc_timestamp] => 1381758200
	)*/

		return $user;
	}

	/**
	 * get the group, into which generated users are supposed to be added. this can be a static configured group, or
	 * - in combination with the routing feature, a resolved group id.
	 *
	 * @param $dcVal
	 * @return int
	 * @throws \ErrorException
	 */
	private function getUniqueUserGroupId( $dcVal ) {

		// is routing enabled?
		if( $this->extConf['routingEnable']) {
			$grp = $this->getRoutedGroupId($dcVal);
			if( !$grp ) {
				// error, because no group is set to match the given $_GET['dc'] parameter.
				throw new \Exception('DocCheck Authentication: No suitable routing found.' );
			}
		} else {
			$grp = $this->extConf['uniqueKeyGroup'];
			if( !$grp ) {
				throw new \Exception('DocCheck Authentication: No uniqueKeyGroup set.' );
			}
		}

		// cast as int
		$grp = intval($grp, 10);

		if( false === $this->fetchGroupRecord($grp, $this->extConf['dummyUserPid']) ) {
			// whoops, no group found
			throw new \Exception('DocCheck Authentication: Could not find front end user group ' . $grp );
		}

		return $grp;
	}


	/**
	 * Fetch the group record for a given id, on a specific PID
	 *
	 * @param $groupId
	 * @param $pid
	 * @return bool
	 */
	private function fetchGroupRecord($groupId, $pid )   {

		if( !is_integer($groupId) || 0 === $groupId ) {
			return false;
		}

		$group = FALSE;

		$dbGroups = $this->db_groups;

		$groupIdClause = 'uid = ' . intval($groupId,10) . ' AND pid = ' . intval($pid) . ' AND deleted = 0 AND hidden = 0';

		// Look up the user by the username and/or extraWhere:
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$dbGroups['table'],
			$groupIdClause
		);


		if ($dbres) {
			$group = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
			$GLOBALS['TYPO3_DB']->sql_free_result($dbres);
		}

		return $group;
	}

	/**
	 * Read the routing map and find a suitable group id for this user
	 *
	 * @param $dcVal
	 * @return null
	 */
	private function getRoutedGroupId($dcVal) {
		// first, explode the route map
		$routingMapStr = $this->extConf['routingMap'];
		$routingMapStr = explode(',',$routingMapStr);
		foreach( $routingMapStr as $routeItem ) {
			list($grp,$dcParam) = explode('=', $routeItem);
			if( $dcParam === $dcVal ) {
				return $grp;
			}
		}
		return null;
	}

	private function isValidMd5( $md5 ) {
		return !empty($md5) && preg_match('/^[a-f0-9]{32}$/', $md5);
	}

	/**
	 * Retreive the Dummy User whenever we come from the DocCheck Service.
	 *
	 * @return mixed Array of all users matching current IP
	 */
	function getUser() {
		$dcVal = $_GET['dc'];

		// if no dc param is given - let's not even bother getting the dummy user
		if( !$dcVal || strlen( $dcVal ) === 0 ) {
			return null;
		}

		// if we are not using uniquekey feature, just get the dummy user...
		if( !$this->extConf['uniqueKeyEnable'] ) {
			$user = $this->getDummyUser();
		} else {
			$user = $this->getUniqueUser( $_GET['uniquekey'], $dcVal );
			if( $this->extConf['dcPersonalEnable'] ) {
				$user = $this->augmentDcPersonal($user);
			}
		}

		return $user;
	}

	/**
	 * Authenticate a user
	 * Return 200 if the DocCheck Login is okay. This means that no more checks are needed. Otherwise authentication may fail because we may don't have a password.
	 *
	 * @param	array 	Data of user.
	 * @return	boolean|200|100
	 */
	function authUser($user) {
		// return values:
		// 200 - authenticated and no more checking needed - useful for IP checking without password
		// 100 - Just go on. User is not authenticated but there's still no reason to stop.
		// false - this service was the right one to authenticate the user but it failed
		// true - this service was able to authenticate the user

		$dcVal = $_GET['dc'];

		// check whether there's a chance this user is "ours"
		if( !$this->extConf['uniqueKeyEnable'] ) {
			$ok = $this->authDummyUser( $user, $dcVal );
		} else {
			$ok = $this->authUniqueUser($user, $dcVal);
		}

		// cool, some auth method thought it's fine. Quickly configure the redirect feature.
		if( $ok === 200 ) {
			if( $this->extConf['useFeLoginRedirect'] === '1' ) {
				// TODO: Find a better place to store this bit of information
				$GLOBALS['ap_docchecklogin_do_redirect'] = true;
			}
		}

		return $ok;
	}

	/**
	 * Check whether
	 * ... the given user is the dummy user
	 * ... the dummy may sign in with this dc-param
	 *
	 * @param $user
	 */
	private function authDummyUser($user, $dcVal) {
		if( !$this->isDummyUser( $user )) {
			// oops, not the dummy user. Try other auth methods.
			return 100;
		}

		// now check whether we have the valid dc param

		if( strlen( $dcVal ) > 0 && $dcVal === $this->extConf['dcParam'] ) {
			return 200;
		} else {
			// the dummy user may never sign in without the valid dcParam
			return false;
		}
	}

	private function authUniqueUser($user, $dcVal) {
		if( !$this->isUniqueUser($user)) {
			// not a unique user, try other auth methods.
			return 100;
		}
		// find the correct group
		$expectedGroupId = $this->getUniqueUserGroupId($dcVal);
		$actualGroupId = intval($user[$this->db_user['usergroup_column'] ]);

		// the given dcval does not match any configured group id
		if( !$actualGroupId ) {
			return false;
		}

		// is the unqiueUser in the expected group?
		if( $expectedGroupId !== $actualGroupId) {
			// nope.
			return false;
		}

		return 200;
	}

	/**
	 * Find out whether a given user is the dummy (non-unique)
	 *
	 * @param $user
	 */
	private function isDummyUser($user) {
		// wait, are we supposed to use unique key? then how can this be a dummy user?
		if( $this->extConf['uniqueKeyEnable'] ) {
			return false;
		}

		return ( $user['pid'] === $this->extConf['dummyUserPid']
			&& $user['username'] === $this->extConf['dummyUser']);
	}

	/**
	 * Detect whether a given user has been generated by this extension
	 *
	 * @param $user
	 */
	private function isUniqueUser($user) {
		// if uniquekey is not even enabled, this can't be a unique key user.
		if( !$this->extConf['uniqueKeyEnable'] ) {
			return false;
		}

		// if the pid is incorrect, break
		if ( $user['pid'] !== $this->extConf['dummyUserPid'] ) {
			return false;
		}

		// match the user name pattern
		if( !preg_match('/^dc_[0-9a-f]{32}$/i', $user[ $this->db_user['username_column'] ] ) ) {
			return false;
		}

		return true;
	}
}
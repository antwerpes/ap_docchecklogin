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
	 * Retreive the Dummy User whenever we come from the DocCheck Service.
	 *
	 * @return mixed Array of all users matching current IP
	 */
	function getUser() {
		// get the config

		$dummyUserName = $this->extConf['dummyUser'];
		if(!$dummyUserName) {
			throw new \Exception('DocCheck Authentication: No Dummy User specified in Extension settings');
		}

		$dcVal = $_GET['dc'];

		// if no dc param is given, or it's not the one we expect, let's not even bother getting the dummy user
		if( !$dcVal || strlen( $dcVal ) === 0 || $dcVal !== $this->extConf['dcParam'] ) {
			return null;
		}

		$user = $this->fetchUserRecord( $dummyUserName );
		if ( !$user ) {
			throw new \Exception('DocCheck Authentication: Dummy User ' . $dummyUserName . ' was not found on the Page with the ID ' . $this->extConf['dummyUserPid'] );
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
	function authUser($user)	{
		// return values:
		// 200 - authenticated and no more checking needed - useful for IP checking without password
		// 100 - Just go on. User is not authenticated but there's still no reason to stop.
		// false - this service was the right one to authenticate the user but it failed
		// true - this service was able to authenticate the user

		// check whether the user is the dummy user
		if( $user['pid'] !== $this->extConf['dummyUserPid'] || $user['username'] !== $this->extConf['dummyUser']) {
			// could not authenticate, but maybe it's just not a doccheck dummyuser
			return 100;
		}

		$OK = 100;

		$dcVal = $_GET['dc'];

		// if a dc parameter is given AND equals the one we expect, then the login is deemed successful
		if( strlen( $dcVal ) > 0 && $dcVal === $this->extConf['dcParam'] ) {
			if( $this->extConf['useFeLoginRedirect'] === '1' ) {
				// TODO: Find a better place to store this bit of information
				$GLOBALS['ap_docchecklogin_do_redirect'] = true;
			}
			$OK = 200;
		}

		return $OK;
	}
}
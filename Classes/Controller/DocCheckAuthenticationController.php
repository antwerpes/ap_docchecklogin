<?php

namespace Antwerpes\ApDocCheckLogin\Controller;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DocCheckAuthenticationController
 *
 * @package Antwerpes\ApDocCheckLogin\Controller
 */
class DocCheckAuthenticationController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     *
     */
    const SIGNAL_BEFORE_REDIRECT = 'beforeRedirect';

    /**
     * Frontend User array, old style.
     *
     * @var array
     */
    protected $feUser;

    /**
     *
     */
    public function initializeObject()
    {
        $this->initializeFeUser();
    }

    /**
     *
     */
    protected function initializeFeUser()
    {
        if ($GLOBALS['TSFE']->fe_user->user && $GLOBALS['TSFE']->fe_user->user['uid']) {
            $this->feUser = $GLOBALS['TSFE']->fe_user->user;
        }
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function mainAction()
    {
        // is logged in?
        if ($this->feUser) {
            $this->forward('loggedIn');
            return;
        }
        // not logged in, do redirect the user
        $this->forward('loginForm');
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function loggedInAction()
    {
        // if the settings tell us to redirect on a successful login, do so now.
        if ($GLOBALS['ap_docchecklogin_do_redirect'] === true) {

            // reset the do_redirect flag
            $GLOBALS['ap_docchecklogin_do_redirect'] = false;

            // a ?redirect_url -Parameter takes precedence
            $redirectToUri = $this->getRedirectUriFromCookie();
            // alternatively, get redirect conf from user or user group config
            if (!$redirectToUri) {
                $redirectToUri = $this->getRedirectUriFromFeLogin();
            }
            // aight, so did we find a page id to redirect to?
            if ($redirectToUri) {
                // this way works better than $this->redirect(), which will always add some bullshit params
                if (strpos($redirectToUri, '/') === 0) {
                    $redirectToUri = substr($redirectToUri, 1);
                }

                $hookParams = array('redirectToUri' => &$redirectToUri, 'feUser' => &$this->feUser);
                $this->callHook(self::SIGNAL_BEFORE_REDIRECT, $hookParams);
                $this->redirectToUri($redirectToUri);
            }
            return;
        }
    }

    /**
     * Tries to get a redirect configuration (Page ID) for the current user.
     *
     * @return int|null Page ID
     */
    public function getUserRedirectPid()
    {
        $redirectToPid = $GLOBALS['TSFE']->fe_user->user['felogin_redirectPid'];
        if (!$redirectToPid) {
            return null;
        }
        return $redirectToPid;
    }

    /**
     * Tries to get a redirect configuration (Page ID) for the current user's primary group.
     *
     * @return int|null Page ID
     */
    public function getGroupRedirectPid()
    {
        $groupData = $GLOBALS['TSFE']->fe_user->groupData;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_groups');
        $statement = $queryBuilder->select('felogin_redirectPid')
            ->from('fe_groups')
            ->where('felogin_redirectPid<>\'\' AND uid IN (' . implode(',', $groupData['uid']) . ')')
            ->execute();

        while ($row = $statement->fetch()) {
            // take the first group with a redirect page
            return $row[0];
        }

        return null;
    }

    /**
     *
     */
    public function loginFormAction()
    {
        // set a redirect cookie, if a redirect_url GET Param is set
        $redirectUrl = $_GET['redirect_url'];
        // ... or if the redirect-option is chosen in the plugin
        if (!$redirectUrl && $this->settings['redirect']) {
            $redirectUrl = $this->uriBuilder->reset()->setTargetPageUid($this->settings['redirect'])
                ->setLinkAccessRestrictedPages(true)
                ->setCreateAbsoluteUri(true)
                ->build();
        }
        if ($redirectUrl) {
            // store as cookie and expire in 10 minutes
            setcookie('ap_docchecklogin_redirect', $redirectUrl, intval(gmdate('U')) + 600, '/');
        } else {
            // delete an older cookie if no longer needed
            setcookie('ap_docchecklogin_redirect', '', intval(gmdate('U')) - 3600, '/');
        }

        $loginId = $this->settings['loginId'];
        // override given loginId if loginOverrideId is set
        if (is_numeric($this->settings['loginOverrideId'])) {
            $loginId = $this->settings['loginOverrideId'];
        }

        // most settings are injected implicitly, but a custom login template must be checked briefly
        if ($this->settings['loginLayout'] === 'custom') {
            $templateKey = $this->settings['customLayout'];
        } else {
            $templateKey = $this->settings['loginLayout'] . '_red';
        }

        $this->view->assign('loginId', $loginId);
        $this->view->assign('templateKey', $templateKey);
    }

    /**
     * @return null
     */
    public function getRedirectUriFromCookie()
    {
        if (array_key_exists('ap_docchecklogin_redirect', $_COOKIE)) {
            // clear the cookie
            $redirectUri = $_COOKIE['ap_docchecklogin_redirect'];
            setcookie('ap_docchecklogin_redirect', "", intval(gmdate('U')) - 3600, '/');

            return $redirectUri;
        }

        return null;
    }

    /**
     * @return null
     */
    public function getRedirectUriFromFeLogin()
    {
        // user configuration takes precedence
        $redirectToPid = $this->getUserRedirectPid();
        $redirectUri = null;
        // only bother fetching the group redirect config if no user user-level config was found
        if (!$redirectToPid) {
            $redirectToPid = $this->getGroupRedirectPid();
        }

        if ($redirectToPid) {
            $redirectUri = $this->uriBuilder->reset()->setTargetPageUid($redirectToPid)->setCreateAbsoluteUri(true)->build();
        }

        return $redirectUri;
    }

    /**
     * @param string $hookName
     * @param array $params
     *
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    protected function callHook($hookName, &$params)
    {
        // call hook to post-process the fetched user record
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ap_docchecklogin'][$hookName])) {
            $params['pObj'] = $this;

            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ap_docchecklogin'][$hookName] as $funcRef) {
                GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }


        if ($this->signalSlotDispatcher) {
            $this->signalSlotDispatcher->dispatch(__CLASS__, $hookName, $params);
        }
    }
}

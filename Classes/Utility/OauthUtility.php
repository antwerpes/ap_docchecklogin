<?php
namespace Antwerpes\ApDocchecklogin\Utility;

use TYPO3\CMS\Backend\Routing\Exception\InvalidRequestTokenException;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class OauthUtility
{
    private $generateTokenUrl = 'https://login.doccheck.com/service/oauth/access_token/';
    private $validateTokenUrl = 'https://login.doccheck.com/service/oauth/access_token/checkToken.php';

    public function generateToken($clientId,$clientSecret,$code){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->generateTokenUrl.'?client_id='.$clientId.'&client_secret='.$clientSecret.'&code='.$code.'&grant_type=authorization_code',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = json_decode(curl_exec($curl));
        curl_close($curl);
        if ($response->access_token) {
            $DC_ACCESS_TOKEN = $response->access_token;
            global $DC_ACCESS_TOKEN;
            $GLOBALS['DC_REFRESH_TOKEN'] = $response->refresh_token;
            mail('sabrina.zwirner@antwerpes.de', 'Global First', print_r( time(), 1));
            return true;
        } else {
            throw new InvalidRequestTokenException(
                'There was a Problem in receiving the access token'
            );
        }
    }
}
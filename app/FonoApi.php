<?php
/**
 * Created by PhpStorm.
 * User: agavrila
 * Date: 2018-11-11
 * Time: 12:06 AM
 */

namespace App;


use Exception;

class FonoApi
{
    private $_ApiKey = null;
    private $_ApiUrl = "https://fonoapi.freshpixl.com/v1/";

    function __construct()
    {
        $this->_ApiKey = "cc004a2fdb5f30dad1a2f65d80ec77980f4f40fb46ce4fe8";
    }


    /**
     *
     * Gets Device Data Object from fonoapi.freshpixl.com
     *
     * @param      $device
     * @param null $position
     * @param null $brand
     *
     * @return mixed
     * @throws \Exception
     */
    public function getDevice($device)
    {
        $url = $this->_ApiUrl . "getdevice";
        $postData = array(
            'device' => trim($device),
            'token' => $this->_ApiKey
        );
        $result = json_decode($this->sendPostData($url, $postData));
        if (isset($result->status)) {
            throw new Exception($result->message . " | <strong>innerException</strong> : " . $result->innerException);
        } else {
            return $result;
        }
    }

    /**
     * Sends Post Data to the Server
     *
     * @param $url
     * @param $post
     *
     * @return mixed
     */
    private function sendPostData($url, $post)
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec($ch);
            if (FALSE === $result)
                throw new Exception(curl_error($ch), curl_errno($ch));
            curl_close($ch);
            return $result;
        } catch (Exception $e) {
            $result["status"] = $e->getCode();
            $result["message"] = "Curl Failed";
            $result["innerException"] = $e->getMessage();
            return json_encode($result);
        }
    }
}
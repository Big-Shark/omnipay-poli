<?php

namespace Omnipay\Poli\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use SimpleXMLElement;
use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Poli Complete Purchase Request
 * 
 * @link http://www.polipaymentdeveloper.com/doku.php?id=gettransaction
 */
class CompletePurchaseRequest extends PurchaseRequest
{
    //protected $endpoint = "https://publicapi.apac.paywithpoli.com/api/Transaction/GetTransaction";
    protected $endpoint = 'https://poliapi.apac.paywithpoli.com/api/v2/Transaction/GetTransaction';

    public function getData()
    {
        $this->validate(
            'merchantCode',
            'authenticationCode'
        );

        $token = $this->getToken();

        if (!$token) {
            $token = $this->httpRequest->query->get('token');
        }

        if (!$token) {
            //this may be a POST nudge request, so look for the token there
            $token = $this->httpRequest->request->get('Token');
        }

        if (!$token) {
            throw new InvalidRequestException('Transaction token is missing');
        }
        $data = array();
        $data['token'] = $token;
        
        return $data;
    }

    public function getToken()
    {
        return $this->getParameter('token');
    }

    public function send()
    {
        return $this->sendData($this->getData());
    }

    public function sendData($data)
    {
        $auth = base64_encode($this->getMerchantCode().":".$this->getAuthenticationCode());
        $httpResponse = $this->httpClient->request(
            'GET',
            $this->endpoint.'?'.http_build_query($data),
            [
                'Authorization' => 'Basic '.$auth,
                'Content-Type' => 'application/json',
            ],
            json_encode($data)
        );

        return $this->response = new CompletePurchaseResponse($this, $httpResponse->getBody()->getContents());
    }
}

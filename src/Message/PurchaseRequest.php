<?php

namespace Omnipay\Poli\Message;

use Omnipay\Common\Message\AbstractRequest;

/**
 * Poli Purchase Request
 *
 * @link http://www.polipaymentdeveloper.com/doku.php?id=initiate
 */
class PurchaseRequest extends AbstractRequest
{
    protected $endpoint = 'https://poliapi.apac.paywithpoli.com/api/v2/Transaction/Initiate';

    public function getMerchantCode()
    {
        return $this->getParameter('merchantCode');
    }

    public function setMerchantCode($value)
    {
        return $this->setParameter('merchantCode', $value);
    }

    public function getAuthenticationCode()
    {
        return $this->getParameter('authenticationCode');
    }

    public function setAuthenticationCode($value)
    {
        return $this->setParameter('authenticationCode', $value);
    }

    public function setMerchantReference($value)
    {
        return $this->setParameter('merchantReference', $value);
    }

    public function setMerchantReferenceFormat($value)
    {
        return $this->setParameter('merchantReferenceFormat', $value);
    }

    public function getData()
    {
        $this->validate(
            'merchantCode',
            'authenticationCode',
            'transactionId',
            'currency',
            'amount',
            'returnUrl',
            'cancelUrl'
        );

        $data = array();
        $data['Amount'] = $this->getAmount();
        $data['CurrencyCode'] = $this->getCurrency();
        $data['CancellationURL'] = $this->getCancelUrl();
        $data['MerchantData'] = $this->getTransactionId();
        $data['MerchantDateTime'] = date('Y-m-d\TH:i:s');
        $data['MerchantHomePageURL'] = $this->getCancelUrl();
        $data['MerchantReference'] = $this->parameters->get('merchantReference', $this->getCombinedMerchantRef());
        if($data['CurrencyCode'] === 'NZD') {
            $data['MerchantReferenceFormat'] = $this->parameters->get('merchantReferenceFormat', 1);
        }
        $data['NotificationURL'] = $this->getNotifyUrl();
        $data['SuccessURL'] = $this->getReturnUrl();
        $data['Timeout'] = 0;
        $data['FailureURL'] = $this->getReturnUrl();
        $data['UserIPAddress'] = $this->getClientIp();

        return $data;
    }

    /**
     * Generate reference data
     * @link http://www.polipaymentdeveloper.com/doku.php?id=nzreconciliation
     */
    public function getCombinedMerchantRef()
    {
        $card = $this->getCard();
        $id = $this->cleanField($this->getTransactionId());
        if ($card && $card->getName()) {
            $data = array($this->cleanField($card->getName()), "", $id);
            return implode("|", $data);
        }

        return $id;
    }

    /**
     * Data in reference field must not contain illegal characters
     */
    protected function cleanField($field)
    {
        return substr($field, 0, 12);
    }

    public function send()
    {
        return $this->sendData($this->getData());
    }

    public function sendData($data)
    {
        $auth = base64_encode($this->getMerchantCode().":".$this->getAuthenticationCode());
        $httpResponse = $this->httpClient->request(
            'POST',
            $this->endpoint,
            [
                'Authorization' => 'Basic '.$auth,
                'Content-Type' => 'application/json',
            ],
            json_encode($data)
        );

        return $this->response = new PurchaseResponse($this, $httpResponse->getBody()->getContents());
    }
}

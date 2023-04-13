<?php

namespace App\Services;

use Omnipay\Omnipay;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PayPalApiService
{
    private $apiContext;
    private $omnipayGateway;

    public function __construct()
    {
        $this->apiContext = new ApiContext(new OAuthTokenCredential(env('PAYPAL_APP_CLIENT_ID'), env('PAYPAL_APP_SECRET')));
        $this->omnipayGateway = Omnipay::create('PayPal_Rest');
        $this->omnipayGateway->setClientId(env('PAYPAL_APP_CLIENT_ID'));
        $this->omnipayGateway->setSecret(env('PAYPAL_APP_SECRET'));
        if (env('PAYPAL_APP_SANDBOX_TESTING')) {
            $this->omnipayGateway->setTestMode(env('PAYPAL_APP_SANDBOX_TESTING'));
        }
    }

    public function listPlans()
    {
        try {
            $data = $this->omnipayGateway->listPlan(['status' => $this->omnipayGateway::BILLING_PLAN_STATE_ACTIVE])->send()->getData();
            return $data['plans'] ?? [];
        } catch (\Throwable $exception) {
            info($exception->getTraceAsString());
        }
        return [];
    }
}
<?php
/**
 * Created by NiNaCoder.
 * Date: 2019-08-06
 * Time: 17:06
 */

namespace App\Http\Controllers\Frontend;

use App\Models\Email;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Service;
use Cart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Omnipay\Omnipay;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Agreement;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\PaymentExecution;
use PayPal\Rest\ApiContext;
use AshAllenDesign\LaravelExchangeRates\Classes\ExchangeRate;
use App\Modules\MobileHelper\APISession;

class PaypalController extends APISession
{
    private $omniPayPalGateway;
    private $paypalSDKApiContext;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $paypalMode = boolval(env('PAYPAL_APP_SANDBOX_TESTING')) === true ? 'sandbox' : 'live';

        // Omnipay Paypal Gateway
        $this->omniPayPalGateway = Omnipay::create('PayPal_Rest');
        $this->omniPayPalGateway->setClientId(env('PAYPAL_APP_CLIENT_ID'));
        $this->omniPayPalGateway->setSecret(env('PAYPAL_APP_SECRET'));
        if ( boolval(env('PAYPAL_APP_SANDBOX_TESTING'))) {
            $this->omniPayPalGateway->setTestMode(env('PAYPAL_APP_SANDBOX_TESTING'));
        }

        // PayPalSDK ApiContext
        $this->paypalSDKApiContext = new ApiContext(new OAuthTokenCredential(env('PAYPAL_APP_CLIENT_ID'), env('PAYPAL_APP_SECRET')));
        $this->paypalSDKApiContext->setConfig([
            'mode' => $paypalMode,
            'log.LogLevel' => 'INFO', // PLEASE USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
            'cache.enabled' => true,
        ]);

        $this->apiSession();
    }

    protected function getPayPalFrequency($planPeriodFormat)
    {
        switch ($planPeriodFormat) {
            case 'D':
                return $this->omniPayPalGateway::BILLING_PLAN_FREQUENCY_DAY;
            case 'W':
                return $this->omniPayPalGateway::BILLING_PLAN_FREQUENCY_WEEK;
            case 'Y':
                return $this->omniPayPalGateway::BILLING_PLAN_FREQUENCY_YEAR;
            case 'M':
            default:
                return $this->omniPayPalGateway::BILLING_PLAN_FREQUENCY_MONTH;
        }
    }

    protected function getPayPalAmount($inputAmount)
    {
        if(in_array(config('settings.currency', 'USD'), config('payment.paypal_currency_subscription'))) {
            return in_array(config('settings.currency', 'USD'), config('payment.currency_decimals')) ? $inputAmount : intval($inputAmount);
        } else {
            $exchangeRates = new ExchangeRate();
            return number_format($exchangeRates->convert($inputAmount, config('settings.currency'), 'USD', now()), 2);
        }
        return intval($inputAmount);
    }

    protected function getPayPalSubscriptionStartDate($isTrial = false, $trialPeriodFormat, $trialPeriod)
    {
        if($isTrial) {
            switch ($trialPeriodFormat) {
                case 'D':
                    $trialEnd = now()->addDays($trialPeriod)->format('Y-m-d\TH:i:s\Z');
                    break;
                case 'W':
                    $trialEnd = now()->addWeeks($trialPeriod)->format('Y-m-d\TH:i:s\Z');
                    break;
                case 'M':
                    $trialEnd = now()->addMonths($trialPeriod)->format('Y-m-d\TH:i:s\Z');
                    break;
                case 'Y':
                    $trialEnd = now()->addYears($trialPeriod)->format('Y-m-d\TH:i:s\Z');
                    break;
                default:
                    $trialEnd = date("Y-m-d\TH:i:s\Z", strtotime('+2 minute'));
            }
        } else {
            $trialEnd = date("Y-m-d\TH:i:s\Z", strtotime('+2 minute'));;
        }
        return $trialEnd;
    }

    protected function redirectJS()
    {
        return <<<EOF
<script type="text/javascript">
        try {
            var opener = window.opener;
            if(opener) {
                opener.Payment.subscriptionSuccess();
                window.close();
            }
            
        } catch(e) {
            
        }
        setTimeout(function () {
            window.location.href = "' . config('settings.deeplink_scheme', 'musicengine') . '://engine/payment/success";
        }, 500);
</script>
EOF;
    }

    public function subscription()
    {
        try {
            if(auth()->user()->subscription) {
                abort(403, 'You are already have a subscription.');
            }

            // Get Service data from DB
            $service = Service::findOrFail($this->request->route('id'));

            // Variables for PayPal plan & subscription creation
            $subscriptionName = $service->title;
            $amount = $this->getPayPalAmount($service->price);
            $frequency = $this->getPayPalFrequency($service->plan_period_format);
            $susbcriptionDescription = $service->price . ' USD @ ' . $service->plan_period . ' ' . $frequency;
            $trialEnd = $this->getPayPalSubscriptionStartDate($service->trial, $service->trial_period_format, $service->trial_period);

            // Create PayPal Plan Request
            $createPlanRequest = $this->omniPayPalGateway->createPlan([
                'name' => $subscriptionName,
                'description' => $susbcriptionDescription,
                'type' => $this->omniPayPalGateway::BILLING_PLAN_TYPE_FIXED,
                'paymentDefinitions' => [
                    [
                        'name' => 'Regular Payments',
                        'type' => $this->omniPayPalGateway::PAYMENT_REGULAR,
                        'frequency' => $frequency,
                        'frequency_interval' => 1,
                        'cycles' => intval($service->plan_period),
                        'amount' => [
                            'value' => $amount,
                            'currency' => 'USD'
                        ],
                    ],
                ],
                'merchant_preferences' => [
                    'setup_fee' => [
                        'value' => 1,
                        'currency' => 'USD'
                    ],
                    'return_url' => route('frontend.paypal.subscription.success', ['id' => $service->id]),
                    'cancel_url' => route('frontend.paypal.subscription.cancel', ['id' => $service->id]),
                    'auto_bill_amount' => 'YES',
                    'initial_fail_amount_action' => "CONTINUE",
                    'max_fail_attempts' => 0
                ]
            ]);

            $createPlanResponse = $createPlanRequest->send();

            if ($createPlanResponse->isSuccessful()) { // PayPal Plan create request is successful
                $payPalPlanId = $createPlanResponse->getTransactionReference(); // PayPal planId

                // Activate PayPal Plan Request
                $activatePlanRequest = $this->omniPayPalGateway->updatePlan([
                    'transactionReference' => $payPalPlanId,
                    'state' => $this->omniPayPalGateway::BILLING_PLAN_STATE_ACTIVE
                ]);

                $activatePlanResponse = $activatePlanRequest->send();

                if ($activatePlanResponse->isSuccessful()) { // Activate PayPlan Plan is successful
                    // Create PayPal Subscription from activated PayPal Plan
                    $createSubscriptionRequest = $this->omniPayPalGateway->createSubscription([
                        'name' => $subscriptionName,
                        'description' => $susbcriptionDescription,
                        'startDate' => now()->addDay(1)->startOfDay()->toDateTime(),
                        'planId' => $payPalPlanId,
                        'payerDetails' => [
                            'payment_method' => 'paypal'
                        ]
                    ]);

                    $createSubscriptionResponse = $createSubscriptionRequest->send();

                    #dd(compact('createSubscriptionResponse'));;

                    if ($createSubscriptionResponse->isSuccessful() && $createSubscriptionResponse->isRedirect()) {
                        $approvalUrl = $createSubscriptionResponse->getRedirectUrl();
                        $subscriptionId = $createSubscriptionResponse->getTransactionReference();
                        header("Location:" . $approvalUrl);
                        exit();

                    } else {
                        // TODO: Error handling - subscription NOT created
                    }

                } else { //TODO: Error handling - newly created plan request NOT activated

                }

            } else { // TODO: Error handling - plan request NOT created

            }
        } catch (\Throwable $exception) {
            info($exception->getTraceAsString());
        }
    }

    /**
     * Callback URL after successful purchase
     *
     * @return void
     */
    public function success() {
        try {
            if(auth()->user()->subscription) {
                abort(403, 'You are already have a subscription.');
            }

            $this->request->validate([
                'token' => 'required|string',
            ]);

            $service = Service::findOrFail($this->request->route('id'));

            $payPalSubscriptionId = $this->request->input('token');
            $payPalBillingAgreementId = $this->request->input('ba_token');

            // Complete PayPal subscription
            $completeSubscriptionResponse = $this->omniPayPalGateway->completeSubscription([
                'transactionReference'  => $payPalSubscriptionId,
            ])->send();
            // Complete PayPal Subscription is successful
            if ($completeSubscriptionResponse->isSuccessful()) {
                $payPalCompletedSubscriptionId = $completeSubscriptionResponse->getTransactionReference();

                sleep(3);

                // Get Billing Agreement data
                $billingAgreement = new Agreement();
                $billingAgreement->execute($payPalSubscriptionId, $this->paypalSDKApiContext);

                #dd(compact('billingAgreement'));

                // Get Billing Agreement | Plan data
                $billingAgreementData = Agreement::get($billingAgreement->getId(), $this->paypalSDKApiContext);
                $plan = $billingAgreementData->getPlan();

                // Save new Subscription Data
                DB::table('subscriptions')
                    ->where('user_id', auth()->user()->id)
                    ->where('service_id', $service->id)
                    ->where('gate', 'paypal')
                    ->delete();

                $subscription = new Subscription();
                $subscription->gate = 'paypal';
                $subscription->user_id = auth()->user()->id;
                $subscription->service_id = $service->id;
                $subscription->payment_status = 1;
                $subscription->transaction_id = $this->request->input('token');
                $subscription->token = $payPalCompletedSubscriptionId;
                $subscription->trial_end = null; //TODO: Save correct [trial_end] value
                $subscription->next_billing_date = Carbon::make($billingAgreementData->getAgreementDetails()->next_billing_date)->addDay(1);
                $subscription->cycles = $billingAgreementData->getAgreementDetails()->cycles_completed;
                $subscription->amount = $plan->getPaymentDefinitions()[0]->amount->value ?? 0;
                $subscription->currency = $plan->getPaymentDefinitions()[0]->amount->currency ?? 0;
                if(!$service->trial) {
                    $subscription->last_payment_date = now();
                }
                $subscription->save();

                // Send subscription receipt email
                (new Email)->subscriptionReceipt(auth()->user(), $subscription);

                // Redirect JS code
                echo $this->redirectJS();
            } else { // TODO: Error handling - subscription IS NOT completed

            }
        } catch (Exception $ex) {
            echo "Failed to get activate";
            var_dump($ex);
            exit();
        }
    }

    public function purchase()
    {
        Cart::session(auth()->user()->id);

        if(Cart::isEmpty()) {
            abort(500,'Cart is empty');
        }

        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        $items = array();

        foreach (Cart::getContent() as $product) {
            $item = new Item();
            $item->setName($product->associatedModel->title)
                ->setCurrency(config('settings.currency', 'USD'))
                ->setQuantity(1)
                ->setPrice($product->price);
            $items[] = $item;
        }

        $itemList = new ItemList();
        $itemList->setItems($items);

        $details = new Details();
        /*$details->setShipping(1.2)
            ->setTax(1.3)
            ->setSubtotal(17.50);*/
        $amount = new Amount();
        $amount->setCurrency(config('settings.currency', 'USD'))
            ->setTotal(Cart::getTotal())
            ->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("Ordering media")
            ->setInvoiceNumber(uniqid());

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($this->request->is('api*') ? route('api.paypal.purchase.authorization.success', ['api-token' => $this->request->input('api-token')]) : route('frontend.paypal.purchase.authorization.success'))
            ->setCancelUrl($this->request->is('api*') ? route('api.paypal.purchase.authorization.cancel', ['api-token' => $this->request->input('api-token')]) : route('frontend.paypal.purchase.authorization.cancel'));

        $payment = new Payment();
        $payment->setIntent("authorize")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));
        $request = clone $payment;
        try {
            $payment->create($this->paypalSDKApiContext);
        } catch (Exception $ex) {
            abort(403, "Problem with creating Payment Authorization Using PayPal");
        }

        $approvalUrl = $payment->getApprovalLink();

        header('Location: ' . $approvalUrl);
        exit;
    }

    public function successAuthorization() {
        Cart::session(auth()->user()->id);

        $this->paypalSDKApiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                env('PAYPAL_APP_CLIENT_ID'),
                env('PAYPAL_APP_SECRET')
            )
        );

        $paymentId = $this->request->input('paymentId');
        $payment = Payment::get($paymentId, $this->paypalSDKApiContext);
        $execution = new PaymentExecution();
        $execution->setPayerId($this->request->input('PayerID'));
        $transaction = new Transaction();
        $amount = new Amount();
        $details = new Details();
        $amount->setCurrency(config('settings.currency', 'USD'));
        $amount->setTotal(Cart::getTotal());
        $amount->setDetails($details);
        $transaction->setAmount($amount);
        $execution->addTransaction($transaction);
        try {
            $result = $payment->execute($execution, $this->paypalSDKApiContext);

            try {
                $authorizationId = $result->transactions[0]->related_resources[0]->authorization->id;
                $authorization = Authorization::get($authorizationId, $this->paypalSDKApiContext);
                $amt = new Amount();
                $amt->setCurrency(config('settings.currency', 'USD'))
                    ->setTotal(Cart::getTotal());
                $capture = new Capture();
                $capture->setAmount($amt);
                $getCapture = $authorization->capture($capture, $this->paypalSDKApiContext);

                foreach (Cart::getContent() as $item) {
                    $order = new Order();
                    $order->user_id = auth()->user()->id;
                    $order->orderable_id = $item->attributes->orderable_id;
                    $order->orderable_type = $item->attributes->orderable_type;
                    $order->payment = 'paypal';
                    $order->amount = $item->price;
                    $order->currency = config('settings.currency', 'USD');
                    $order->payment_status = 1;
                    $order->transaction_id = $paymentId;
                    $order->save();
                }

                Cart::clear();

                echo '<script type="text/javascript">
                        try {
                            var opener = window.opener;
                            if(opener) {
                                opener.Payment.purchaseSuccess();
                                window.close();
                            }
                        } catch(e) {
                            
                        }
                        
                        setTimeout(function () {
                            window.location.href = "' . config('settings.deeplink_scheme', 'musicengine') . '://engine/payment/success";
                        }, 1000);
            
                      </script>';
                exit;
            } catch (Exception $ex) {
                abort(403, "Can't capture the authorization payment with paypal");
            }
        } catch (Exception $ex) {
            abort(403, "Can't execute payment with paypal");
        }
    }

    public function cancel() {
        $view = view()->make('commons.abort-with-message')->with('code', 'Cancel')->with('message', 'Payment canceled by customer.');
        die($view);
    }
}
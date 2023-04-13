<?php

/**
 * Created by NiNaCoder.
 * Date: 2019-08-06
 * Time: 17:06
 */

namespace App\Http\Controllers\Frontend;

use App\Models\Email;
use App\Models\Order;
use App\Models\Service;
use App\Models\Subscription;
use App\Modules\MobileHelper\APISession;
use Carbon\Carbon;
use Cart;
use Illuminate\Http\Request;
use Omnipay\Omnipay;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;
use View;

class StripeController extends APISession
{
    protected $stripeOmnipay;
    protected $stripeClient;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $stripeApiKey = config('settings.payment_stripe_test_mode') ? config('settings.payment_stripe_test_key') : env('STRIPE_SECRET_API');
        $this->stripeClient = new StripeClient($stripeApiKey);
        $this->stripeOmnipay = Omnipay::create('Stripe');
        $this->stripeOmnipay->setApiKey($stripeApiKey);
    }

    public function subscriptionAuthorization()
    {
        $this->apiSession();

        $service = Service::findOrFail($this->request->route('id'));

        $amount = in_array(config('settings.currency', 'USD'), config('payment.currency_decimals')) ? $service->price : intval($service->price);

        View::getFinder()->setPaths([resource_path('views/api')]);

        return View::make('stripe.index')
            ->with('user', auth()->user())
            ->with('total', $amount)
            ->with('token', $this->request->input('api-token'))
            ->with('callback', route('api.stripe.subscription.callback'));
    }

    public function subscriptionAuthorizationV2() // Omnipay Stripe implementation
    {
        $this->apiSession();

        $service = Service::findOrFail($this->request->route('id'));

        $amount = in_array(config('settings.currency', 'USD'), config('payment.currency_decimals')) ? $service->price : intval($service->price);

        View::getFinder()->setPaths([resource_path('views/api')]);

        return View::make('stripe.index')
            ->with('user', auth()->user())
            ->with('total', $amount)
            ->with('token', $this->request->input('api-token'))
            ->with('callback', route('api.stripe.subscription.callback.v2'));
    }

    public function subscriptionCallback()
    {
        if (auth()->user()->subscription) {
            abort(Response::HTTP_FORBIDDEN, 'You are already have a subscription.');
        }

        $this->request->validate([
            'planId' => 'required|integer',
            'stripeToken' => 'required|string',
        ]);

        $service = Service::findOrFail($this->request->input('planId'));

        $product = $this->stripeClient->products->create([
            'name' => $service->title,
        ]);

        $plan = $this->stripeClient->plans->create([
            "amount" => in_array(config('settings.currency', 'USD'), config('payment.currency_decimals')) ? ($service->price  * 100) : (intval($service->price) * 100),
            "interval" => "month",
            'product' => $product->id,
            "currency" => config('settings.currency', 'USD')
        ]);

        $customer = $this->stripeClient->customers->create([
            "email" => auth()->user()->email,
            "source" => config('settings.payment_stripe_test_mode') ? 'tok_visa' : $this->request->input('stripeToken')
        ]);

        if ($service->trial) {
            switch ($service->trial_period_format) {
                case 'D':
                    $trial_end = now()->addDays($service->trial_period);
                    break;
                case 'W':
                    $trial_end = now()->addWeeks($service->trial_period);
                    break;
                case 'M':
                    $trial_end = now()->addMonths($service->trial_period);
                    break;
                case 'Y':
                    $trial_end = now()->addYears($service->trial_period);
                    break;
                default:
                    $trial_end = 'now';
            }
        } else {
            $trial_end = 'now';
        }

        $stripe_subscription = $this->stripeClient->subscriptions->create([
            'customer' => $customer->id,
            'items' => [
                [
                    'plan' => $plan->id
                ],
            ],
            'trial_end' => ($trial_end == 'now' ? $trial_end : $trial_end->timestamp),
        ]);

        if ($stripe_subscription->id) {
            $subscription = new Subscription();
            $subscription->gate = 'stripe';
            $subscription->user_id = auth()->user()->id;
            $subscription->service_id = $service->id;
            $subscription->payment_status = 1;
            $subscription->transaction_id = $stripe_subscription->id;
            $subscription->token = $stripe_subscription->id;
            $subscription->next_billing_date = Carbon::make($stripe_subscription->current_period_end);
            $subscription->trial_end = ($trial_end == 'now' ? now() : $trial_end);
            $subscription->amount = $service->price;
            $subscription->currency = config('settings.currency', 'USD');
            if ($stripe_subscription->status == 'active') {
                $subscription->cycles = $stripe_subscription->plan->interval_count;
                $subscription->last_payment_date = now();
            }

            $subscription->save();

            (new Email())->subscriptionReceipt(auth()->user(), $subscription);

            return response()->json($subscription);
        } else {
            return response()->json([
                'message' => 'Payment failed',
                'errors' => array('message' => array(__('web.PAYMENT_FAILED_DESCRIPTION')))
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function subscriptionCallbackV2()
    {
        if (auth()->user()->subscription) {
            abort(Response::HTTP_FORBIDDEN, 'You are already have a subscription.');
        }

        $this->request->validate([
            'planId' => 'required|integer',
            'stripeToken' => 'required|string',
        ]);

        $service = Service::findOrFail($this->request->input('planId'));

        // Create Stripe Product
        $product = $this->stripeClient->products->create([
            'name' => $service->title,
        ]);

        // info(json_encode(['#product' => $product]));

        $amount = in_array(config('settings.currency', 'USD'), config('payment.currency_decimals')) ? ($service->price  * 100) : (intval($service->price) * 100);

        // Create Stripe Plan
        $createPlanRequest = $this->stripeOmnipay->createPlan([
            "id" => "basic",
            "amount" => $amount,
            "interval" => "month",
            'product' => $product->id,
            "currency" => config('settings.currency', 'USD')
        ]);
        $createPlanResponse = $createPlanRequest->send();

        if ($createPlanResponse->isSuccessful()) {
            $createdStripePlanId = $createPlanResponse->getTransactionReference();

            // info('#planId=' . $createdStripePlanId);

            // Create Stripe Customer
            $customer = $this->stripeClient->customers->create([
                "email" => auth()->user()->email,
                "source" => config('settings.payment_stripe_test_mode') ? 'tok_visa' : $this->request->input('stripeToken')
            ]);

            // info(json_encode(compact('customer')));

            if ($service->trial) {
                switch ($service->trial_period_format) {
                    case 'D':
                        $trialEnd = now()->addDays($service->trial_period);
                        break;
                    case 'W':
                        $trialEnd = now()->addWeeks($service->trial_period);
                        break;
                    case 'M':
                        $trialEnd = now()->addMonths($service->trial_period);
                        break;
                    case 'Y':
                        $trialEnd = now()->addYears($service->trial_period);
                        break;
                    default:
                        $trialEnd = 'now';
                }
            } else {
                $trialEnd = 'now';
            }

            // Create Stripe Subscription
            $createSubscriptionRequest = $this->stripeOmnipay->createSubscription([
                'customerReference' => $customer->id,
                'plan' => $createdStripePlanId,
                'trialEnd' => ($trialEnd == 'now' ? $trialEnd : $trialEnd->timestamp)
            ]);
            $createSubscriptionResponse = $createSubscriptionRequest->send();

            if ($createSubscriptionResponse->isSuccessful()) {
                $createdStripeSubscriptionId = $createSubscriptionResponse->getTransactionReference();
                // info('#subscrption=' . $createdStripeSubscriptionId);

                // Save Subscription data in DB
                $subscription = new Subscription();
                $subscription->gate = 'stripe';
                $subscription->user_id = auth()->user()->id;
                $subscription->service_id = $service->id;
                $subscription->payment_status = 1;
                $subscription->transaction_id = $createdStripeSubscriptionId;
                $subscription->token = $createdStripeSubscriptionId;
                $subscription->next_billing_date = Carbon::make(($trialEnd == 'now' ? $trialEnd : $trialEnd->timestamp));
                $subscription->trial_end = ($trialEnd == 'now' ? now() : $trialEnd);
                $subscription->amount = $service->price;
                $subscription->currency = config('settings.currency', 'USD');


                // TODO: Check created Stripe subscription status if already active, if active - set the [cycles & last_payment_date]
                /*if ($stripeSubscription->status == 'active') {
                    $subscription->cycles = $stripeSubscription->plan->interval_count;
                    $subscription->last_payment_date = now();
                }*/

                $subscription->save();

                (new Email())->subscriptionReceipt(auth()->user(), $subscription);

                return response()->json($subscription);
            } else {
                return response()->json([
                    'message' => 'Payment failed',
                    'errors' => array('message' => array(__('web.PAYMENT_FAILED_DESCRIPTION')))
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            return response()->json([
                'message' => 'Payment failed',
                'errors' => array('message' => array(__('web.PAYMENT_FAILED_DESCRIPTION')))
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }  // Omnipay Stripe implementation

    public function purchaseAuthorization()
    {
        $this->apiSession();

        Cart::session(auth()->user()->id);

        View::getFinder()->setPaths([resource_path('views/api')]);

        return View::make('stripe.index')
            ->with('user', auth()->user())
            ->with('total', Cart::getTotal())
            ->with('token', $this->request->input('api-token'))
            ->with('callback', route('api.stripe.purchase.callback'));
    }

    public function purchaseAuthorizationV2()
    {
        $this->apiSession();

        Cart::session(auth()->user()->id);

        View::getFinder()->setPaths([resource_path('views/api')]);

        return View::make('stripe.index')
            ->with('user', auth()->user())
            ->with('total', Cart::getTotal())
            ->with('token', $this->request->input('api-token'))
            ->with('callback', route('api.stripe.purchase.callback.v2'));
    }

    public function purchaseCallback()
    {
        $this->apiSession();

        Cart::session(auth()->user()->id);

        if (Cart::isEmpty()) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Cart is empty');
        }

        $this->request->validate([
            'stripeToken' => 'required|string',
        ]);

        $itemIds = [];
        foreach (Cart::getContent() as $item) {
            $itemIds[] = $item->id;
        }

        $charge = $this->stripeClient->charges->create([
            'amount' => (Cart::getTotal() * 100),
            "currency" => config('settings.currency', 'USD'),
            "source" => config('settings.payment_stripe_test_mode') ? 'tok_visa' : $this->request->input('stripeToken'),
            'description' => implode('|', $itemIds),
        ]);

        if ($charge->id) {
            foreach (Cart::getContent() as $item) {
                $order = new Order();
                $order->user_id = auth()->user()->id;
                $order->orderable_id = $item->attributes->orderable_id;
                $order->orderable_type = $item->attributes->orderable_type;
                $order->payment = 'stripe';
                $order->amount = $item->price;
                $order->currency = config('settings.currency', 'USD');
                $order->payment_status = $charge->captured ? 1 : 0;
                $order->transaction_id = $charge->id;
                $order->save();
            }

            Cart::clear();
            if ($this->request->is('api*')) {
                echo '<script type="text/javascript">                        
                        setTimeout(function () {
                            window.location.href = "' . config('settings.deeplink_scheme', 'musicengine') . '://engine/payment/success";
                        }, 1000);
                      </script>';
                exit;
            }
            return response()->json($charge);
        } else {
            return response()->json([
                'message' => 'Payment failed',
                'errors' => array('message' => array(__('web.PAYMENT_FAILED_DESCRIPTION')))
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function purchaseCallbackV2() // Omnipay Stripe implementation
    {
        $this->apiSession();

        Cart::session(auth()->user()->id);

        if (Cart::isEmpty()) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Cart is empty');
        }

        $this->request->validate([
            'stripeToken' => 'required|string',
        ]);

        $itemIds = [];
        foreach (Cart::getContent() as $item) {
            $itemIds[] = $item->id;
        }

        // Create Stripe Charge
        $createPurchaseRequest = $this->stripeOmnipay->purchase([
            'amount' => (Cart::getTotal() * 100),
            "currency" => config('settings.currency', 'USD'),
            "source" => config('settings.payment_stripe_test_mode') ? 'tok_visa' : $this->request->input('stripeToken'),
            'description' => implode('|', $itemIds),
        ]);

        // Check Stripe Charge Response
        $createPurchaseResponse = $createPurchaseRequest->send();
        if ($createPurchaseResponse->isSuccessful()) {
            $stripeChargeReferenceId = $createPurchaseResponse->getTransactionReference();

            // Create Order DB record(s)
            foreach (Cart::getContent() as $item) {
                $order = new Order();
                $order->user_id = auth()->user()->id;
                $order->orderable_id = $item->attributes->orderable_id;
                $order->orderable_type = $item->attributes->orderable_type;
                $order->payment = 'stripe';
                $order->amount = $item->price;
                $order->currency = config('settings.currency', 'USD');
                $order->payment_status = $createPurchaseResponse->isSuccessful();
                $order->transaction_id = $stripeChargeReferenceId;
                $order->save();
            }

            // Clear the cart items
            Cart::clear();

            // Redirect the user if needed
            if ($this->request->is('api*')) {
                echo '<script type="text/javascript">                        
                        setTimeout(function () {
                            window.location.href = "' . config('settings.deeplink_scheme', 'musicengine') . '://engine/payment/success";
                        }, 1000);
                      </script>';
                exit;
            }
            return response()->json($stripeChargeReferenceId);
        } else {
            return response()->json([
                'message' => 'Payment failed',
                'errors' => array(
                    'message' => array(
                        __('web.PAYMENT_FAILED_DESCRIPTION')
                    )
                )
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

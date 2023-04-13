<?php

namespace App\Models;

use App\Traits\SanitizedRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Log;
use Omnipay\Omnipay;
use Stripe\StripeClient;

class Subscription extends Model
{
    use SanitizedRequest;
    use SoftDeletes;

    protected static function booted()
    {
        static::created(function ($subscription) {
            switch ($subscription->service->plan_period_format) {
                case 'D':
                    $end_at = Carbon::now()->addDays($subscription->service->plan_period);
                    break;
                case 'W':
                    $end_at = Carbon::now()->addWeeks($subscription->service->plan_period);
                    break;
                case 'M':
                    $end_at = Carbon::now()->addMonths($subscription->service->plan_period);
                    break;
                case 'Y':
                    $end_at = Carbon::now()->addYears($subscription->service->plan_period);
                    break;
                default:
                    $end_at = Carbon::now()->addDays(1);
                    break;
            }

            RoleUser::updateOrCreate([
                'user_id' => $subscription->user->id,
            ], [
                'role_id' => $subscription->service->role_id,
                'end_at' => $end_at
            ]);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function cancelStripeSubscription(): ?bool
    {
        try {
            $stripeClient = new StripeClient(config('settings.payment_stripe_test_mode') ? config('settings.payment_stripe_test_key') : env('STRIPE_SECRET_API'));
            $stripeSubscription = $stripeClient->subscriptions->cancel($this->token);

//            $this->payment_status = 0;
//            $this->next_billing_date = Carbon::parse($stripeSubscription->current_period_end);
//            $this->last_payment_date = now();
//            $this->deleted_at = now();
//            $this->save();
            $this->delete();
            return true;

            return $stripeSubscription;
        } catch (\Exception $exception) {
            Log::error($exception->getTraceAsString());
        }
        return null;
    }

    public function cancelPaypalSubscription(): ?bool
    {
        $omniPayPalGateway = Omnipay::create('PayPal_Rest');
        $omniPayPalGateway->setClientId(env('PAYPAL_APP_CLIENT_ID'));
        $omniPayPalGateway->setSecret(env('PAYPAL_APP_SECRET'));
        if (boolval(env('PAYPAL_APP_SANDBOX_TESTING'))) {
            $omniPayPalGateway->setTestMode(env('PAYPAL_APP_SANDBOX_TESTING'));
        }

        $suspendSubscriptionRequest = $omniPayPalGateway->suspendSubscription([
            'transactionReference' => $this->token,
            'description' => "Suspending the agreement.",
        ]);

        $suspendSubscriptionResponse = $suspendSubscriptionRequest->send();

        if ($suspendSubscriptionResponse->isSuccessful()) {
            $this->delete();
            return true;
        }
        return null;
    }
}
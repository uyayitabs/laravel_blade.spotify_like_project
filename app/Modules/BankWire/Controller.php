<?php

namespace App\Modules\BankWire;

use App\Models\Order;
use App\Models\Service;
use Carbon\Carbon;
use Cart;
use Illuminate\Http\Request;
use App\Models\Email;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use View;
use Session;
use Validator;
use Image;

class Controller
{
    private $endpoint;
    private $_APIKey;

    private $request;

    public function __construct(Request $request, $protocol = 'https')
    {
        $this->endpoint = config('payment.gateway.bankwire.endpoint');
        $this->_protocol = $protocol;
        $this->request = $request;
        $this->_APIKey = config('payment.gateway.bankwire.public_key');
        View::addLocation(app_path() . '/Modules/BankWire/views');
    }

    public function subscriptionAuthorization()
    {
        if(auth()->user()->subscription) {
            abort(403, 'You are already have a subscription.');
        }

        return View::make('bankwire.index')
            ->with('formUrl', route('frontend.bankwire.subscription.callback', ['id' => $this->request->route('id')]));
    }

    public function subscriptionCallback()
    {
        if(auth()->user()->subscription) {
            abort(403, 'You are already have a subscription.');
        }

        if($this->request->input('transactionReference')) {
            $request = $this->getSingleCollection($this->request->input('transactionReference'));

            if($request->result->data->transactionStatus === "SUCCEEDED")
            {
                $message = 'SUCCEEDED';
                $service = Service::findOrFail($this->request->route('id'));
                $subscription = new Subscription();
                $subscription->gate = 'bankwire';
                $subscription->user_id = auth()->user()->id;
                $subscription->service_id = $service->id;
                $subscription->payment_status = 1;
                $subscription->transaction_id = $this->request->input('transaction_id');
                $subscription->token = $this->request->input('transaction_id');
                $subscription->trial_end = null;

                switch ($service->plan_period_format) {
                    case 'D':
                        $next_billing_date = Carbon::now()->addDays($service->plan_period)->format('Y-m-d\TH:i:s\Z');
                        break;
                    case 'W':
                        $next_billing_date = Carbon::now()->addWeeks($service->plan_period)->format('Y-m-d\TH:i:s\Z');
                        break;
                    case 'M':
                        $next_billing_date = Carbon::now()->addMonths($service->plan_period)->format('Y-m-d\TH:i:s\Z');
                        break;
                    case 'Y':
                        $next_billing_date = Carbon::now()->addYears($service->plan_period)->format('Y-m-d\TH:i:s\Z');
                        break;
                    default:
                        $next_billing_date = date("Y-m-d\TH:i:s\Z", strtotime('+2 minute'));
                }

                $subscription->next_billing_date = $next_billing_date;
                $subscription->cycles = 1;
                $subscription->amount = $service->price;
                $subscription->currency = config('settings.currency', 'USD');

                if(! $service->trial) {
                    $subscription->last_payment_date = Carbon::now();
                }

                $subscription->save();

                (new Email)->subscriptionReceipt(auth()->user(), $subscription);

                echo '<script type="text/javascript">
            var opener = window.opener;
            if(opener) {
                opener.Payment.subscriptionSuccess();
                window.close();
            }
            </script>';

                exit;

            } else {
                $message = 'Payment still on hold, please approve the payment';
            }

            return View::make('bankwire.verify')
                ->with('transactionReference', $this->request->input('transactionReference'))
                ->with('status', 'failed')
                ->with('message', $message);
        } else {

            $service = Service::findOrFail($this->request->route('id'));
            $request = collect($this->requestPayment($this->request->input('number'), round($service->price) , $service->title,"Pay for Subscription"));

            if(isset($request['result']) && isset($request['result']->code) && $request['result']->code === 202)
            {
                return View::make('bankwire.verify')
                    ->with('transactionReference', $request['result']->transactionReference);
            } else {
                return redirect()->route('frontend.bankwire.purchase.authorization')->with('status', 'failed')->with('message', $request->first()->{key($request->first())}[0]);
            }
        }
    }

    public function purchaseAuthorization()
    {
        return View::make('bankwire.index')
            ->with('formUrl', route('frontend.bankwire.purchase.callback'));
    }

    public function purchaseCallback()
    {
        $validator = Validator::make($this->request->all(), [ 'artwork' => 'required|image|mimes:jpeg,png,jpg,gif|max:' . config('settings.max_image_file_size', 8096) ]);

        if ($validator->fails()) {
            return redirect()->back()->with('status', 'failed')->with('message', trans('Please upload image filetype only (png, bmp, gif).'));
        } else {
            if($this->request->file('artwork')) {
                Cart::session(auth()->user()->id);
                foreach (Cart::getContent() as $item) {
                    $order = new Order();
                    $order->user_id = auth()->user()->id;
                    $order->orderable_id = $item->attributes->orderable_id;
                    $order->orderable_type = $item->attributes->orderable_type;
                    $order->payment = 'bankwire';
                    $order->amount = $item->price;
                    $order->currency = config('settings.currency', 'USD');
                    $order->payment_status = 0;
                    $order->transaction_id = $this->request->input('transaction_id');

                    $order->clearMediaCollection('artwork');
                    $order->addMediaFromBase64(base64_encode(Image::make($this->request->file('artwork'))->orientate()->fit(intval(config('settings.image_artwork_max', 500)),  intval(config('settings.image_artwork_max', 500)))->encode('jpg', config('settings.image_jpeg_quality', 90))->encoded))
                        ->usingFileName(time(). '.jpg')
                        ->toMediaCollection('artwork', config('settings.storage_artwork_location', 'public'));

                    $order->save();
                }

                Cart::clear();

                echo '<script type="text/javascript">
                var opener = window.opener;
                if(opener) {
                    opener.Payment.purchaseSuccess();
                    window.close();
                }
                
             </script>';
                exit();

            } else {
                $message = 'Payment still on hold, please approve the payment';
            }

            return View::make('bankwire.verify')
                ->with('transactionReference', $this->request->input('transactionReference'))
                ->with('status', 'failed')
                ->with('message', $message);
        }
    }
}
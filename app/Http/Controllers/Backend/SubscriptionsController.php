<?php

/**
 * Created by NiNaCoder.
 * Date: 2019-08-06
 * Time: 23:14
 */

namespace App\Http\Controllers\Backend;

use App\Models\Artist;
use App\Models\Email;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use App\Models\Subscription;
use PayPal\Api\Agreement;
use Stripe\StripeClient;

class SubscriptionsController
{
    private $request;
    private $select;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index()
    {
        $subscriptions = Subscription::withoutTrashed()
            ->orderBy('id', 'desc');

        if ($this->request->has('term')) {
            $term = $this->request->input('term');
            $subscriptions = $subscriptions->whereHas('user', function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%');
            });
        }

        $subscriptions = $subscriptions->paginate(20);


        $total = DB::table('subscriptions')->where('deleted_at', '=', null)->count();

        return view('backend.subscriptions.index')
            ->with('total', $total)
            ->with('term', $this->request->input('term'))
            ->with('subscriptions', $subscriptions);
    }

    public function edit()
    {
        $order = Subscription::findOrFail($this->request->route('id'));

        return view('backend.subscriptions.form')
            ->with('order', $order);
    }

    public function delete()
    {
        $subscription = Subscription::find(request()->route('id'));

        if ($subscription && $subscription->gate === 'stripe') {
            if ($subscription->cancelStripeSubscription()) {
                return redirect()->route('backend.subscriptions')->with('status', 'success')->with('message', 'Subscription successfully cancelled!');
            }
            return redirect()->route('backend.subscriptions')->with('status', 'failed')->with('message', 'Encountered error cancelling subscription!');
        } else if ($subscription && $subscription->gate === 'paypal') {
            if ($subscription->cancelPaypalSubscription()) {
                return redirect()->route('backend.subscriptions')->with('status', 'success')->with('message', 'Subscription successfully cancelled!');
            }
            return redirect()->route('backend.subscriptions')->with('status', 'failed')->with('message', 'Encountered error cancelling subscription!');
        }

        return redirect()->route('backend.subscriptions')->with('status', 'failed')->with('message', 'Encountered error cancelling subscription!');
    }
}

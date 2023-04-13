<?php
/**
 * Created by NiNaCoder.
 * Date: 2019-06-23
 * Time: 18:10
 */


Route::group(['middleware' => 'api'], function () {
    Route::get('subscription/stripe/{id}', 'StripeController@subscriptionAuthorization')->name('stripe.subscription.authorization');
    Route::post('subscription/stripe/callback', 'StripeController@subscriptionCallback')->name('stripe.subscription.callback');
    Route::get('purchase/stripe/authorization', 'StripeController@purchaseAuthorization')->name('stripe.purchase.authorization');
    Route::post('purchase/stripe/callback', 'StripeController@purchaseCallback')->name('stripe.purchase.callback');

    // Omnipay Stripe
    Route::get('subscription/stripe/v2/{id}', 'StripeController@subscriptionAuthorizationV2')->name('stripe.subscription.authorization.v2');
    Route::post('subscription/stripe/v2/callback', 'StripeController@subscriptionCallbackV2')->name('stripe.subscription.callback.v2');
    Route::get('purchase/stripe/v2/authorization', 'StripeController@purchaseAuthorizationV2')->name('stripe.purchase.authorization.v2');
    Route::post('purchase/stripe/v2/callback', 'StripeController@purchaseCallbackV2')->name('stripe.purchase.callback.v2');
});
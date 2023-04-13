<?php
/**
 * Created by NiNaCoder.
 * Date: 2019-08-01
 * Time: 20:34
 */

Route::get('lyricist/{id}/{slug}', '\App\Http\Controllers\Backend\LyricistsController@index')->name('lyricist');
Route::get('lyricist/{id}/{slug}/albums', '\App\Http\Controllers\Backend\LyricistsController@albums')->name('lyricist.albums');
Route::get('lyricist/{id}/{slug}/similar-lyricist', '\App\Http\Controllers\Backend\LyricistsController@similar')->name('lyricist.similar');
Route::get('lyricist/{id}/{slug}/followers', '\App\Http\Controllers\Backend\LyricistsController@followers')->name('lyricist.followers');
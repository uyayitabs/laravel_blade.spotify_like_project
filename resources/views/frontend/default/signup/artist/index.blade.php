@extends('index')

@section('content')
    <style>
        #main {
            padding-left: 0px;
            margin-right: 0;
            padding-top: 0px;
            padding-bottom: 0px;
            overflow: hidden;
        }
        .claim-hero {
            height: 100vh;
        }
        .container-artist-signup {
            margin: auto;
        }
        .lightbox {
            vertical-align: middle;
            max-height: none;
        }
    </style>

    <script>
        history.navigationMode = 'compatible';
        (function (factory) {
            if (typeof define === 'function' && define.amd) {
                define(['jquery'], factory);
            } else if (typeof exports === 'object') {
                factory(require('jquery'));
            } else {
                factory(jQuery);
            }
        })(function ($) {

            function showSignupForm() {
                setTimeout(function() {
                    $('.lightbox-signup-artist').removeClass('hide');
                    $('body').removeClass('no-scroll');
                }, 100);
            }

            showSignupForm();

            $(document).on('click', ".lightbox-close.close", function () {
                if (window.location.pathname === "/signup/artist") {
                    showSignupForm();
                }
            });

            $(window).focus(function() {
                showSignupForm();
            });

            $(window).blur(function() {
                setTimeout(function() {
                    $('body').removeClass('no-scroll');
                }, 100);
            });
        });
    </script>

    <div class="claim-hero">
            <div class="container-artist-signup">
                <div class="row">
                    <div class="col">
                        <div class="vertical-align">

                            <div class="lightbox lightbox-signup-artist">
                                <div class="lbcontainer">
                                    <form id="signup-artist-form" method="POST" action="{{ route('frontend.auth.signup_artist') }}">
                                        <div class="lightbox-header">
                                            <h2 class="title" data-translate-text="POPUP_SIGNUP_ARTIST_TITLE">{{ __('web.POPUP_SIGNUP_ARTIST_TITLE') }}</h2>
                                            @yield('lightbox-close')
                                        </div>
                                        <div class="lightbox-content">
                                            <div class="lightbox-error error hide"></div>
                                            <div class="lightboxsettings.disable_register-content-block">
                                                @if(config('settings.disable_register'))
                                                    <p class="mt-3 mb-3">{{ __('web.REGISTRATION_DISABLED') }}</p>
                                                @else
                                                    @if(config('settings.social_login'))
                                                        <div class="lb-nav-outer">
                                                            <div class="lb-nav-container no-center">
                                                                <div class="row">
                                                                    @if(config('settings.facebook_login'))
                                                                        <div class="col">
                                                                            <a class="lb-facebook-login btn share-btn third-party facebook" data-action="social-login" data-service="facebook">
                                                                                <svg class="icon" width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" xml:space="preserve"><path d="M448,0H64C28.704,0,0,28.704,0,64v384c0,35.296,28.704,64,64,64h192V336h-64v-80h64v-64c0-53.024,42.976-96,96-96h64v80h-32c-17.664,0-32-1.664-32,16v64h80l-32,80h-48v176h96c35.296,0,64-28.704,64-64V64C512,28.704,483.296,0,448,0z"></path></svg>
                                                                                <span class="text desktop" data-translate-text="SIGN_IN_FACEBOOK">{{ __('web.SIGN_IN_FACEBOOK') }}</span>
                                                                                <span class="text mobile" data-translate-text="FACEBOOK">{{ __('web.FACEBOOK') }}</span>
                                                                            </a>
                                                                        </div>
                                                                    @endif
                                                                    @if(config('settings.google_login'))
                                                                        <div class="col">
                                                                            <a class="lb-google-login btn share-btn third-party google" data-action="social-login" data-service="google">
                                                                                <svg class="icon icon-google-plus-white-active" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#fff">
                                                                                    <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-10.333 16.667c-2.581 0-4.667-2.087-4.667-4.667s2.086-4.667 4.667-4.667c1.26 0 2.313.46 3.127 1.22l-1.267 1.22c-.347-.333-.954-.72-1.86-.72-1.593 0-2.893 1.32-2.893 2.947s1.3 2.947 2.893 2.947c1.847 0 2.54-1.327 2.647-2.013h-2.647v-1.6h4.406c.041.233.074.467.074.773-.001 2.666-1.787 4.56-4.48 4.56zm11.333-4h-2v2h-1.334v-2h-2v-1.333h2v-2h1.334v2h2v1.333z"></path>
                                                                                </svg>
                                                                                <span class="text desktop" data-translate-text="SIGN_IN_GOOGLE">{{ __('web.SIGN_IN_GOOGLE') }}</span>
                                                                                <span class="text mobile" data-translate-text="GOOGLE">{{ __('web.GOOGLE') }}</span>
                                                                            </a>
                                                                        </div>
                                                                    @endif
                                                                    @if(config('settings.twitter_login'))
                                                                        <div class="col">
                                                                            <a class="lb-twitter-login btn share-btn third-party twitter" data-action="social-login" data-service="twitter">
                                                                                <svg class="icon icon-twitter-white-active" width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 510 510" xml:space="preserve"><path d="M459,0H51C22.95,0,0,22.95,0,51v408c0,28.05,22.95,51,51,51h408c28.05,0,51-22.95,51-51V51C510,22.95,487.05,0,459,0z M400.35,186.15c-2.55,117.3-76.5,198.9-188.7,204C165.75,392.7,132.6,377.4,102,359.55c33.15,5.101,76.5-7.649,99.45-28.05c-33.15-2.55-53.55-20.4-63.75-48.45c10.2,2.55,20.4,0,28.05,0c-30.6-10.2-51-28.05-53.55-68.85c7.65,5.1,17.85,7.65,28.05,7.65c-22.95-12.75-38.25-61.2-20.4-91.8c33.15,35.7,73.95,66.3,140.25,71.4c-17.85-71.4,79.051-109.65,117.301-61.2c17.85-2.55,30.6-10.2,43.35-15.3c-5.1,17.85-15.3,28.05-28.05,38.25c12.75-2.55,25.5-5.1,35.7-10.2C425.85,165.75,413.1,175.95,400.35,186.15z"></path></svg>
                                                                                <span class="text desktop" data-translate-text="SIGN_IN_TWITTER">{{ __('web.SIGN_IN_TWITTER') }}</span>
                                                                                <span class="text mobile" data-translate-text="TWITTER">{{ __('web.TWITTER') }}</span>
                                                                            </a>
                                                                        </div>
                                                                    @endif
                                                                    @if(config('settings.apple_login'))
                                                                        <div class="col">
                                                                            <a class="lb-apple-login btn share-btn third-party apple" data-action="social-login" data-service="apple">
                                                                                <svg class="icon icon-apple-white-active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"  width="24" height="24" xml:space="preserve">
                                                                                    <g>
                                                                                        <path d="M185.255,512c-76.201-0.439-139.233-155.991-139.233-235.21c0-129.404,97.075-157.734,134.487-157.734   c16.86,0,34.863,6.621,50.742,12.48c11.104,4.087,22.588,8.306,28.975,8.306c3.823,0,12.832-3.589,20.786-6.738   c16.963-6.753,38.071-15.146,62.651-15.146c0.044,0,0.103,0,0.146,0c18.354,0,74.004,4.028,107.461,54.272l7.837,11.777   l-11.279,8.511c-16.113,12.158-45.513,34.336-45.513,78.267c0,52.031,33.296,72.041,49.292,81.665   c7.061,4.248,14.37,8.628,14.37,18.208c0,6.255-49.922,140.566-122.417,140.566c-17.739,0-30.278-5.332-41.338-10.034   c-11.191-4.761-20.845-8.862-36.797-8.862c-8.086,0-18.311,3.823-29.136,7.881C221.496,505.73,204.752,512,185.753,512H185.255z"/>
                                                                                        <path d="M351.343,0c1.888,68.076-46.797,115.304-95.425,112.342C247.905,58.015,304.54,0,351.343,0z"/>
                                                                                    </g>
                                                                                </svg>
                                                                                <span class="text desktop" data-translate-text="SIGN_IN_APPLE">{{ __('web.SIGN_IN_APPLE') }}</span>
                                                                                <span class="text mobile" data-translate-text="APPLE">{{ __('web.APPLE') }}</span>
                                                                            </a>
                                                                        </div>
                                                                    @endif
                                                                    @if(config('settings.discord_login'))
                                                                        <div class="col">
                                                                            <a class="lb-apple-login btn share-btn third-party discord" data-action="social-login" data-service="discord">
                                                                                <svg class="icon icon-discord-white-active" height="24" viewBox="0 0 510.901 510.901" width="24" xmlns="http://www.w3.org/2000/svg"><g><g><path d="m483.927 224.185c-15.602-49.641-25.629-72.626-30.219-81.911l-.002-.003c-3.69-7.601-13.042-23.957-13.414-24.607-1.31-1.599-33.786-40.463-112.059-69.209l-10.343 28.16c37.01 13.592 62.342 29.288 78.034 41.089-43.344-12.925-94.883-20.835-140.53-20.835-45.702 0-97.303 7.933-140.679 20.886 14.528-10.779 40.946-27.464 78.183-41.14l-10.343-28.16c-78.272 28.746-110.749 67.61-112.059 69.209 0 0-7.715 12.454-14.197 26.129-3.885 8.196-13.949 26.91-29.536 80.716-19.549 67.5-26.492 162.909-26.763 166.726 3.451 7.549 43.451 71.212 151.089 71.212l29.495-42.71c24.345 6.315 49.46 9.51 74.812 9.51 25.406 0 50.571-3.209 74.962-9.549l29.313 42.749c109.779 0 145.613-61.496 151.23-71.384-.434-4.545-11.306-117.023-26.974-166.878zm-28.73 181.831c-22.833 16.16-49.659 24.98-79.833 26.267l-15.133-22.069c20.871-7.865 40.895-18.099 59.712-30.624l-16.623-24.973c-20.989 13.971-43.649 24.781-67.368 32.266v.029s-39.276 12.335-80.555 12.335-80.445-12.299-80.445-12.299v-.031c-23.759-7.486-46.457-18.308-67.478-32.3l-16.624 24.972c18.85 12.547 38.912 22.795 59.823 30.665l-15.213 22.029c-30.187-1.281-57.024-10.102-79.865-26.268-12.734-9.013-20.938-18.15-24.881-23.069 1.74-20.548 8.86-94.827 24.869-150.091 12.015-41.475 20.309-60.884 24.837-70.255 0 0 116.109-35.732 174.977-35.732 58.82 0 130.587 14.613 174.584 35.546 4.757 10.838 13.226 32.259 25.327 70.765 12.767 40.623 22.29 126.948 24.665 149.897-4.001 4.971-12.168 14.016-24.776 22.94z"/></g><path d="m156.038 252.991h30v50h-30z"/><path d="m324.754 252.991h30v50h-30z"/></g></svg>
                                                                                <span class="text desktop" data-translate-text="SIGN_IN_DISCORD">{{ __('web.SIGN_IN_DISCORD') }}</span>
                                                                                <span class="text mobile" data-translate-text="DISCORD">{{ __('web.DISCORD') }}</span>
                                                                            </a>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div id="signup-stage-signup-artist" class="signup-stage">
                                                        <div class="row">
                                                            <div class="control control-group col-lg-6 col-12">
                                                                <label class="control-label" for="signup-email" data-translate-text="FORM_EMAIL_ADDRESS">{{ __('web.FORM_EMAIL_ADDRESS') }}</label>
                                                                <div class="controls"><input class="signup-text" id="signup-email" name="email" type="text"></div>
                                                            </div>
                                                            <div class="control control-group col-lg-6 col-12">
                                                                <label class="control-label" for="signup-fname" data-translate-text="FORM_NAME">{{ __('web.FORM_NAME') }}</label>
                                                                <div class="controls"><input class="signup-text" id="signup-fname" name="name" type="text"></div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="control control-group col-lg-6 col-12">
                                                                <label class="control-label" for="signup-password1" data-translate-text="FORM_PASSWORD">{{ __('web.FORM_PASSWORD') }}</label>
                                                                <div class="controls"><input class="signup-text" id="signup-password1" name="password" type="password"></div>
                                                            </div>
                                                            <div class="control control-group col-lg-6 col-12">
                                                                <label class="control-label" for="signup-password2" data-translate-text="FORM_CONFIRM_PASSWORD">{{ __('web.FORM_CONFIRM_PASSWORD') }}</label>
                                                                <div class="controls"><input class="signup-text" id="signup-password2" name="password_confirmation" type="password"></div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="control control-group col-12">
                                                                <label class="control-label" for="artist-claiming-email" data-translate-text="FORM_ARTIST_OR_BAND">{{ __('web.FORM_ARTIST_OR_BAND') }}</label>
                                                                <div class="controls">
                                                                    <input type="text" name="artist_name" value="" autocomplete="off">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="control-group custom-url col-12">
                                                                <label class="control-label" for="username" data-translate-text="LB_SIGNUP_FORM_URL">{{ __('web.LB_SIGNUP_FORM_URL') }}</label>
                                                                <div class="controls">
                                                                    <div class="input-prepend">
                                                                        <span class="add-on">{{ route('frontend.homepage') }}/</span>
                                                                        <input id="signup-username" name="username" class="signup-text" size="16" value="" type="text" autocomplete="off" maxlength="30" autocapitalize="none" autocorrect="off">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <input class="hide custom-checkbox" type="checkbox" name="artist" id="im-artist" checked="checked">
                                                        <p class="tos small" data-translate-text="FORM_TOS_2_ARTIST">{!! __('web.FORM_TOS_2_ARTIST') !!}</p>
                                                    </div>
                                                    <div id="signup-stage-verify" class="signup-stage hide">
                                                        <h2 class="text-center"  data-translate-text="LB_VERIFY_ACCOUNT">{{ __('web.LB_VERIFY_ACCOUNT') }}</h2>
                                                        <p  data-translate-text="LB_VERIFY_ACCOUNT_DESCRIPTION">{{ __('web.LB_VERIFY_ACCOUNT_DESCRIPTION') }}</p>
                                                    </div>
                                                    <div id="signup-stage-complete" class="signup-stage hide">
                                                        <div class="url-callout">
                                                            <p class="profile-url text-center"></p>
                                                        </div>
                                                        <div class="complete-todo">
                                                            <svg class="todo-icon profile" height="512" viewBox="0 0 480.063 480.063" width="512" xmlns="http://www.w3.org/2000/svg"><path d="m394.032 424.803v39.26c0 4.42-3.58 8-8 8h-292c-4.42 0-8-3.58-8-8v-39.26c0-41.19 33.39-74.56 74.59-74.57 14.56-.01 27.38-7.5 34.76-18.86 7.414-11.394 6.65-21.302 6.65-29.31l.15-.37c-35.9-14.86-61.15-50.23-61.15-91.5v-3.13c-14.255 0-25-11.265-25-24.54v-41.56c-.32-14.47.34-65.5 37.2-101.03 42.86-41.31 110.78-37.93 159.98-15.83 1.6.72 1.55 3.01-.07 3.68l-12.83 5.28c-1.92.79-1.51 3.62.55 3.84l6.23.67c29.83 3.19 57.54 19.39 74.72 46.35.46.73.33 1.84-.26 2.47-10.6 11.21-16.52 26.09-16.52 41.56v54.57c0 13.55-10.99 24.54-24.54 24.54h-1.46v3.13c0 41.27-25.25 76.64-61.15 91.5l.15.37c0 7.777-.827 17.82 6.65 29.31 7.38 11.36 20.2 18.85 34.76 18.86 41.2.01 74.59 33.38 74.59 74.57z" fill="#ffdfba"></path><path d="m394.032 424.803v39.26c0 4.418-3.582 8-8 8h-292c-4.418 0-8-3.582-8-8v-39.26c0-41.19 33.395-74.555 74.585-74.57 14.564-.005 27.387-7.504 34.765-18.86 25.754 22.002 63.531 22.015 89.3 0 7.377 11.356 20.201 18.855 34.765 18.86 41.19.015 74.585 33.38 74.585 74.57z" fill="#fe4f60"></path><path d="m381.807 83.928c.464.729.334 1.833-.259 2.461-10.597 11.218-16.517 26.093-16.517 41.564v54.57c0 12.388-9.333 24.54-26 24.54v-61.77c0-26.51-21.49-48-48-48h-102c-26.51 0-48 21.49-48 48v61.77c-14.255 0-25-11.265-25-24.54v-41.56c-.32-14.47.34-65.5 37.2-101.03 42.858-41.311 110.784-37.929 159.977-15.827 1.601.719 1.558 3.01-.065 3.678l-12.831 5.282c-1.918.79-1.514 3.617.548 3.838l6.232.669c29.834 3.187 57.537 19.387 74.715 46.355z" fill="#42434d"></path><path d="m339.032 210.193c0 54.696-44.348 99-99 99-51.492 0-99-40.031-99-102.13v-61.77c0-26.51 21.49-48 48-48h102c26.51 0 48 21.49 48 48z" fill="#ffebd2"></path><path d="m217.616 274.121c16.277 10.183 3.442 35.156-14.376 28.004-36.634-14.704-62.208-50.404-62.208-91.932v-64.9c0-10.084 3.11-19.442 8.423-27.168 6.514-9.473 21.577-5.288 21.577 7.168v64.9c0 36.51 19.192 66.79 46.584 83.928z" fill="#fff3e4"></path><path d="m279.162 318.483c-24.637 10.313-51.712 11.113-78.26 0 1.356-5.626 1.13-9.27 1.13-16.42l.15-.37c24.082 9.996 51.571 10.016 75.7 0l.15.37c0 7.153-.226 10.796 1.13 16.42z" fill="#ffd6a6"></path><path d="m200.913 374.39c-3.698 1.163-7.664 1.804-11.916 1.841-41.296.364-74.966 33.017-74.966 74.315v7.517c0 7.732-6.268 14-14 14h-6c-4.418 0-8-3.582-8-8v-39.26c0-41.191 33.395-74.555 74.585-74.57 14.564-.005 27.387-7.504 34.765-18.86 2.974 2.54 6.158 4.823 9.512 6.822 14.753 8.791 12.402 31.044-3.98 36.195z" fill="#ff6d7a"></path><path d="m279.15 374.39c3.698 1.163 7.664 1.804 11.916 1.841 41.296.364 74.966 33.017 74.966 74.315v7.517c0 7.732 6.268 14 14 14h6c4.418 0 8-3.582 8-8v-39.26c0-41.191-33.395-74.555-74.585-74.57-14.564-.005-27.387-7.504-34.765-18.86-2.974 2.54-6.158 4.823-9.512 6.822-14.753 8.791-12.402 31.044 3.98 36.195z" fill="#e84857"></path><path d="m313.142 27.783c-11.758 4.839-13.434 5.906-17.508 5.274-65.674-10.18-123.294 16.993-142.862 80.786v.01c-7.32 8.42-11.74 19.42-11.74 31.44v37.523c0 16.188-25 17.315-25-.293v-41.56c-.32-14.47.34-65.5 37.2-101.03 42.86-41.31 110.78-37.93 159.98-15.83 1.6.72 1.55 3.01-.07 3.68z" fill="#4d4e59"></path><path d="m402.032 424.806v47.257c0 4.418-3.582 8-8 8s-8-3.582-8-8v-47.257c0-36.795-29.775-66.572-66.573-66.571-17.411 0-33.208-8.87-42.259-23.728-2.298-3.773-1.103-8.696 2.671-10.994 3.773-2.299 8.695-1.103 10.994 2.671 6.122 10.051 16.811 16.051 28.594 16.051 45.637-.002 82.573 36.93 82.573 82.571zm-139.606-80.193c.941 4.317-1.796 8.579-6.113 9.52-21.054 4.587-42.467-.005-59.516-11.642-16.878 18.087-39.176 15.744-36.191 15.744-36.795-.001-66.573 29.773-66.573 66.571v47.257c0 4.418-3.582 8-8 8s-8-3.582-8-8v-47.257c0-45.636 36.929-82.571 82.571-82.571 18.462 0 33.429-14.875 33.429-33.342v-2.107c-34.919-16.697-59.429-51.784-60.923-92.643-14.37-3.455-25.077-16.317-25.077-31.62v-41.473c-.437-20.3 2.577-71.143 39.648-106.877 45.775-44.126 119.183-41.323 173.161-15.338 5.261 2.535 6.06 9.643 1.691 13.324 27.345 6.67 50.925 23.48 66.074 47.538.782 1.239 2.214 3.184 1.84 6.287-.232 1.931-.807 3.565-2.295 5.075-9.75 9.888-15.119 22.991-15.119 36.896v54.57c0 4.418-3.582 8-8 8s-8-3.582-8-8v-54.57c0-16.037 5.479-31.259 15.542-43.487-15.338-21.936-39.268-36.044-66.332-38.942l-14.061-1.506c-8.222-.88-9.835-12.207-2.194-15.352l6.395-2.633c-83.286-29.035-172.351 3.226-172.351 114.928v41.56c0 6.348 3.656 11.865 9 14.636v-51.863c0-30.878 25.122-56 56-56h102c30.878 0 56 25.12 56 55.997v65.503c0 69.574-67.988 122.42-137.17 102.053-.45 5.708-1.871 11.216-4.186 16.336 13.458 9.242 30.453 12.97 47.23 9.314 4.317-.94 8.579 1.797 9.52 6.114zm-22.394-43.425c50.178 0 91-40.822 91-91v-64.895c0-22.054-17.944-39.997-40-39.997h-102c-22.056 0-40 17.944-40 40v64.892c0 50.178 40.822 91 91 91zm81 137.875h-24c-4.418 0-8 3.582-8 8s3.582 8 8 8h24c4.418 0 8-3.582 8-8s-3.582-8-8-8z"></path></svg>                                <div class="todo-description">
                                                                <h3 class="todo-header" data-translate-text="LB_SIGNUP_CUSTOMIZE_PROFILE">{{ __('web.LB_SIGNUP_CUSTOMIZE_PROFILE') }}</h3>
                                                                <p class="todo-text" data-translate-text="LB_SIGNUP_CUSTOMIZE_PROFILE_SUBTEXT">{{ __('web.LB_SIGNUP_CUSTOMIZE_PROFILE_SUBTEXT') }}</p>
                                                            </div>
                                                            <a class="btn btn-secondary edit-profile" data-translate-text="EDIT_PROFILE">{{ __('web.EDIT_PROFILE') }}</a>
                                                        </div>
                                                        <div class="complete-todo">
                                                            <svg class="todo-icon popular" id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g id="XMLID_1159_"><g id="XMLID_2123_"><path id="XMLID_2121_" d="m189.412 153.557 40.002-124.408c8.199-25.5 44.263-25.544 52.524-.063l40.355 124.471h131.388c26.747 0 37.854 34.238 16.2 49.939l-106.078 76.914 40.723 124.129c8.347 25.442-20.787 46.669-42.447 30.926l-105.675-76.807-106.049 77.265c-21.632 15.761-50.782-5.408-42.49-30.855l40.653-124.755-106.354-76.784c-21.709-15.673-10.622-49.972 16.154-49.972z" fill="#ffcd69"></path><g id="XMLID_2119_"><path id="XMLID_871_" d="m134.105 451.353c-7.69 0-15.38-2.436-22.093-7.311-13.442-9.762-18.803-26.275-13.655-42.071l38.393-117.818-100.44-72.516c-13.475-9.729-18.878-26.233-13.766-42.047s19.154-26.032 35.774-26.032h123.805l37.772-117.47c5.089-15.827 19.121-26.067 35.747-26.088h.049c16.604 0 30.638 10.201 35.761 26.002l38.112 117.556h13.423c5.523 0 10 4.478 10 10s-4.477 10-10 10h-20.694c-4.334 0-8.176-2.793-9.513-6.916l-40.354-124.472c-2.909-8.974-10.55-12.17-16.739-12.17-.007 0-.014 0-.021 0-6.193.008-13.84 3.219-16.731 12.21l-40.003 124.409c-1.33 4.135-5.176 6.939-9.52 6.939h-131.094c-9.441 0-14.84 6.294-16.744 12.184-1.904 5.891-1.211 14.153 6.443 19.681l106.354 76.785c3.524 2.544 5.001 7.073 3.654 11.206l-40.653 124.755c-2.924 8.973 1.384 16.055 6.392 19.69 5.007 3.636 13.076 5.54 20.702-.016l106.049-77.266c3.505-2.555 8.259-2.559 11.768-.007l105.675 76.807c7.637 5.55 15.704 3.633 20.708-.012 5.003-3.646 9.301-10.737 6.358-19.708l-40.723-124.13c-1.354-4.13.113-8.662 3.632-11.213l106.078-76.914c7.635-5.536 8.317-13.794 6.409-19.676s-7.307-12.167-16.738-12.167h-20.694c-5.523 0-10-4.478-10-10s4.477-10 10-10h20.694c16.602 0 30.64 10.204 35.762 25.996 5.123 15.792-.252 32.293-13.693 42.038l-100.172 72.632 38.449 117.199c5.181 15.792-.151 32.32-13.584 42.107-13.432 9.786-30.799 9.797-44.244.024l-99.789-72.528-100.167 72.98c-6.722 4.899-14.43 7.348-22.139 7.348z"></path></g><g id="XMLID_2151_"><path id="XMLID_856_" d="m256 512c-5.523 0-10-4.478-10-10v-80.877c0-5.522 4.477-10 10-10s10 4.478 10 10v80.877c0 5.522-4.477 10-10 10z"></path></g><g id="XMLID_2150_"><path id="XMLID_855_" d="m499.933 334.776c-1.024 0-2.065-.159-3.092-.492l-76.918-24.992c-5.252-1.707-8.127-7.349-6.42-12.601 1.706-5.252 7.348-8.123 12.601-6.421l76.918 24.992c5.252 1.707 8.127 7.349 6.42 12.601-1.374 4.226-5.294 6.913-9.509 6.913z"></path></g><g id="XMLID_2152_"><path id="XMLID_854_" d="m359.211 113.447c-2.038 0-4.094-.621-5.87-1.911-4.468-3.246-5.458-9.5-2.212-13.968l47.538-65.431c3.247-4.468 9.499-5.459 13.968-2.212 4.468 3.246 5.458 9.5 2.212 13.968l-47.538 65.431c-1.956 2.694-5.006 4.123-8.098 4.123z"></path></g><g id="XMLID_2154_"><path id="XMLID_853_" d="m152.789 113.447c-3.093 0-6.142-1.43-8.099-4.123l-47.538-65.43c-3.246-4.468-2.255-10.722 2.212-13.968 4.469-3.248 10.722-2.256 13.968 2.212l47.538 65.431c3.246 4.468 2.255 10.722-2.212 13.968-1.774 1.289-3.831 1.91-5.869 1.91z"></path></g><g id="XMLID_2156_"><path id="XMLID_852_" d="m12.067 334.776c-4.216 0-8.135-2.687-9.509-6.913-1.706-5.252 1.168-10.894 6.42-12.601l76.918-24.992c5.254-1.706 10.894 1.168 12.601 6.421 1.707 5.252-1.168 10.894-6.42 12.601l-76.917 24.992c-1.027.333-2.069.492-3.093.492z"></path></g></g><g id="XMLID_1847_"><g id="XMLID_1848_"><path id="XMLID_851_" d="m387.99 163.56c-2.63 0-5.21-1.069-7.07-2.93-1.86-1.86-2.93-4.44-2.93-7.07s1.07-5.21 2.93-7.069c1.86-1.86 4.43-2.931 7.07-2.931 2.63 0 5.21 1.07 7.07 2.931 1.86 1.859 2.93 4.439 2.93 7.069s-1.07 5.21-2.93 7.07-4.44 2.93-7.07 2.93z"></path></g></g></g></svg>                                <div class="todo-description">
                                                                <h3 class="todo-header" data-translate-text="LB_SIGNUP_ENJOY_POPULAR">{{ __('web.LB_SIGNUP_ENJOY_POPULAR') }}</h3>
                                                                <p class="todo-text" data-translate-text="LB_SIGNUP_ENJOY_POPULAR_SUBTEXT">{{ __('web.LB_SIGNUP_ENJOY_POPULAR_SUBTEXT') }}</p>
                                                            </div>
                                                            <a href="{{ route('frontend.trending') }}" class="btn btn-secondary popular-page" data-translate-text="VIEW_POPULAR_PAGE">{{ __('web.VIEW_POPULAR_PAGE') }}</a>
                                                        </div>
                                                        @if(config('settings.allow_artist_claim', 1))
                                                            <div class="complete-todo">
                                                                <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 457.5 457.5" xml:space="preserve" class="todo-icon popular">
                                            <path d="M300.15,222.7v5.2c0,39.6-31.8,71.9-71.4,71.9s-71.4-31.8-71.4-71.4v-5.7v-52.2v-52.1v-37
                                                c0-39.1,31.8-71.4,71.4-71.4c19.8,0,37.6,7.9,50.6,20.9s20.9,30.8,20.9,50.6v37v52v52.2H300.15z"></path>
                                                                    <path d="M309.75,227.9V81.4c0-21.8-8.2-42.3-23.6-57.6C270.85,8.5,250.65,0,228.85,0c-44.9,0-81.1,36.5-81.1,81.4v147
                                                c0,21.8,8.2,42.3,23.6,57.6c15.3,15.3,35.5,23.8,57.3,23.8C273.55,309.8,309.75,273.1,309.75,227.9z M167.45,232.5h37.8
                                                c5.5,0,10-4.5,10-10s-4.5-10-10-10h-37.5v-32h37.5c5.5,0,10-4.5,10-10s-4.5-10-10-10h-37.5v-32h37.5c5.5,0,10-4.5,10-10
                                                s-4.5-10-10-10h-37.5V81.4c0-30.5,21.9-55.8,51-60.6v43.4c0,5.5,4.5,10,10,10s10-4.5,10-10V20.8c12.7,2,24.1,7.9,33.3,17.1
                                                c11.6,11.6,17.7,27,17.7,43.5v27.1h-37.5c-5.5,0-10,4.5-10,10s4.5,10,10,10h37.5v32h-37.5c-5.5,0-10,4.5-10,10s4.5,10,10,10h37.5v32
                                                h-37.5c-5.5,0-10,4.5-10,10s4.5,10,10,10h37.8c-2.4,32-29,57.3-61.2,57.3c-16.5,0-31.9-6.4-43.5-18
                                                C174.65,261.2,168.45,247.5,167.45,232.5z"></path>
                                                                    <path d="M354.75,159.5c-5.5,0-10,4.5-10,10v58.9c0,31.1-12,60.3-33.9,82.2s-51.1,34-82.2,34s-60.2-12.1-82.1-34
                                                c-21.8-21.9-33.8-51.1-33.8-82.2v-58.9c0-5.5-4.5-10-10-10s-10,4.5-10,10v58.9c0,36.5,14.1,70.7,39.7,96.3
                                                c23.3,23.3,53.5,37.1,86.3,39.5v73.3h-46.8c-5.5,0-10,4.5-10,10s4.5,10,10,10h113.7c5.5,0,10-4.5,10-10s-4.5-10-10-10h-46.9v-73.3
                                                c32.7-2.3,63-16.1,86.3-39.5c25.7-25.7,39.7-59.9,39.7-96.4v-58.8C364.75,164,360.25,159.5,354.75,159.5z"></path>
                                            </svg>
                                                                <div class="todo-description">
                                                                    <h3 class="todo-header" data-translate-text="LB_SIGNUP_MAKE_MUSIC">{{ __('web.LB_SIGNUP_MAKE_MUSIC') }}</h3>
                                                                    <p class="todo-text" data-translate-text="LB_SIGNUP_MAKE_MUSIC_SUBTEXT">{{ __('web.LB_SIGNUP_MAKE_MUSIC_SUBTEXT') }}</p>
                                                                </div>
                                                                <a class="btn btn-secondary create-artist" data-translate-text="CLAIM_ARTIST">{{ __('web.CLAIM_ARTIST') }}</a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        @if(! config('settings.disable_register'))
                                            <div class="lightbox-footer">
                                                <div class="right">
                                                    <a class="btn btn-primary close hide" data-translate-text="CLOSE">{{ __('web.CLOSE') }}</a>
                                                    <button class="btn btn-primary" type="submit" data-translate-text="SIGN_UP">{{ __('web.SIGN_UP') }}</button>
                                                </div>
                                                <div class="left"></div>
                                            </div>
                                        @endif
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    {!! Advert::get('footer') !!}
@endsection
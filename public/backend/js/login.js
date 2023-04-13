(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        factory(require('jquery'));
    } else {
        factory(jQuery);
    }
})(function ($) {
    "use strict";
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    window.Connect = {
        thirdParty: {
            redirect: function (service) {
                window.open(route.route('frontend.auth.login.socialite.redirect', {'service': service}),'_blank','height=450,width=750');
            },
            callback: function (data, service){
                setTimeout(function() {
                    if(! User.isLogged()) {
                        User.SignIn.me();
                        $.engineLightBox.hide();
                        Toast.show("success", ('Successfully logged in with :service').replace(':service', service));
                        if (window.location.pathname === '/signup/artist') {
                            $(window).attr('location', '/artist-management');
                            return;
                        } else {
                            Cookies.set('ASK_USER_TO_SUBSCRIBE', true, {expires: 365});
                            window.location.reload();
                            return;
                        }
                    }
                    Connect.thirdParty.connected(data, service);
                }, 3000);
            },
            error: function(service){
                Toast.show("error", ('Your :service has been associated with another account.').replace(':service', service));
            },
            connected: function (data, service) {
                if(window.location.pathname === route.route('frontend.settings.services')) {
                    $(window).trigger({
                        type: "engineReloadCurrentPage"
                    });
                }
                if(service === 'facebook') {
                    Artist.claim.el.find('.facebook-icon-container > img').attr('src', data.avatar).removeClass('hide');
                    Artist.claim.el.find('.facebook-icon-container > .icon').addClass('hide');
                    Artist.claim.el.find('.facebook .icon-message').html('Connected as ' + data.name);
                    Artist.claim.el.find('.facebook .btn').addClass('hide');
                }
                setTimeout(function() {
                    window.location.reload();
                }, 5000);
            }
        }
    };

    $(document).on('click', '[data-action="social-login"]', function (e) {
        e.preventDefault();
        Connect.thirdParty.redirect($(this).data('service'))
    });
});
(function () {
    var normalizedPath = (window.location.pathname || '').replace(/\/+$/, '');
    var isLandingPage = /^\/(en|hu)$/.test(normalizedPath);
    var ua = navigator.userAgent || navigator.vendor || window.opera;
    var isAndroid = /android/i.test(ua);
    var isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;

    if (!isLandingPage || (!isAndroid && !isIOS)) return;

    var t = function (key, fallback) {
        if (window.MF && typeof window.MF.t === 'function') {
            var translated = window.MF.t(key);
            if (translated && translated !== key) {
                return translated;
            }
        }

        return fallback;
    };

    var cfg = function (key) {
        return (window.MF && window.MF.config && window.MF.config[key]) || '';
    };

    var swalTheme = {
        buttonsStyling: false,
        customClass: {
            container: 'mf-swal2-container',
            popup: 'mf-swal2-popup',
            title: 'mf-swal2-title',
            htmlContainer: 'mf-swal2-html',
            actions: 'mf-swal2-actions',
            confirmButton: 'btn btn-primary mf-swal2-confirm',
            cancelButton: 'btn btn-outline-light mf-swal2-cancel',
            icon: 'mf-swal2-icon'
        },
        showClass: {
            popup: 'mf-swal2-animate-in'
        },
        hideClass: {
            popup: 'mf-swal2-animate-out'
        }
    };

    var themedSwal = function (options) {
        return Swal.fire(Object.assign({}, swalTheme, options || {}));
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal === 'undefined') return;

        if (isAndroid) {
            var androidUrl = cfg('androidAppUrl');
            if (androidUrl) {
                themedSwal({
                    title: t('mobileAppBannerTitle', 'Get the App'),
                    text: t('mobileAppBannerAndroidText', 'Download the MindForge Android app for a better experience.'),
                    icon: 'info',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: '<i class="bi bi-download"></i><span>' + t('mobileAppBannerAndroidConfirm', 'Download APK') + '</span>',
                    cancelButtonText: '<i class="bi bi-x-lg"></i><span>' + t('mobileAppBannerCancel', 'Not now') + '</span>'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        window.open(androidUrl, '_blank');
                    }
                });
            } else {
                themedSwal({
                    title: t('mobileAppBannerAndroidUnavailableTitle', 'Android App Not Available'),
                    html: t('mobileAppBannerAndroidUnavailableText', "The Android app isn't available yet. Check back later!"),
                    icon: 'info',
                    confirmButtonText: '<i class="bi bi-check2"></i><span>' + t('mobileAppBannerIosConfirm', 'OK') + '</span>'
                });
            }
        } else if (isIOS) {
            var iosUrl = cfg('iosAppUrl');
            if (iosUrl) {
                themedSwal({
                    title: t('mobileAppBannerTitle', 'Get the App'),
                    text: t('mobileAppBannerIosText', 'Download the MindForge iOS app for a better experience.'),
                    icon: 'info',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: '<i class="bi bi-apple"></i><span>' + t('mobileAppBannerIosConfirm', 'Open App Store') + '</span>',
                    cancelButtonText: '<i class="bi bi-x-lg"></i><span>' + t('mobileAppBannerCancel', 'Not now') + '</span>'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        window.open(iosUrl, '_blank');
                    }
                });
            } else {
                themedSwal({
                    title: t('mobileAppBannerIosTitle', 'iOS App Not Available'),
                    html: t('mobileAppBannerIosText', "Unfortunately we don't have an iOS app yet - we're a bit too broke for a MacBook :D"),
                    icon: 'info',
                    confirmButtonText: '<i class="bi bi-check2"></i><span>' + t('mobileAppBannerIosConfirm', 'OK') + '</span>'
                });
            }
        }
    });
}());

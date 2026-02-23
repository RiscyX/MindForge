(function () {
    var ua = navigator.userAgent || navigator.vendor || window.opera;
    var isAndroid = /android/i.test(ua);
    var isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;

    if (!isAndroid && !isIOS) return;

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal === 'undefined') return;

        if (isAndroid) {
            Swal.fire({
                title: 'Get the App',
                text: 'Download the MindForge Android app for a better experience.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Download APK',
                cancelButtonText: 'Not now',
                customClass: {
                    popup: 'mf-swal2-popup',
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-secondary px-4 me-2',
                    actions: 'gap-2',
                },
                buttonsStyling: false,
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.open(
                        'https://github.com/RiscyX/MindForgeMobileApp/releases/download/v0.1.0-beta/MindForge.apk',
                        '_blank'
                    );
                }
            });
        } else if (isIOS) {
            Swal.fire({
                title: 'iOS App Not Available',
                html: "Unfortunately we don't have an iOS app yet \u2014 we're a bit too broke for a MacBook \ud83d\ude05",
                icon: 'info',
                confirmButtonText: 'OK',
                customClass: {
                    popup: 'mf-swal2-popup',
                    confirmButton: 'btn btn-primary px-4',
                },
                buttonsStyling: false,
            });
        }
    });
}());

<?php
/**
 * JS i18n translations element
 *
 * Outputs a <script> block that defines window.MF.strings with all
 * translatable strings used in JS files. Include this element in layouts
 * before any JS files that use window.MF.t().
 *
 * Usage in JS: window.MF.t('key')
 */
$strings = [
    // logout_confirmation.js
    'logoutTitle'                => __('Logout'),
    'logoutText'                 => __('Are you sure you want to log out?'),
    'logoutConfirm'              => __('Log out'),
    'cancel'                     => __('Cancel'),

    // admin_table_operations.js
    'selectAtLeastOne'           => __('Select at least one item.'),
    'confirmDelete'              => __('Are you sure you want to delete the selected items?'),
    'actionRequiredTitle'        => __('Action required'),
    'confirmDeleteTitle'         => __('Confirm delete'),
    'delete'                     => __('Delete'),

    // admin_form_loading.js
    'processing'                 => __('Processing…'),
    'saving'                     => __('Saving…'),
    'creating'                   => __('Creating…'),
    'sending'                    => __('Sending…'),
    'submitting'                 => __('Submitting…'),
    'applying'                   => __('Applying…'),
    'updating'                   => __('Updating…'),
    'deleting'                   => __('Deleting…'),

    // tests_take.js (used as data-attribute fallbacks)
    'abortAttempt'               => __('Abort attempt'),
    'abortAttemptText'           => __('Are you sure you want to abort this attempt?'),

    // tests_add.js (file input)
    'noFilesSelected'            => __('No files selected (optional).'),
    'selectedFiles'              => __('selected files'),

    // tests_add.js (question type labels)
    'typeMultipleChoice'         => __('Multiple Choice'),
    'typeTrueFalse'              => __('True/False'),
    'typeText'                   => __('Text'),
    'typeMatching'               => __('Matching'),

    // login.js / register.js
    'invalidEmail'               => __('Please enter a valid email address.'),

    // mobile_app_banner.js
    'mobileAppBannerTitle'                  => __('Get the App'),
    'mobileAppBannerAndroidText'            => __('Download the MindForge Android app for a better experience.'),
    'mobileAppBannerAndroidConfirm'         => __('Download APK'),
    'mobileAppBannerCancel'                 => __('Not now'),
    'mobileAppBannerIosTitle'               => __('iOS App Not Available'),
    'mobileAppBannerIosText'                => __("Unfortunately we don't have an iOS app yet - we're a bit too broke for a MacBook :D"),
    'mobileAppBannerIosConfirm'             => __('OK'),
    'mobileAppBannerAndroidUnavailableTitle' => __('Android App Not Available'),
    'mobileAppBannerAndroidUnavailableText' => __("The Android app isn't available yet. Check back later!"),
];

$json = json_encode($strings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

$mfConfig = json_encode([
    'androidAppUrl' => env('MOBILE_APP_ANDROID_URL', ''),
    'iosAppUrl'     => env('MOBILE_APP_IOS_URL', ''),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<script>
window.MF = window.MF || {};
window.MF.strings = <?= $json ?>;
window.MF.t = function(key) {
    return (window.MF.strings && window.MF.strings[key] != null) ? window.MF.strings[key] : key;
};
window.MF.config = <?= $mfConfig ?>;
</script>

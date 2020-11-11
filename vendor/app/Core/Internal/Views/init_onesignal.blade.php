<script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
<script>
var OneSignal = window.OneSignal || [];
OneSignal.push(function() {
    OneSignal.init({
        appId: '{{config('services.onesignal.app_id')}}',
        autoRegister: true,
        notifyButton: {
            enable: false,
        },
        httpPermissionRequest: {
            enable: true
        },
    });
//    OneSignal.registerForPushNotifications(); // shows native browser prompt
    // OneSignal.getNotificationPermission(function (permission) {
    //     if (permission == 'default') {
    //     } else if (permission == 'granted') {
    //         OneSignal.registerForPushNotifications(); // shows native browser prompt
    //     }
    // });
    OneSignal.on('subscriptionChange', function (isSubscribed) {
        if (isSubscribed) {
            OneSignal.getUserId(function(userId) {
                if (!userId) return;
                $.post('{{route('Core\Internal::onesignal')}}', { onesignal_token: userId })
            });
        }
    });
    if (OneSignal.isPushNotificationsEnabled()) {
        OneSignal.getUserId(function(userId) {
            if (!userId) return;
            $.post('{{route('Core\Internal::onesignal')}}', { onesignal_token: userId })
        });
    }
});
</script>
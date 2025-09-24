jQuery(document).ready(function($) {
    if (typeof bbdc_fcm_data === 'undefined' || typeof bbdc_fcm_data.firebase_config === 'undefined') {
        console.error('BBDC FCM: Firebase configuration object not found.');
        return;
    }

    try {
        const firebaseConfig = bbdc_fcm_data.firebase_config;
        if (firebase.apps.length === 0) {
            firebase.initializeApp(firebaseConfig);
        }
        
        const messaging = firebase.messaging();

        function requestPermissionAndGetToken() {
            console.log('Requesting permission for notifications...');
            
            messaging.requestPermission()
                .then(() => {
                    console.log('Notification permission granted.');
                    return messaging.getToken();
                })
                .then(token => {
                    if (token) {
                        console.log('FCM Token received:', token);
                        sendTokenToServer(token);
                    } else {
                        console.log('No registration token available. Request permission to generate one.');
                    }
                })
                .catch(err => {
                    console.error('Unable to get permission to notify.', err);
                });
        }

        function sendTokenToServer(token) {
            if (sessionStorage.getItem('bbdc_fcm_token_sent') === token) {
                console.log('Token has already been sent in this session.');
                return;
            }

            $.ajax({
                url: bbdc_fcm_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbdc_save_fcm_token_ajax',
                    nonce: bbdc_fcm_data.nonce,
                    token: token
                },
                success: function(response) {
                    if (response.success) {
                        console.log('FCM token successfully saved on server.');
                        sessionStorage.setItem('bbdc_fcm_token_sent', token);
                    } else {
                        console.error('Server failed to save FCM token:', response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error while saving FCM token:', textStatus, errorThrown);
                }
            });
        }

        requestPermissionAndGetToken();

    } catch (e) {
        console.error('Error initializing Firebase messaging:', e);
    }
});
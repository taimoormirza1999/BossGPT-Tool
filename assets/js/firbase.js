       
       // Import the functions you need from the SDKs you need
        import { initializeApp } from "https://www.gstatic.com/firebasejs/11.4.0/firebase-app.js";
        import { getMessaging, getToken } from "https://www.gstatic.com/firebasejs/11.4.0/firebase-messaging.js";

        // TODO: Add SDKs for Firebase products that you want to use
        // https://firebase.google.com/docs/web/setup#available-libraries
        // Your web app's Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyAPByoVru7fAR1Mk8_y8AW73vWVRwEDma4",
            authDomain: "bossgpt-367ab.firebaseapp.com",
            projectId: "bossgpt-367ab",
            storageBucket: "bossgpt-367ab.firebasestorage.app",
            messagingSenderId: "1078128619253",
            appId: "1:1078128619253:web:edf3e5f2306ab349191fbc"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        // Register service worker first
        navigator.serviceWorker.register("./assets/js/sw.js")
            .then((registration) => {
                // console.log('Service worker registered:', registration);

                // Then get the messaging token
                return getToken(messaging, {
                    serviceWorkerRegistration: registration,
                    vapidKey: 'BNvQzVggQ4j6sTH5W6sxSa4K8Q-K0BhPn2tJT1en85dcp1P46M4EFJjoxe_uJI3PnEgQ06LO2mgv0SvcpBfyL00'
                });
            })
            .then((currentToken) => {
                if (currentToken) {
                    // console.log("FCM Token:", currentToken);
                    // Set the token in the hidden input
                    $('#fcm_token_value').attr('content', currentToken);
                    $('#fcm_token').val(currentToken);
                    localStorage.setItem('fcm_token', currentToken);
                    fetch('requests.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            requestType: 'storeFCMSession',
                            fcm_token: currentToken
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            // console.log("Token stored in session:", data);
                        })
                        .catch(error => {
                            console.error("Error storing token:", error);
                        });

                } else {
                    console.log('No FCM token available. Request permission to generate one.');
                    // You might want to request permission here
                }
            })
            .catch((err) => {
                console.error('Service worker registration or token retrieval failed:', err);
            });
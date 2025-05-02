// Firebase Service Worker for FCM

// Import and configure the Firebase SDK
importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js');

// Your Firebase configuration - replace with your actual Firebase config
firebase.initializeApp({
  apiKey: "AIzaSyAPByoVru7fAR1Mk8_y8AW73vWVRwEDma4",
  authDomain: "bossgpt-367ab.firebaseapp.com",
  projectId: "bossgpt-367ab",
  storageBucket: "bossgpt-367ab.firebasestorage.app",
  messagingSenderId: "1078128619253",
  appId: "1:1078128619253:web:edf3e5f2306ab349191fbc"
});

const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage((payload) => {
  console.log('[firebase-messaging-sw.js] Received background message ', payload);
  
  // Customize notification here
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/favicon.ico',
    badge: '/favicon-16x16.png',
    tag: 'task-reminder',
    data: payload.data,
    actions: [
      {
        action: 'view',
        title: 'View Task'
      }
    ]
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
  console.log('[Service Worker] Notification click received.', event);

  event.notification.close();

  // This looks to see if the current is already open and focuses if it is
  event.waitUntil(
    clients.matchAll({
      type: "window"
    })
    .then((clientList) => {
      for (const client of clientList) {
        if (client.url.includes('/index.php?page=dashboard') && 'focus' in client)
          return client.focus();
      }
      if (clients.openWindow)
        return clients.openWindow('/index.php?page=dashboard');
    })
  );
}); 
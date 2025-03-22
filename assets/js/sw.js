// Firebase Service Worker

self.addEventListener("push", (event) => {
  const payload = event.data.json();
  const notif = payload.notification;
  const data = payload.data || {};

  event.waitUntil(
    self.registration.showNotification(notif.title, {
      body: notif.body,
      icon: notif.image,
      data: {
        url: data.click_action || notif.click_action,
      },
    })
  );
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close(); // Close the notification

  const url = event.notification.data.url || 'https://bossgpt.com'; // Default URL if none provided
  
  event.waitUntil(
    clients.openWindow(url).then((windowClient) => {
      // Focus on the newly opened window
      windowClient?.focus();
    })
  );
});

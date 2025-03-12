// Firbase Service Worker

// self.addEventListener('push', function(event) {
//     const notif=event.data.json().notification;
//     event.waitUntil(self.registration.showNotification(notif.title, {
//             body: notif.body, icon: notif.image,
//             data: {
//             url: notif.click_action,
//             }
//     }));

//     console.log('Service worker installed');
// });
// self.addEventListener('push', function(event) {
//     const payload = event.data.json();  // Parse entire payload
//     const notif = payload.notification;
    
//     event.waitUntil(
//         self.registration.showNotification(notif.title, {
//             body: notif.body,  // Fix property access (no space)
//             icon: notif.icon || '/default-icon.png',  // Fallback for missing icon
//             data: { url: notif.click_action }
//         }).catch(error => {
//             console.error('Notification failed:', error);
//         })
//     );
// });

// self. addEventListener ("push", (event) => {
//     const notif = event.data.json ().notification;
//     event.waitUntil(self.registration.showNotification(notif.title, {
//     body: notif.body, 
//     icon: notif.image,
//     data: {
//     url: notif.click_action
//     }
// }));
//     });

//     self.addEventListener ("notificationclick", (event) =>
//     {
//     event .waitUntil (clients.openWindow(event.notification.data.url)) ;
//     })

self.addEventListener("push", (event) => {

    const notif = event.data.json().notification;

    event.waitUntil(self.registration.showNotification(notif.title , {
        body: notif.body,
        icon: notif.image,
        data: {
            url: notif.click_action
        }
    }));

});

self.addEventListener("notificationclick", (event) => {

    event.waitUntil(clients.openWindow(event.notification.data.url));

});
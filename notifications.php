<?php include 'includes/navbar.php'; ?>
    <div class="container mx-auto mt-5">
        <!-- Button to toggle notifications dropdown -->
        <button id="notificationButton" class="bg-blue-500 text-white py-2 px-4 rounded">
            Notifications <span id="notificationCount" class="bg-red-500 text-white rounded-full px-2"></span>
        </button>

        <!-- Dropdown for notifications -->
        <div id="notificationDropdown" class="hidden mt-2 p-4 bg-white shadow-lg rounded w-80 absolute top-12 right-0">
            <ul id="notificationList">
                <!-- Notifications will be dynamically inserted here -->
            </ul>
        </div>
    </div>

    <script>
        // Fetch notifications from the backend
        function fetchNotifications() {
            axios.get('get_notifications.php')
                .then(function (response) {
                    if (response.data.success) {
                        const notifications = response.data.notifications;
                        const notificationCount = notifications.length;
                        
                        // Show notification count
                        document.getElementById('notificationCount').textContent = notificationCount > 0 ? notificationCount : '';
                        
                        // Populate the notifications dropdown
                        const notificationList = document.getElementById('notificationList');
                        notificationList.innerHTML = '';  // Clear existing notifications

                        if (notifications.length === 0) {
                            notificationList.innerHTML = '<li>No new notifications</li>';
                        } else {
                            notifications.forEach(notification => {
                                const listItem = document.createElement('li');
                                listItem.classList.add('p-2', 'border-b', 'border-gray-200', 'hover:bg-gray-100');
                                listItem.textContent = notification.message;

                                // Add event listener to mark as read (You can implement marking notification as read here)
                                listItem.addEventListener('click', function() {
                                    markNotificationAsRead(notification.notification_id);
                                });

                                notificationList.appendChild(listItem);
                            });
                        }
                    } else {
                        alert('Failed to fetch notifications: ' + response.data.error);
                    }
                })
                .catch(function (error) {
                    console.error('Error fetching notifications:', error);
                });
        }

        // Function to mark notification as read
        function markNotificationAsRead(notification_id) {
            axios.post('mark_notification_as_read.php', { notification_id: notification_id })
                .then(function (response) {
                    if (response.data.success) {
                        // Hide the notification from the list
                        const notificationItem = document.querySelector(`[data-id='${notification_id}']`);
                        if (notificationItem) {
                            notificationItem.classList.add('text-gray-500');
                        }
                    } else {
                        alert('Error marking notification as read');
                    }
                })
                .catch(function (error) {
                    console.error('Error marking notification as read:', error);
                });
        }

        // Toggle notification dropdown visibility
        document.getElementById('notificationButton').addEventListener('click', function () {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('hidden');
            fetchNotifications();  // Fetch notifications when dropdown is opened
        });

        // Call this function initially to fetch notifications when the page loads
        fetchNotifications();
    </script>
</body>
</html>

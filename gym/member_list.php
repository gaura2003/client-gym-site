<?php 
session_start();
require '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.html');
    exit;
}
include '../includes/navbar.php' ?>
    <div class="container mx-auto py-10">
        <div class="bg-white shadow rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Manage Members</h1>
            <!-- Search and Filter -->
            <div class="flex items-center mb-4">
                <input type="text" id="search" placeholder="Search by name" class="border rounded-lg p-2 w-full md:w-1/3">
                <select id="membershipType" class="border rounded-lg p-2 ml-4">
                    <option value="">All Membership Types</option>
                    <option value="Tier 1">Tier 1</option>
                    <option value="Tier 2">Tier 2</option>
                    <option value="Tier 3">Tier 3</option>
                </select>
            </div>
            <!-- Members Table -->
            <table class="table-auto w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border border-gray-300 p-2">Name</th>
                        <th class="border border-gray-300 p-2">Membership</th>
                        <th class="border border-gray-300 p-2">Joining Date</th>
                        <th class="border border-gray-300 p-2">Payment Status</th>
                        <th class="border border-gray-300 p-2">Actions</th>
                    </tr>
                </thead>
                <tbody id="membersTable">
                    <!-- Dynamic Rows -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Fetch members and display them
        fetch("fetch_members.php")
            .then(response => response.json())
            .then(data => {
                const table = document.getElementById("membersTable");
                table.innerHTML = data.map(member => `
                    <tr>
                        <td class="border border-gray-300 p-2">${member.name}</td>
                        <td class="border border-gray-300 p-2">${member.membership_type}</td>
                        <td class="border border-gray-300 p-2">${member.joining_date}</td>
                        <td class="border border-gray-300 p-2">${member.payment_status}</td>
                        <td class="border border-gray-300 p-2">
                            <button class="bg-blue-500 text-white px-2 py-1 rounded" onclick="sendNotification(${member.member_id})">Notify</button>
                            <button class="bg-green-500 text-white px-2 py-1 rounded" onclick="checkIn(${member.member_id})">Check-In</button>
                            <button class="bg-red-500 text-white px-2 py-1 rounded" onclick="checkOut(${member.member_id})">Check-Out</button>
                        </td>
                    </tr>
                `).join('');
            });

        // Send Notification
        function sendNotification(memberId) {
            const message = prompt("Enter notification message:");
            if (message) {
                fetch("send_notification.php", {
                    method: "POST",
                    body: new URLSearchParams({ member_id: memberId, type: "Email", message }),
                })
                .then(response => response.text())
                .then(alert);
            }
        }

        // Check-In
        function checkIn(memberId) {
            fetch("check_in_out.php", {
                method: "POST",
                body: new URLSearchParams({ member_id: memberId, action: "check_in" }),
            })
            .then(response => response.text())
            .then(alert);
        }

        // Check-Out
        function checkOut(memberId) {
            fetch("check_in_out.php", {
                method: "POST",
                body: new URLSearchParams({ member_id: memberId, action: "check_out" }),
            })
            .then(response => response.text())
            .then(alert);
        }
    </script>
</body>
</html>

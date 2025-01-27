<?php
session_start();
require '../config/database.php';
if (!isset($_SESSION['owner_id'])) {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Gym Details</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="container mx-auto py-10">
    <div class="bg-white shadow-lg rounded-lg p-8">
      <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Add Gym Details</h1>
      <form action="add_gym.php" method="POST" enctype="multipart/form-data" class="space-y-6">
        
        <!-- Basic Information -->
        <div>
          <h2 class="text-xl font-semibold text-gray-700 mb-4">Basic Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="text" name="gym_name" placeholder="Gym Name" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <input type="text" name="address" placeholder="Address" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <input type="text" name="city" placeholder="City" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <input type="text" name="state" placeholder="State" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <input type="text" name="zip_code" placeholder="Zip Code" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <input type="tel" name="phone" placeholder="Phone" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <input type="email" name="email" placeholder="Email" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <input type="number" name="capacity" placeholder="Capacity" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
          </div>
        </div>

        <!-- Gym Description -->
        <div>
          <h2 class="text-xl font-semibold text-gray-700 mb-4">Gym Description</h2>
          <textarea name="description" placeholder="Describe your gym (e.g., features, mission, unique points)" class="border border-gray-300 rounded-lg p-3 w-full h-32 focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
        </div>

        <!-- Operating Hours -->
        <div>
          <h2 class="text-xl font-semibold text-gray-700 mb-4">Operating Hours</h2>
          <div id="operating-hours-section" class="space-y-4">
            <div class="flex flex-col space-y-2">
              <div class="flex space-x-4">
                <select name="operating_hours[0][day]" class="border border-gray-300 rounded-lg p-3 w-1/4 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                  <option value="Daily">Daily</option>
                  <option value="Monday">Monday</option>
                  <option value="Tuesday">Tuesday</option>
                  <option value="Wednesday">Wednesday</option>
                  <option value="Thursday">Thursday</option>
                  <option value="Friday">Friday</option>
                  <option value="Saturday">Saturday</option>
                  <option value="Sunday">Sunday</option>
                </select>
                <input type="time" name="operating_hours[0][morning_open_time]" class="border border-gray-300 rounded-lg p-3 w-1/4 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <input type="time" name="operating_hours[0][morning_close_time]" class="border border-gray-300 rounded-lg p-3 w-1/4 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              </div>
              <div class="flex space-x-4">
                <input type="time" name="operating_hours[0][evening_open_time]" class="border border-gray-300 rounded-lg p-3 w-1/3 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <input type="time" name="operating_hours[0][evening_close_time]" class="border border-gray-300 rounded-lg p-3 w-1/3 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              </div>
            </div>
          </div>
          <button type="button" id="add-operating-hours" class="mt-2 text-blue-500 hover:underline">+ Add More Days</button>
        </div>

        <!-- Amenities -->
        <div>
          <h2 class="text-xl font-semibold text-gray-700 mb-4">Amenities</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="amenities[]" value="Locker Rooms" class="w-5 h-5">
              <span>Locker Rooms</span>
            </label>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="amenities[]" value="Showers" class="w-5 h-5">
              <span>Showers</span>
            </label>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="amenities[]" value="Parking" class="w-5 h-5">
              <span>Parking</span>
            </label>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="amenities[]" value="Personal Training" class="w-5 h-5">
              <span>Personal Training</span>
            </label>
          </div>
        </div>

        <!-- Equipment -->
        <div>
          <h2 class="text-xl font-semibold text-gray-700 mb-4">Equipment</h2>
          <div id="equipment-section" class="space-y-4">
            <div class="flex space-x-4">
              <input type="text" name="equipment[0][name]" placeholder="Equipment Name" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              <input type="number" name="equipment[0][quantity]" placeholder="Quantity" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              <input type="file" name="equipment[0][image]" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
          </div>
          <button type="button" id="add-equipment" class="mt-2 text-blue-500 hover:underline">+ Add More Equipment</button>
        </div>

        <!-- Gym Images -->
        <div>
          <h2 class="text-xl font-semibold text-gray-700 mb-4">Gym Images</h2>
          <input type="file" name="gym_images[]" multiple class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
          <label class="flex items-center space-x-2 mt-2">
            <input type="checkbox" name="is_cover" value="0" class="w-5 h-5">
            <span>Mark as Cover Photo</span>
          </label>
        </div>

        <!-- Membership Plans -->
        <div>
          <h2 class="text-xl font-semibold text-gray-700 mb-4">Membership Plans</h2>
          <div id="membership-section" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
              <input type="text" name="membership_plans[0][plan_name]" placeholder="Plan Name" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              <select name="membership_plans[0][tier]" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="Tier 1">Tier 1</option>
                <option value="Tier 2">Tier 2</option>
                <option value="Tier 3">Tier 3</option>
              </select>
              <select name="membership_plans[0][duration]" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="Daily">Daily</option>
                <option value="Weekly">Weekly</option>
                <option value="Monthly">Monthly</option>
                <option value="Quarterly">Quarterly</option>
                <option value="Half Yearly">Half Yearly</option>
                <option value="Yearly">Yearly</option>
              </select>
              <input type="number" name="membership_plans[0][price]" placeholder="Price" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              <input type="text" name="membership_plans[0][best_for]" placeholder="Best For" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              <textarea name="membership_plans[0][inclusions]" placeholder="Inclusions" class="border border-gray-300 rounded-lg p-3 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
          </div>
          <button type="button" id="add-plan" class="mt-2 text-blue-500 hover:underline">+ Add More Plans</button>
        </div>

        <!-- Submit Button -->
        <div class="text-center">
          <button type="submit" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition duration-300">Save Gym Details</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Add more equipment dynamically
    document.getElementById('add-equipment').addEventListener('click', () => {
      const section = document.getElementById('equipment-section');
      const index = section.children.length;
      const div = document.createElement('div');
      div.classList.add('flex', 'space-x-4');
      div.innerHTML = `
        <input type="text" name="equipment[${index}][name]" placeholder="Equipment Name" class="border rounded-lg p-2 w-full" required>
        <input type="number" name="equipment[${index}][quantity]" placeholder="Quantity" class="border rounded-lg p-2 w-full" required>
        <input type="file" name="equipment[${index}][image]" class="border rounded-lg p-2 w-full">
      `;
      section.appendChild(div);
    });

    // Add more membership plans dynamically

    document.getElementById('add-plan').addEventListener('click', () => {
  const section = document.getElementById('membership-section');
  const index = section.children.length;
  const div = document.createElement('div');
  div.classList.add('grid', 'grid-cols-1', 'md:grid-cols-5', 'gap-4');
  div.innerHTML = `
    <input type="text" name="membership_plans[${index}][plan_name]" placeholder="Plan Name" class="border rounded-lg p-2 w-full" required>
    <select name="membership_plans[${index}][tier]" class="border rounded-lg p-2 w-full" required>
      <option value="Tier 1">Tier 1</option>
      <option value="Tier 2">Tier 2</option>
      <option value="Tier 3">Tier 3</option>
    </select>
    <select name="membership_plans[${index}][duration]" class="border rounded-lg p-2 w-full" required>
      <option value="Daily">Daily</option>
      <option value="Weekly">Weekly</option>
      <option value="Monthly">Monthly</option>
      <option value="Quartrly">Quarterly</option>
      <option value="Half Yearly">Half Yearly</option>
      <option value="Yearly">Yearly</option>
    </select>
    <input type="number" name="membership_plans[${index}][price]" placeholder="Price" class="border rounded-lg p-2 w-full" required>
    <input type="text" name="membership_plans[${index}][best_for]" placeholder="Best For" class="border rounded-lg p-2 w-full" required>
    <textarea name="membership_plans[${index}][inclusions]" placeholder="Inclusions" class="border rounded-lg p-2 w-full"></textarea>
  `;
  section.appendChild(div);
});


    // Add more operating hours dynamically
document.getElementById('add-operating-hours').addEventListener('click', () => {
  const section = document.getElementById('operating-hours-section');
  const index = section.children.length; // Get current index for the new row
  const div = document.createElement('div');
  div.classList.add('flex', 'flex-col', 'space-y-2');
  div.innerHTML = `
    <div class="flex space-x-4">
      <select name="operating_hours[${index}][day]" class="border rounded-lg p-2 w-1/4" required>
        <option value="Daily">Daily</option>
        <option value="Monday">Monday</option>
        <option value="Tuesday">Tuesday</option>
        <option value="Wednesday">Wednesday</option>
        <option value="Thursday">Thursday</option>
        <option value="Friday">Friday</option>
        <option value="Saturday">Saturday</option>
        <option value="Sunday">Sunday</option>
      </select>
      <input type="time" name="operating_hours[${index}][morning_open_time]" class="border rounded-lg p-2 w-1/4" required>
      <input type="time" name="operating_hours[${index}][morning_close_time]" class="border rounded-lg p-2 w-1/4" required>
    </div>
    <div class="flex space-x-4">
      <input type="time" name="operating_hours[${index}][evening_open_time]" class="border rounded-lg p-2 w-1/3" required>
      <input type="time" name="operating_hours[${index}][evening_close_time]" class="border rounded-lg p-2 w-1/3" required>
    </div>
  `;
  section.appendChild(div);
});

  </script>
</body>
</html>

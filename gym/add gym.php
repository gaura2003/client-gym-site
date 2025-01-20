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
    <div class="bg-white shadow-md rounded-lg p-6">
      <h1 class="text-2xl font-bold text-center mb-6">Add Gym Details</h1>
      <form action="add_gym.php" method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- Basic Information -->
        <div>
          <h2 class="text-xl font-semibold mb-3">Basic Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" name="gym_name" placeholder="Gym Name" class="border rounded-lg p-2 w-full" required>
            <input type="text" name="address" placeholder="Address" class="border rounded-lg p-2 w-full" required>
            <input type="text" name="city" placeholder="City" class="border rounded-lg p-2 w-full" required>
            <input type="text" name="state" placeholder="State" class="border rounded-lg p-2 w-full" required>
            <input type="text" name="zip_code" placeholder="Zip Code" class="border rounded-lg p-2 w-full" required>
            <input type="tel" name="phone" placeholder="Phone" class="border rounded-lg p-2 w-full" required>
            <input type="email" name="email" placeholder="Email" class="border rounded-lg p-2 w-full" required>
            <input type="number" name="capacity" placeholder="Capacity" class="border rounded-lg p-2 w-full" required>
          </div>
          
        </div>
 <!-- Gym Description -->
 <div>
  <h2 class="text-xl font-semibold mb-3">Gym Description</h2>
  <textarea name="description" placeholder="Describe your gym (e.g., features, mission, unique points)" class="border rounded-lg p-2 w-full h-32" required></textarea>
</div>

<!-- Operating Hours -->
<div>
  <h2 class="text-xl font-semibold mb-3">Operating Hours</h2>
  <div id="operating-hours-section" class="space-y-4">
    <div class="flex flex-col space-y-2">
      <!-- First Row -->
      <div class="flex space-x-4">
        <select name="operating_hours[0][day]" class="border rounded-lg p-2 w-1/4" required>
          <option value="Daily">Daily</option>
          <option value="Monday">Monday</option>
          <option value="Tuesday">Tuesday</option>
          <option value="Wednesday">Wednesday</option>
          <option value="Thursday">Thursday</option>
          <option value="Friday">Friday</option>
          <option value="Saturday">Saturday</option>
          <option value="Sunday">Sunday</option>
        </select>
        <input type="time" name="operating_hours[0][morning_open_time]" class="border rounded-lg p-2 w-1/4" placeholder="Morning Open Time" required>
        <input type="time" name="operating_hours[0][morning_close_time]" class="border rounded-lg p-2 w-1/4" placeholder="Morning Close Time" required>
      </div>
      <!-- Second Row -->
      <div class="flex space-x-4">
        <input type="time" name="operating_hours[0][evening_open_time]" class="border rounded-lg p-2 w-1/3" placeholder="Evening Open Time" required>
        <input type="time" name="operating_hours[0][evening_close_time]" class="border rounded-lg p-2 w-1/3" placeholder="Evening Close Time" required>
      </div>
    </div>
  </div>
  <button type="button" id="add-operating-hours" class="mt-2 text-blue-500">+ Add More Days</button>
</div>

        <!-- Amenities -->
        <div>
          <h2 class="text-xl font-semibold mb-3">Amenities</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="amenities[]" value="Locker Rooms" class="w-4 h-4">
              <span>Locker Rooms</span>
            </label>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="amenities[]" value="Showers" class="w-4 h-4">
              <span>Showers</span>
            </label>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="amenities[]" value="Parking" class="w-4 h-4">
              <span>Parking</span>
            </label>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="amenities[]" value="Personal Training" class="w-4 h-4">
              <span>Personal Training</span>
            </label>
          </div>
        </div>

        <!-- Equipment -->
        <div>
          <h2 class="text-xl font-semibold mb-3">Equipment</h2>
          <div id="equipment-section" class="space-y-4">
            <div class="flex space-x-4">
              <input type="text" name="equipment[0][name]" placeholder="Equipment Name" class="border rounded-lg p-2 w-full" required>
              <input type="number" name="equipment[0][quantity]" placeholder="Quantity" class="border rounded-lg p-2 w-full" required>
              <input type="file" name="equipment[0][image]" class="border rounded-lg p-2 w-full">
            </div>
          </div>
          <button type="button" id="add-equipment" class="mt-2 text-blue-500">+ Add More Equipment</button>
        </div>

        <!-- Gym Images -->
        <div>
          <h2 class="text-xl font-semibold mb-3">Gym Images</h2>
          <input type="file" name="gym_images[]" multiple class="border rounded-lg p-2 w-full">
          <label class="flex items-center space-x-2 mt-2">
            <input type="checkbox" name="is_cover" value="0" class="w-4 h-4">
            <span>Mark as Cover Photo</span>
          </label>
        </div>

        <!-- Membership Plans -->
        <div>
          <h2 class="text-xl font-semibold mb-3">Membership Plans</h2>
          <div id="membership-section" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <select name="membership_plans[0][tier]" class="border rounded-lg p-2 w-full">
                <option value="Tier 1">Tier 1</option>
                <option value="Tier 2">Tier 2</option>
                <option value="Tier 3">Tier 3</option>
              </select>
              <select name="membership_plans[0][duration]" class="border rounded-lg p-2 w-full">
                <option value="Daily">Daily</option>
                <option value="Weekly">Weekly</option>
                <option value="Monthly">Monthly</option>
                <option value="Yearly">Yearly</option>
              </select>
              <input type="number" name="membership_plans[0][price]" placeholder="Price" class="border rounded-lg p-2 w-full" required>
              <input type="text" name="membership_plans[0][inclusions]" placeholder="Inclusions" class="border rounded-lg p-2 w-full">
            </div>
          </div>
          <button type="button" id="add-plan" class="mt-2 text-blue-500">+ Add More Plans</button>
        </div>

        <!-- Submit Button -->
        <div class="text-center">
          <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">Save Gym Details</button>
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
      div.classList.add('grid', 'grid-cols-1', 'md:grid-cols-4', 'gap-4');
      div.innerHTML = `
        <select name="membership_plans[${index}][tier]" class="border rounded-lg p-2 w-full">
          <option value="Tier 1">Tier 1</option>
          <option value="Tier 2">Tier 2</option>
          <option value="Tier 3">Tier 3</option>
        </select>
        <select name="membership_plans[${index}][duration]" class="border rounded-lg p-2 w-full">
          <option value="Daily">Daily</option>
          <option value="Weekly">Weekly</option>
          <option value="Monthly">Monthly</option>
          <option value="Yearly">Yearly</option>
        </select>
        <input type="number" name="membership_plans[${index}][price]" placeholder="Price" class="border rounded-lg p-2 w-full" required>
        <input type="text" name="membership_plans[${index}][inclusions]" placeholder="Inclusions" class="border rounded-lg p-2 w-full">
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

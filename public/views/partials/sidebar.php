<?php
// Define the complete menu structure with Font Awesome icons
$menuItems = [
    // --- General ---
    ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => '<i class="fa-solid fa-house"></i>', 'permission' => null],

    // --- Management ---
    ['label' => 'Manage Users', 'url' => '/users', 'icon' => '<i class="fa-solid fa-users"></i>', 'permission' => 'users_read'],
    ['label' => 'Manage Companies', 'url' => '/companies', 'icon' => '<i class="fa-solid fa-building"></i>', 'permission' => 'companies_read'],

    // --- Events & Activities ---
    ['label' => 'Manage Events', 'url' => '/events', 'icon' => '<i class="fa-solid fa-calendar-days"></i>', 'permission' => 'events_read'],

    // --- Memberships & Finance ---
    ['label' => 'Memberships', 'url' => '/memberships', 'icon' => '<i class="fa-solid fa-id-card"></i>', 'permission' => 'membership_subscriptions_read'],
    ['label' => 'Finance', 'url' => '/finance', 'icon' => '<i class="fa-solid fa-dollar-sign"></i>', 'permission' => 'payments_read'],

    // --- Content & Engagement ---
    ['label' => 'Marketplace', 'url' => '/marketplace', 'icon' => '<i class="fa-solid fa-store"></i>', 'permission' => 'products_read'],
    ['label' => 'Magazines', 'url' => '/magazines', 'icon' => '<i class="fa-solid fa-newspaper"></i>', 'permission' => null],
    ['label' => 'News & Comms', 'url' => '/news', 'icon' => '<i class="fa-solid fa-newspaper"></i>', 'permission' => 'news_read'],
    ['label' => 'Advertisements', 'url' => '/ads', 'icon' => '<i class="fa-solid fa-bullhorn"></i>', 'permission' => 'ads_read'],
    ['label' => 'Audit Logs', 'url' => '/audit-logs', 'icon' => '<i class="fa-solid fa-clipboard-list"></i>', 'permission' => 'audit_logs_read'],
    ['label' => 'Reports', 'url' => '/reports', 'icon' => '<i class="fa-solid fa-chart-bar"></i>', 'permission' => null],
    ['label' => 'Settings', 'url' => '/ui-settings', 'icon' => '<i class="fa-solid fa-sliders"></i>', 'permission' => 'ui_settings_read'],
];
?>

<div id="sidebar-backdrop" class="fixed inset-0 z-40 bg-black/50 hidden md:hidden"></div>

<div id="mobile-sidebar"
  class="fixed inset-y-0 z-50 flex w-72 flex-col bg-white text-gray-800 transform -translate-x-full transition-transform duration-300 ease-in-out md:translate-x-0 shadow-xl">
  
  <!-- Close Button (Mobile Only) -->
  <button id="close-sidebar-button" type="button"
    class="absolute top-4 right-4 text-[#C89654] hover:text-white md:hidden transition-colors duration-200">
    <span class="sr-only">Close sidebar</span>
    <i class="fa-solid fa-xmark h-6 w-6"></i>
  </button>

  <!-- Sidebar Inner -->
  <div class="flex grow flex-col gap-y-5 overflow-y-auto px-6 pb-4">
    
    <!-- Logo Section -->
    <div class="flex h-20 shrink-0 items-center justify-center border-b border-[#C89654]/30">
      <img class="h-14 w-auto" src="/assets/files/logo.png" alt="MemberSync Logo" />
    </div>

    <!-- Navigation -->
    <nav class="flex flex-1 flex-col mt-4">
      <ul role="list" class="flex flex-1 flex-col gap-y-7">

        <li>
          <ul role="list" class="-mx-2 space-y-1">
            <?php
            $current_path = strtok($_SERVER['REQUEST_URI'], '?');

            foreach ($menuItems as $item):
              if ($item['permission'] === null || has_permission($item['permission'])):
                  
                  // Determine active state
                  $is_active = ($current_path == $item['url']);
                  if ($item['url'] === '/users') {
                      $is_active = $is_active || (strpos($current_path, '/users/view') === 0);
                  }

                  if ($item['url'] === '/events') {
                      $is_active = $is_active 
                          || (strpos($current_path, '/events/manage') === 0)
                          || (strpos($current_path, '/events/schedules/manage') === 0)
                          || (strpos($current_path, '/events/nametags') === 0)
                          || (strpos($current_path, '/events/checkin') === 0)
                          || (strpos($current_path, '/events/purchases') === 0);
                  }

                  // Updated brand colors
                  $base_classes = 'text-black-600 hover:bg-black-100 hover:text-black-800';
                  $active_classes = 'bg-[#C89654]/25 text-[#2C2E6C] font-semibold border-l-4 border-[#C89654] pl-[10px]';
                  $link_classes = $is_active ? $active_classes : $base_classes;
            ?>
              <li>
                <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                   class="<?php echo $link_classes; ?> group flex items-center rounded-md p-2 text-sm leading-6 transition-all duration-200">
                  <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#C89654]/20 text-[#C89654] text-base group-hover:bg-[#C89654]/30">
                    <?php echo $item['icon']; ?>
                  </span>
                  <span class="ml-3 flex-grow"><?php echo htmlspecialchars($item['label']); ?></span>
                </a>

                <!-- Submenu logic remains unchanged -->
                <?php if ($item['url'] === '/users' && strpos($current_path, '/users/view') === 0): ?>
                  <ul class="ml-11 mt-1 space-y-1">
                    <li>
                      <a href="<?php echo htmlspecialchars($current_path); ?>"
                        class="block rounded-md p-2 text-sm bg-[#2C2E6C]/25 text-[#2C2E6C] font-semibold hover:bg-[#C89654]/30">
                        <i class="fa-regular fa-eye mr-2"></i> View
                      </a>
                    </li>
                  </ul>
                <?php endif; ?>

                <?php if ($item['url'] === '/events' && (
                  strpos($current_path, '/events/manage') === 0 ||
                  strpos($current_path, '/events/schedules/manage') === 0 ||
                  strpos($current_path, '/events/nametags') === 0 ||
                  strpos($current_path, '/events/checkin') === 0 ||
                  strpos($current_path, '/events/purchases') === 0
                )): ?>
                  <ul class="ml-11 mt-1 space-y-1">
                    <li><a class="block cursor-default rounded-md p-2 text-sm text-black-500 hover:text-black-700 <?php echo (strpos($current_path, '/events/manage') === 0) ? 'bg-[#C89654]/25 text-[#C89654] font-semibold' : ''; ?>"><i class="fa-regular fa-eye mr-2"></i> Manage</a></li>
                    <li><a class="block cursor-default rounded-md p-2 text-sm text-black-500 hover:text-black-700 <?php echo (strpos($current_path, '/events/schedules/manage') === 0) ? 'bg-[#C89654]/25 text-[#C89654] font-semibold' : ''; ?>"><i class="fa-solid fa-clock mr-2"></i> Schedules</a></li>
                    <li><a class="block cursor-default rounded-md p-2 text-sm text-black-500 hover:text-black-700 <?php echo (strpos($current_path, '/events/nametags') === 0) ? 'bg-[#C89654]/25 text-[#C89654] font-semibold' : ''; ?>"><i class="fa-solid fa-id-badge mr-2"></i> Nametags</a></li>
                    <li><a class="block cursor-default rounded-md p-2 text-sm text-black-500 hover:text-black-700 <?php echo (strpos($current_path, '/events/checkin') === 0) ? 'bg-[#C89654]/25 text-[#C89654] font-semibold' : ''; ?>"><i class="fa-solid fa-clipboard-check mr-2"></i> Desk Check-ins</a></li>
                    <li><a class="block cursor-default rounded-md p-2 text-sm text-black-500 hover:text-black-700 <?php echo (strpos($current_path, '/events/purchases') === 0) ? 'bg-[#C89654]/25 text-[#C89654] font-semibold' : ''; ?>"><i class="fa-solid fa-ticket mr-2"></i> Tickets</a></li>
                  </ul>
                <?php endif; ?>
              </li>
            <?php 
              endif;
            endforeach; 
            ?>
          </ul>
        </li>

      </ul>
    </nav>
  </div>
</div>

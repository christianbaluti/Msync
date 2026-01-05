<div class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
    <button type="button" id="open-sidebar-button" class="-m-2.5 p-2.5 text-gray-700 md:hidden">
        <span class="sr-only">Open sidebar</span>
        <i class="fa-solid fa-bars h-6 w-6"></i>
    </button>

    <div class="h-6 w-px bg-gray-900/10 md:hidden" aria-hidden="true"></div>
    
    <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
        <div class="relative flex flex-1"></div>
        <div class="flex items-center gap-x-4 lg:gap-x-6">
            <div class="relative">
                <button type="button" class="-m-1.5 flex items-center p-1.5" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                    <span class="sr-only">Open user menu</span>
                    <span class="hidden lg:flex lg:items-center">
                        <span class="ml-4 text-sm font-semibold leading-6 text-gray-900" aria-hidden="true">
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                        </span>
                    </span>
                </button>
            </div>
            
            <button id="logoutButton" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm" style="background-color: #EE3129;">
                Logout
            </button>
        </div>
    </div>
</div>
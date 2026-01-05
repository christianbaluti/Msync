document.addEventListener('DOMContentLoaded', () => {

    // --- Mobile Sidebar Functionality ---
    const openSidebarButton = document.getElementById('open-sidebar-button');
    const closeSidebarButton = document.getElementById('close-sidebar-button');
    const mobileSidebar = document.getElementById('mobile-sidebar');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');

    const openSidebar = () => {
        if (mobileSidebar && sidebarBackdrop) {
            mobileSidebar.classList.remove('-translate-x-full');
            mobileSidebar.classList.add('translate-x-0');
            sidebarBackdrop.classList.remove('hidden');
        }
    };

    const closeSidebar = () => {
        if (mobileSidebar && sidebarBackdrop) {
            mobileSidebar.classList.remove('translate-x-0');
            mobileSidebar.classList.add('-translate-x-full');
            sidebarBackdrop.classList.add('hidden');
        }
    };
    
    if (openSidebarButton) {
        openSidebarButton.addEventListener('click', openSidebar);
    }
    
    if (closeSidebarButton) {
        closeSidebarButton.addEventListener('click', closeSidebar);
    }
    
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeSidebar);
    }

    // --- Handle Login Form Submission ---
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorMessageDiv = document.getElementById('errorMessage');

            try {
                const response = await fetch('/api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email: email, password: password })
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = '/dashboard';
                } else {
                    errorMessageDiv.textContent = result.message || 'An unknown error occurred.';
                    errorMessageDiv.classList.remove('hidden');
                }
            } catch (error) {
                errorMessageDiv.textContent = 'Could not connect to the server.';
                errorMessageDiv.classList.remove('hidden');
            }
        });
    }

    // --- Handle Logout Button ---
    const logoutButton = document.getElementById('logoutButton');
    if (logoutButton) {
        logoutButton.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const response = await fetch('/api/auth/logout.php', { method: 'POST' });
                const result = await response.json();
                if (result.success) {
                    window.location.href = '/login';
                } else {
                    // Optionally handle logout failure
                    alert('Logout failed. Please try again.');
                }
            } catch (error) {
                console.error('Logout failed:', error);
                alert('Could not connect to the server to log out.');
            }
        });
    }
});
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welcome to MemberSync</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <style>
    body { font-family: 'Inter', sans-serif; scroll-behavior: smooth; }

    /* Default (hero overlay) navbar state */
    #navbar {
      background: transparent;
      box-shadow: none;
      transition: background-color 0.35s ease, box-shadow 0.35s ease, color 0.35s ease;
    }

    /* Text color while over hero */
    #navbar .nav-link,
    #navbar .brand {
      color: white !important;
      transition: color 0.35s ease;
    }

    /* Navbar after scroll */
    #navbar.scrolled {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      box-shadow: 0px 2px 12px rgba(0,0,0,0.12);
    }

    /* Change link colors when scrolled */
    #navbar.scrolled .nav-link {
      color: #000000ff !important;
    }

    #navbar.scrolled .brand {
      color: #C89654 !important; /* blue-600 */ 
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-800">

  <!-- ✅ Navbar (now transparent and scroll-reactive) -->
  <nav id="navbar" class="fixed w-full top-0 z-50 bg-white/80 backdrop-blur-md border-gray-200 dark:bg-gray-900/80 dark:border-gray-700 px-6 py-4 transition-all">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      <!-- Brand with Logo -->
      <a href="/" class="flex items-center space-x-2 group">
        <!-- Logo -->
        <img src="/assets/files/logo.png" alt="MemberSync Logo" class="h-12 w-12 transition-transform group-hover:rotate-6" />
        <!-- Brand Name -->
        <span class="text-2xl font-extrabold tracking-tight text-[#2C2E6C] dark:text-[#c89654]">MemberSync</span>
      </a>

      <!-- Nav Links -->
      <div class="flex items-center space-x-6">
        <a href="#features" class="nav-link text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors">Features</a>
        <a href="/home" class="nav-link text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors">Home</a>

        <a href="/login"
          class="bg-[#2C2E6C] hover:bg-[#1e293b] text-white font-semibold py-2 px-5 rounded-full shadow-sm transition-all focus:ring-2 focus:ring-blue-400 focus:outline-none">
          Login
        </a>
      </div>
    </div>
  </nav>


  <!-- ✅ Hero Section (unchanged, only navbar floats above it) -->
  <section class="relative h-[100vh] flex items-center justify-center overflow-hidden">
    <img src="https://images.unsplash.com/photo-1556761175-b413da4baf72?q=80&w=1974&auto=format&fit=crop" 
         class="absolute inset-0 w-full h-full object-cover brightness-[0.4]" alt="Community background" />
    <div class="absolute inset-0 bg-gradient-to-b from-black/30 to-black/70"></div>

    <div class="relative z-10 text-center max-w-2xl px-6">
      <h1 class="text-5xl sm:text-6xl font-extrabold text-white leading-tight mb-4">
        Empowering <span class="text-[#C89654]">Communities</span> with Smart Connectivity
      </h1>

      <p class="text-gray-200 text-lg sm:text-xl mb-8">
        MemberSync brings your organization, events, and members together — all in one intelligent platform.
      </p>

      <div class="flex justify-center gap-4">
        <a href="/login" class="bg-[#C89654] hover:bg-[#B07F44] text-white font-semibold py-3 px-8 rounded-full shadow-lg transition-transform hover:scale-105">
          Get Started
        </a>

        <a href="#features" class="border border-gray-300 hover:border-[#2C2E6C] text-white/80 hover:text-white py-3 px-8 rounded-full transition-colors">
          Learn More
        </a>
      </div>
    </div>
  </section>

  <!-- ✅ Features Section (you said keep it exactly — unchanged) -->
  <section id="features" class="py-20 bg-white">
    <div class="max-w-6xl mx-auto px-6 text-center">
      <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-800 mb-8">Why Choose MemberSync?</h2>
      <p class="text-gray-500 max-w-2xl mx-auto mb-16">
        A unified solution to help your community thrive. Simplify management, enhance engagement, and make data-driven decisions.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
        <div class="p-8 rounded-xl shadow hover:shadow-lg transition-all border-t-4 border-blue-500 bg-white">
          <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="mx-auto w-16 mb-4" alt="Users Icon" />
          <h3 class="text-xl font-semibold mb-2 text-gray-800">Membership Management</h3>
          <p class="text-gray-600 text-sm">Track member profiles, subscriptions, and renewals effortlessly.</p>
        </div>

        <div class="p-8 rounded-xl shadow hover:shadow-lg transition-all border-t-4 border-green-500 bg-white">
          <img src="https://cdn-icons-png.flaticon.com/512/1055/1055646.png" class="mx-auto w-16 mb-4" alt="Event Icon" />
          <h3 class="text-xl font-semibold mb-2 text-gray-800">Event Coordination</h3>
          <p class="text-gray-600 text-sm">Organize, schedule, and manage attendance with ease.</p>
        </div>

        <div class="p-8 rounded-xl shadow hover:shadow-lg transition-all border-t-4 border-yellow-500 bg-white">
          <img src="https://cdn-icons-png.flaticon.com/512/1170/1170576.png" class="mx-auto w-16 mb-4" alt="Marketplace Icon" />
          <h3 class="text-xl font-semibold mb-2 text-gray-800">Marketplace Insights</h3>
          <p class="text-gray-600 text-sm">Get valuable analytics on orders, products, and performance.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ✅ Call to Action (unchanged) -->
  <section class="bg-[#5F9EA0] text-white py-20 text-center">
    <h2 class="text-3xl sm:text-4xl font-bold mb-6">Ready to Transform Your Community?</h2>
    <p class="text-blue-100 mb-8 max-w-2xl mx-auto">
      Join thousands of members already using MemberSync to stay connected, informed, and empowered.
    </p>
    <a href="/login" class="bg-white text-blue-600 font-semibold py-3 px-8 rounded-full hover:bg-gray-100 transition">
      Get Started Today
    </a>
  </section>

  <!-- ✅ Footer (unchanged) -->
  <footer class="bg-gray-100 text-center py-6 mt-10">
    <p class="text-sm text-gray-500">
      © 2025 <a href="/" class="text-blue-600 hover:underline">MemberSync</a>. All rights reserved.
    </p>
  </footer>

  <!-- ✅ Navbar Scroll Script -->
  <script>
    const navbar = document.getElementById('navbar');

    window.addEventListener('scroll', () => {
      if (window.scrollY > 40) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  </script>

</body>
</html>

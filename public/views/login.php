<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Member Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-[#2C2E6C] via-white to-[#C89654] flex items-center justify-center h-screen">

  <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl transition transform hover:scale-[1.01] duration-300">
    <!-- 🔹 Logo -->
    <div class="flex justify-center mb-4">
      <img 
        src="/assets/files/logo.png" 
        height="150"
        width="150"
        alt="MemberSync Logo" 
        class="object-contain transition-transform duration-300 hover:scale-105"
      />
    </div>

    <h2 class="text-3xl font-extrabold mb-6 text-center text-gray-800">Admin Login</h2>
    
    <form id="loginForm" class="space-y-5">
      <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-sm" role="alert"></div>

      <div>
        <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">Email Address</label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition" 
          placeholder="example@email.com" 
          required
        >
      </div>

      <div>
        <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
        <input 
          type="password" 
          id="password" 
          name="password" 
          class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition" 
          placeholder="••••••••" 
          required
        >
      </div>

      <button 
        type="submit" 
        id="submitBtn" 
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
      >
        Sign In
      </button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-6">
      Forgot your password? <a href="#" class="text-blue-600 hover:underline">Reset it</a>
    </p>
  </div>

  <script src="/assets/js/main.js"></script>
</body>
</html>

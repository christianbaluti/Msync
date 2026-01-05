/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.{html,js,php}', // Adjust this to scan your project files
  ],
  theme: {
    extend: {
      // 1. Define your custom font families
      fontFamily: {
        sans: ['Gotham Rounded Book', 'sans-serif'],
        // You can map 'bold' weight to the Black font in your CSS @font-face rule
      },
      // 2. Define your custom colors
      colors: {
        'brand-red': '#EE3129',
        'brand-gray': '#58595B',
      },
    },
  },
  plugins: [],
}
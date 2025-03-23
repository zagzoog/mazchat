/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./dashboard.php",
    "./login.php",
    "./profile.php",
    "./api/**/*.php",
    "./app/**/*.php",
    "./src/**/*.{js,jsx,ts,tsx}",
  ],
  theme: {
    extend: {
      fontFamily: {
        'cairo': ['Cairo', 'sans-serif'],
      },
    },
  },
  plugins: [],
} 
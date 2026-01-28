/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './src/**/*.{html,js,jsx,ts,tsx}',
    './src/**/*.html',
  ],
  theme: {
    extend: {
      fontFamily: {
        outfit: ['Outfit', 'sans-serif'],
      },
      screens: {
        '2xsm': '375px',
        'xsm': '425px',
        '3xl': '2000px',
      },
      fontSize: {
        'title-2xl': ['72px', '90px'],
        'title-xl': ['60px', '72px'],
        'title-lg': ['48px', '60px'],
        'title-md': ['36px', '44px'],
        'title-sm': ['30px', '36px'],
      },
    },
  },
  plugins: [],
  darkMode: 'class',
}

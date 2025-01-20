/** @type {import('tailwindcss').Config} */
export default {
  theme: {
    extend: {},
  },

  content: {
    relative: true,
    files: [
      './views/**/*.{antlers.html,blade.php,vue,md}',
      './fieldsets/**/*.yaml',
    ]
  },

  plugins: [
    require('@tailwindcss/typography'),
  ],

  prefix: 'hls-',
}

/** @type {import('tailwindcss').Config} */
export default {
    theme: {
        extend: {},
    },

    content: [
        '../resources/views/**/*.{antlers.html,blade.php,vue,md}',
        '../resources/fieldsets/**/*.yaml',
        './views/**/*.{antlers.html,blade.php,vue,md}',
        './fieldsets/**/*.yaml',
    ],

    plugins: [
        require('@tailwindcss/typography'),
    ],

    prefix: 'hls-',
};

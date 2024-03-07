/** @type {import('tailwindcss').Config} */
export default {
  content: [
      "./resources/**/*.blade.php",
  ],
  theme: {
      fontFamily: {
          'display': ['Inter'],
          'body': ['Inter'],
      },
      fontSize: {
          xs: ['0.625rem', '12.1px'],
          base: ['1.125rem', '22px'],
      },
      extend: {
        colors: {
            primary: '#00E4A1',
            'gray-primary': '#444444',
        }
      },
  },
  plugins: [],
}


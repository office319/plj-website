module.exports = {
  content: [
    './*.html',
    './blog/*.html',
    './assets/email/*.html',
  ],
  theme: {
    extend: {
      colors: {
        fresh: {
          50: '#f0fdfa',
          100: '#ccfbf1',
          200: '#99f6e4',
          300: '#5eead4',
          400: '#2dd4bf',
          500: '#14b8a6',
          600: '#0d9488',
          700: '#0f766e',
          800: '#115e59',
          900: '#134e4a',
        },
        clean: {
          50: '#e6f7f7',
          100: '#CCE1E2',
          200: '#b3d4d6',
          300: '#99c7ca',
          400: '#81B4B9',
          500: '#009096',
          600: '#007a7f',
          700: '#006468',
          800: '#004e52',
          900: '#003a3d',
        },
      },
      fontFamily: {
        display: ['system-ui', 'sans-serif'],
        body: ['system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};

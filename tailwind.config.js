/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    // Config/layouts Holds most of the shared chrome (sidebar, topbar,
    // auth panel), so it must be scanned or those utilities get purged.
    './Config/layouts/**/*.php',
    // Config/partials (form profil) & Config/Services (partial yang di-echo)
    // juga memuat utility, termasuk padding ikon input (pl-11/pr-11).
    './Config/**/*.php',
    './User/**/*.php',
    './Admin/**/*.php',
    './Owner/**/*.php',
    // File PHP di root: login.php & logout.php (halaman auth).
    './*.php',
    './Assets/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        // Aksen utama: indigo. Pilihan aman untuk CTA & tautan.
        brand: {
          50: '#eef2ff',
          100: '#e0e7ff',
          200: '#c7d2fe',
          300: '#a5b4fc',
          400: '#818cf8',
          500: '#6366f1',
          600: '#4f46e5',
          700: '#4338ca',
          800: '#3730a3',
          900: '#312e81',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        // "shadow tipis" sesuai brief: elevate halus, bukan dramatis.
        card: '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
        lift: '0 10px 30px -12px rgb(15 23 42 / 0.18)',
      },
      borderRadius: {
        '4xl': '2rem',
      },
      keyframes: {
        'fade-up': {
          from: { opacity: '0', transform: 'translateY(12px)' },
          to: { opacity: '1', transform: 'translateY(0)' },
        },
      },
      animation: {
        'fade-up': 'fade-up 0.45s cubic-bezier(0.16, 1, 0.3, 1) both',
      },
    },
  },
  // Preflight (reset) sekarang DINYALAKAN. Seluruh halaman sudah
  // migrated dari Bootstrap ke Tailwind, jadi kita butuh reset yang
  // konsisten (margin heading, gaya button bawaan browser, dsb).
  corePlugins: {
    preflight: true,
  },
  plugins: [],
};

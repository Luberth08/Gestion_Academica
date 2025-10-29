import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  base: '/', // importante
  build: {
    outDir: 'dist', // salida esperada por Vercel
  },
})
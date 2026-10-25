import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
	plugins: [react()],
	publicDir: false,
	base: '/assets/react/',
	build: {
		outDir: 'public/assets/react',
		emptyOutDir: true,
		rollupOptions: {
			input: 'src/main.jsx',
			output: {
				entryFileNames: 'app.js',
				chunkFileNames: 'chunks/[name].js',
				assetFileNames: 'assets/[name][extname]'
			}
		}
	}
});

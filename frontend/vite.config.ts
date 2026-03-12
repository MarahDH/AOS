import { defineConfig, splitVendorChunkPlugin } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";

// https://vite.dev/config/
export default defineConfig({

  plugins: [react(), splitVendorChunkPlugin()],
  resolve: {
    alias: {
      "@interfaces": path.resolve(__dirname, "src/shared/models/interfaces"),
      "@types": path.resolve(__dirname, "src/shared/models/types"),
      "@enums": path.resolve(__dirname, "src/shared/enums"),
      "@components": path.resolve(__dirname, "src/components"),
      "@pages": path.resolve(__dirname, "src/pages"),
      "@utils": path.resolve(__dirname, "src/shared/utils"),
      "@contexts": path.resolve(__dirname, "src/shared/contexts"),
      "@api": path.resolve(__dirname, "src/api"),
      "@hooks": path.resolve(__dirname, "src/shared/hooks"),
      "@styles": path.resolve(__dirname, "src/styles"),
      "@assets": path.resolve(__dirname, "src/assets"),
    },
  },
  build: {
    chunkSizeWarningLimit: 1000,

    rollupOptions: {
      output: {
        manualChunks: {
          react: ["react", "react-dom"],
          mui: [
            "@mui/material",
            "@mui/icons-material",
            "@mui/x-data-grid",
            "@mui/x-date-pickers",
            "@emotion/react",
            "@emotion/styled",
            "@fontsource/roboto",
          ],
          tanstack: ["@tanstack/react-query"],
          lodash: ["lodash", "lodash.debounce"],
          utils: [
            "axios",
            "yup",
            "notistack",
            "react-cookie",
            "react-router-dom",
          ],
        },
      },
    },
  },
});

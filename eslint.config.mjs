import react from "eslint-plugin-react";
import prettier from "eslint-plugin-prettier";
import prettierConfig from "eslint-config-prettier";
import globals from "globals";
import path from "node:path";
import { fileURLToPath } from "node:url";
import js from "@eslint/js";
import { FlatCompat } from "@eslint/eslintrc";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const compat = new FlatCompat({
    baseDirectory: __dirname,
    recommendedConfig: js.configs.recommended,
    allConfig: js.configs.all
});

export default [
    js.configs.recommended,
    ...compat.extends("plugin:react/recommended"),
    prettierConfig,
    {
        plugins: {
            react,
            prettier,
        },

        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.commonjs,
                jQuery: "readonly",
                $: "readonly",
            },

            ecmaVersion: "latest",
            sourceType: "module",
        },

        settings: {
            react: {
                version: "detect",
            },
        },

        rules: {
            "react/react-in-jsx-scope": "off",
            "prettier/prettier": "error",
        },
    }
];
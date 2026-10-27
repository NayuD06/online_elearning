import defaultTheme from "tailwindcss/defaultTheme";
import colors from "tailwindcss/colors";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.js",
        "./app/Enums/**/*.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Be Vietnam Pro", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: colors.teal,
            },
            boxShadow: {
                card: "0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 16px rgba(15, 23, 42, 0.06)",
            },
        },
    },

    plugins: [forms],
};

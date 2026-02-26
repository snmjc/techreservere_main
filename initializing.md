create the docker-compose.yml
create backend folder
initialize symphony: composer create-project symfony/skeleton .
install packages of symphony: composer require symfony/webapp-pack
install more dependencies:
- composer require symfony/orm-pack
- composer require symfony/maker-bundle --dev
- composer require doctrine/doctrine-migrations-bundle


create frontend folder
install vuejs: npm create vite@latest .
- Select Vue
- Select JS
install packages: npm install
install tailwind: npm install -D tailwindcss postcss autoprefixer
initialize: npx tailwindcss init -p
if not working manual add the:
    - `postcss.config.cjs`
    ```js
    module.exports = {
        plugins: {
            tailwindcss: {},
            autoprefixer: {},
        },
    }
    ```
    - `style.css` (update and add tailwind)
    ```css
    :@tailwind base;
    @tailwind components;
    @tailwind utilities;

    ... other codes
    ```
    - `tailwind.config.cjs`
    ```js
    module.exports = {
        content: [
            "./index.html",
            "./src/**/*.{vue,js,ts,jsx,tsx}"
        ],
        theme: {
            extend: {},
        },
        plugins: [],
    }
    ```
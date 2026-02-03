composer create-project symfony/skeleton my_api
cd my_api


composer config platform.php 8.3


composer require doctrine maker --dev
composer require serializer validator security
composer require nelmio/cors-bundle



composer require api
composer require maker --dev

composer require lcobucci/jwt:^5.6
composer require lexik/jwt-authentication-bundle


Generate keys

VERY important

php bin/console lexik:jwt:generate-keypair

Run server

symfony serve
php -S localhost:8020 -t public


Create entity
php bin/console make:entity User


php bin/console make:user

add Column for User entity
php bin/console make:entity User

php bin/console make:migration
php bin/console doctrine:migrations:migrate

php bin/console cache:clear

// load seeder data/fixtures
php bin/console doctrine:fixtures:load
#doctrine:fixtures:load clears existing data by default. If you want to keep data, use:
php bin/console doctrine:fixtures:load --append



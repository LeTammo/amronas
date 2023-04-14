1. Copy the contents of `.env.changeMe` to `.env` and fill in the values.

2. Building the Docker image:  
`docker compose up -d --build`  
or running the Docker image:  
`docker compose up -d`

3. Möglicherweise einmal  
`chown -R www-data:www-data /var/www/app`

4. Außerdem
   `php bin/console doctrine:schema:update --force`

5. composer install
6. yarn install  
   `sudo docker compose run --rm node-service yarn install`

5. Enjoy!
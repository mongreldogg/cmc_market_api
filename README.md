__CampusCoin Market Price API__

*Features*

1. Scheduled backend-only regular prices update
2. Caching values of current market prices in a database
3. Pulls data from CoinExchange, Cryptopia and Cryptowolf APIs

*Installation Requirements*

0. Linux OS (project initially tested on Debian and Ubuntu)
1. Nginx web server
2. MySQL server (5.7 or higher / MariaDB)
3. PHP 5.6 or higher (tested on 7.0 and 7.2, recommended for higher performance)
4. Following extensions must be installed: php-curl, php-mysql (or php-mysqli)
5. Following tools must be also installed on your system: `watch`, `wget`.

*How to install*

0. Install PHP of appropriate version
1. Install Nginx web server and apply a configuration for a website instance. Example has been provided in configuration file `files/nginx.example.conf`
2. Install MySQL server and back-up a database from dump `files/market_api.sql`
3. Edit important configuration for your domain, database credentials and your coin listing on crypto markets inside php files in `config/` folder.

*How to launch instant price updates*

Simply go to project root folder and run the following command:

`php console update_prices`
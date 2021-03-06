# Backend
FROM php:7.4-apache as backend

#install dependencies
RUN apt-get update && apt-get install -y \
    git \
    build-essential \
    locales \
    zip \
    inotify-tools \
    libonig-dev \
    libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring zip exif

# change the document root to /var/www/html/public
RUN sed -i -e "s/html/html\/public/g" \
    /etc/apache2/sites-enabled/000-default.conf

COPY .docker/web/php/php.ini /usr/local/etc/php/php.ini

# enable apache mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Get composer
COPY --from=composer:1.10.22 /usr/bin/composer /usr/bin/composer

# Get node
ENV NODE_VERSION=10.24.0
RUN apt install -y curl
RUN curl -o- https://raw.githubusercontent.com/creationix/nvm/v0.34.0/install.sh | bash
ENV NVM_DIR=/root/.nvm
RUN . "$NVM_DIR/nvm.sh" && nvm install ${NODE_VERSION}
RUN . "$NVM_DIR/nvm.sh" && nvm use v${NODE_VERSION}
RUN . "$NVM_DIR/nvm.sh" && nvm alias default v${NODE_VERSION}
ENV PATH="/root/.nvm/versions/node/v${NODE_VERSION}/bin/:${PATH}"
RUN npm install -g yarn

COPY ./storage /var/www/html/storage

EXPOSE 80

ENTRYPOINT [ ".docker/scripts/startup.sh" ]

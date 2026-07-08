FROM php:8.3-fpm

# Install system dependencies
RUN apt update && apt install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    msmtp \
    libzip-dev \
    && apt clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (Added zip)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

ARG user=laravel_user
ARG uid=1000

# Configure PHP to use msmtp
RUN echo "sendmail_path = /usr/bin/msmtp -t" > /usr/local/etc/php/conf.d/mail.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js and pnpm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt install -y nodejs \
    && npm install -g pnpm \
    && apt clean && rm -rf /var/lib/apt/lists/*

ENV PNPM_HOME="/home/${user}/.local/share/pnpm"
ENV PATH="${PATH}:${PNPM_HOME}"

RUN pnpm config set store-dir /var/www/html/.pnpm-store

# Non-root user configuration


# Ensure the user owns the working directory
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user/.composer && chown -R $user:$user /home/$user \
    && mkdir -p /var/www/html && chown -R $user:$user /var/www/html

WORKDIR /var/www/html

USER $user

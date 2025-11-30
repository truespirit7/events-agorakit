FROM php:8.4-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-client \
    libpq-dev \
    libc-client-dev \
    libkrb5-dev \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip imap sockets

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка рабочей директории
WORKDIR /var/www/html

# Копирование файлов проекта
COPY . .

# Установка зависимостей PHP
RUN composer install --no-dev --optimize-autoloader

# Настройка прав
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Экспорт порта
EXPOSE 80

# Команда запуска
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
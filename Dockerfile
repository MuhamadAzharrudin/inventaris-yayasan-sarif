# ==========================================
# Stage 1: Build frontend
# ==========================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY . .

RUN npm run build


# ==========================================
# Stage 2: Laravel Production
# ==========================================
FROM dunglas/frankenphp:php8.3-alpine

WORKDIR /app

# Install required PHP extensions
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Laravel application
COPY . .

# Copy frontend build
COPY --from=frontend /app/public/build ./public/build

COPY Caddyfile /etc/caddy/Caddyfile

# Install production dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Laravel permissions
RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache \
    && chmod -R 775 \
    storage \
    bootstrap/cache

# Production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN echo "opcache.enable=1" > "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.memory_consumption=128" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.max_accelerated_files=10000" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.validate_timestamps=0" >> "$PHP_INI_DIR/conf.d/opcache.ini"

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
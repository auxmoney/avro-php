FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install zip

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Build arguments for user/group ID
ARG USER_ID=1000
ARG GROUP_ID=1000

# Create application group and user with specified IDs
RUN groupadd -g $GROUP_ID application && \
    useradd -m -s /bin/bash -u $USER_ID -g $GROUP_ID application

USER application
WORKDIR /avro-php
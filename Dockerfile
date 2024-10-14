FROM php:7.4-apache

# Copia o script PHP para o diretório padrão do Apache
COPY notification.php /var/www/html/

# Instala extensões necessárias
RUN docker-php-ext-install mysqli

# Variáveis de ambiente que serão configuradas no Render
ENV DB_HOST="" \
    DB_USER="" \
    DB_PASSWORD="" \
    DB_NAME="" \
    ACCESS_TOKEN=""

# Expõe a porta 80 para o serviço HTTP
EXPOSE 80

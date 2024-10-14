# Usa a imagem oficial do PHP com Apache
FROM php:7.4-apache

# Copia os arquivos do projeto para o diretório padrão do Apache
COPY . /var/www/html/

# Instala extensões necessárias para MySQL
RUN docker-php-ext-install mysqli

# Expor a porta 80
EXPOSE 80

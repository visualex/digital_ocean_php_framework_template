FROM ghcr.io/visualex/visualex/digital_ocean_php_template-mysql:latest

ADD . /var/www/html/

RUN chmod 775 bin/cake
RUN mkdir -p logs
RUN chmod 777 logs
RUN mkdir -p tmp
RUN chmod 777 tmp


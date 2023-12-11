FROM ghcr.io/visualex/digital_ocean_php_framework_template-aphp:latest

ADD . /var/www/html/

RUN chmod 775 bin/cake
RUN mkdir -p logs
RUN chmod 777 logs
RUN mkdir -p tmp
RUN chmod 777 tmp


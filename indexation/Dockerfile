FROM r-base

RUN apt-get update \
 && apt-get install -y default-jdk libssl-dev libcurl4-openssl-dev libxml2-dev \
                       python2.7 python-pip libjson-perl

RUN pip install pdfminer

RUN R -e "install.packages('x.ent')"

COPY ini.json /usr/local/lib/R/site-library/x.ent/www/config/ini.json

RUN mkdir /work
WORKDIR /work

CMD ["make"]

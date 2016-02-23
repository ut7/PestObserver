FROM r-base

RUN apt-get update \
 && apt-get install -y default-jdk libssl-dev libcurl4-openssl-dev libxml2-dev \
                       python2.7 python-pip libjson-perl

RUN pip install pdfminer

RUN cd /opt \
 && wget --quiet http://igm.univ-mlv.fr/~unitex/Unitex3.0.zip \
 && unzip Unitex3.0.zip \
 && (cd Unitex3.0/Src/C++/build && make install) \
 && rm Unitex3.0.zip

RUN mkdir -p /vespa/R-lib
ENV R_LIBS_USER=/vespa/R-lib

WORKDIR /vespa

COPY install_x.ent.sh /vespa
RUN ./install_x.ent.sh

COPY . /vespa

COPY indexation/ini.json /vespa/R-lib/x.ent/www/config/ini.json

CMD ["make"]

#!/bin/bash -ex

wget -N -L -r -l6 --trust-server-names --accept-regex='.*/BSV-.*|.*\.pdf|.*/Bulletin-de-sante-du-vegetal.*$' $1


# find . -name "BSV-*"|grep -v ".*\.pdf"|xargs rm

# http://draaf.auvergne-rhone-alpes.agriculture.gouv.fr/BSV-AUVERGNE-GRANDES-CULTURES-2015

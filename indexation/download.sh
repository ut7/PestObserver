#!/bin/bash -e

download() {
  local regex=$1
  local source_url=$2

  >&2 echo "Downloading BSV from: $source_url"
  wget -N -L -r -l6 --trust-server-names --accept-regex="$regex" "$source_url"
  #find . -type f ! -name "*.pdf" -exec rm {} \;
}

REPORTS="downloadedBSV"
mkdir -p $REPORTS
cd $REPORTS

REGEX1='BSV-.*|Archives-BSV.*|ARCHIVES-BSV.*|.*\.pdf|Grandes-cultures.*|Arboriculture.*|Cultures-legumieres.*|Lin-oleagineux.*|Noix.*|Tabac.*|Viticulture.*|Chataigne.*|Horticulture.*|Prairie.*|Bulletin.*|Bilan_.*|Nouvel-article.*|Campagne.*'

download "$REGEX1" "http://draaf.alsace-champagne-ardenne-lorraine.agriculture.gouv.fr/Surveillance-des-organismes"
download "$REGEX1" "http://draaf.aquitaine-limousin-poitou-charentes.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal"
download "$REGEX1" "http://draaf.auvergne-rhone-alpes.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal"
download "$REGEX1" "http://draaf.bourgogne-franche-comte.agriculture.gouv.fr/Bulletins-de-sante-du-vegetal-BSV"
download "$REGEX1" "http://draaf.bretagne.agriculture.gouv.fr/Bulletin-de-Sante-du-Vegetal-BSV"
download "$REGEX1" "http://draaf.centre-val-de-loire.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal"
download "$REGEX1" "http://draaf.corse.agriculture.gouv.fr/Les-bulletins-de-sante-du-vegetal"
download "$REGEX1|Midi-Pyrenees|Languedoc-Roussillon|Lozere" "http://draaf.languedoc-roussillon-midi-pyrenees.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal"
download "$REGEX1|Nord-Pas-de-Calais|Picardie" "http://draaf.nord-pas-de-calais-picardie.agriculture.gouv.fr/Bulletins-de-sante-du-vegetal-BSV"
download "$REGEX1" "http://draaf.normandie.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal"
download "$REGEX1|2016|2015" "http://draaf.paca.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal-BSV"

download "$REGEX1" "http://draaf.pays-de-la-loire.agriculture.gouv.fr/Derniers-BSV"
download "$REGEX1|/bsv-" "http://www.paysdelaloire.chambagri.fr/menu/vegetal/surveillance-biologique-du-territoire/bsv-grandes-cultures.html"

download "$REGEX1" "http://driaaf.ile-de-france.agriculture.gouv.fr/Epidemiosurveillance-et-Bulletin"

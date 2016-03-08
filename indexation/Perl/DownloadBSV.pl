use 5.018;
use autodie;
use warnings;
use File::Path qw(make_path);

my $REPORTS="data/downloadedBSV";
make_path($REPORTS);
chdir $REPORTS;

sub download {
  my ($regex, $source_url) = @_;

  printf STDERR "Downloading BSV from: %s", $source_url;
  system "wget", "--timestamping", "--relative", "--recursive", "--level=6",
    "--trust-server-names", "--accept-regex=$regex",
    "--no-verbose",
    $source_url
}

my $default_regex='BSV-.*|Archives-BSV.*|ARCHIVES-BSV.*|.*\.pdf|Grandes-cultures.*|Arboriculture.*|Cultures-legumieres.*|Lin-oleagineux.*|Noix.*|Tabac.*|Viticulture.*|Chataigne.*|Horticulture.*|Prairie.*|Bulletin.*|Bilan_.*|Nouvel-article.*|Campagne.*|GRANDES-CULTURES.*|VIGNE.*|LEGUMES.*|HORTICULTURE.*|HOUBLON.*|ZNA.*|TABAC.*|Pour-les.*|/bsv-';

download("$default_regex", "http://draaf.alsace-champagne-ardenne-lorraine.agriculture.gouv.fr/Surveillance-des-organismes");
download("$default_regex", "http://draaf.aquitaine-limousin-poitou-charentes.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal");
download("$default_regex", "http://draaf.auvergne-rhone-alpes.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal");
download("$default_regex", "http://draaf.bourgogne-franche-comte.agriculture.gouv.fr/Bulletins-de-sante-du-vegetal-BSV");
download("$default_regex", "http://draaf.bretagne.agriculture.gouv.fr/Bulletin-de-Sante-du-Vegetal-BSV");
download("$default_regex", "http://draaf.centre-val-de-loire.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal");
download("$default_regex", "http://draaf.corse.agriculture.gouv.fr/Les-bulletins-de-sante-du-vegetal");
download("$default_regex|Midi-Pyrenees|Languedoc-Roussillon|Lozere", "http://draaf.languedoc-roussillon-midi-pyrenees.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal");
download("$default_regex|Nord-Pas-de-Calais|Picardie", "http://draaf.nord-pas-de-calais-picardie.agriculture.gouv.fr/Bulletins-de-sante-du-vegetal-BSV");
download("$default_regex", "http://draaf.normandie.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal");
download("$default_regex|2016|2015", "http://draaf.paca.agriculture.gouv.fr/Bulletin-de-sante-du-vegetal-BSV");
download("$default_regex", "http://draaf.pays-de-la-loire.agriculture.gouv.fr/Derniers-BSV");
download("$default_regex", "http://www.paysdelaloire.chambagri.fr/menu/vegetal/surveillance-biologique-du-territoire/bsv-grandes-cultures.html");
download("$default_regex", "http://driaaf.ile-de-france.agriculture.gouv.fr/Epidemiosurveillance-et-Bulletin");

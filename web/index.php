<?php

use Silex\Application;
use Silex\Provider;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use MySQLHandler\MySQLHandler;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

// Chargement de la config dans parameters.json
require_once __DIR__.'/../config/config.php';
    
// Activation du debugging a desactiver en production
$app['debug'] = $app['parameters']['debug'];
$app['stopwatch'] = new Stopwatch();

$app->before(function ($request) use ($app) {
    $app['stopwatch']->start('vespa');
}, Application::EARLY_EVENT);

$app->register(new Provider\DoctrineServiceProvider());
$app->register(new Provider\SecurityServiceProvider());
$app->register(new Provider\RememberMeServiceProvider());
$app->register(new Provider\SessionServiceProvider());
$app->register(new Provider\ServiceControllerServiceProvider());
$app->register(new Provider\UrlGeneratorServiceProvider());
$app->register(new Provider\TwigServiceProvider());
$app->register(new Provider\SwiftmailerServiceProvider());

// Register the SimpleUser service provider.
$simpleUserProvider = new SimpleUser\UserServiceProvider();
$app->register($simpleUserProvider);

// Register Monolog
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.$app['parameters']['monolog']['logfile'],
    'monolog.name' => $app['parameters']['monolog']['name']
));

// Mount the user controller routes:
$app->mount('/user', $simpleUserProvider);

/*****************************************************************************************
 *                                                                                       *
 * Intégration de l'interface de Datamining de Vespa                                     *
 *                                                                                       *
 *****************************************************************************************/
$app->get('/', function(Request $request) use ($app) {
    //return file_get_contents('header.php').file_get_contents('vespa.php').file_get_contents('footer.php');
    return $app['twig']->render('vespa.twig', array());
})->bind('vespa');

/*****************************************************************************************
 *                                                                                       *
 * Requête de récupération des paramètres d'initialisation de l'interface                *
 *                                                                                       *
 *****************************************************************************************/
$app->post('/Services/Vespa.svc/GetInitializationInfos', function() use ($app) {
    $sql = "SELECT DATE_FORMAT(min(date),'%d/%m/%Y') AS MinDate, DATE_FORMAT(max(date),'%d/%m/%Y') AS MaxDate FROM report";
    $res = $app['db']->fetchAssoc($sql);
    $response['ErrorMessage'] = null;
    $response['ErrorStackTrace'] = null;
    $response['MinDate'] = $res['MinDate'];
    $response['MaxDate'] = $res['MaxDate'];
    return $app->json($response);
});

/*****************************************************************************************
 *                                                                                       *
 * Requête de récupération de la liste des plantes, éventuellement filtré par un mot clé *
 *                                                                                       *
 *****************************************************************************************/
$app->post('/Services/Vespa.svc/GetPlants', function (Request $request) use ($app) {
    // Récupération du filtre text
    $req = json_decode( $request->getContent(), true );
    if ( ( is_null( $req ) ) || (count($req) == 0 ) ) {
        $text = $request->get('text');
    } else {
        $text = $req['text'];
    }
    $searchText = "%".$text."%";
    
    // Construction de la requête
    $sql = "SELECT Id, Name AS Text FROM plant WHERE Name LIKE ? ORDER BY Name";

    // Appel à la BDD
    $results = $app['db']->fetchAll($sql, array( $searchText ) );

    // Reformatage des résultats
    foreach( $results as $res) {
        $items[] = array( "Id"=>(int) $res['Id'], "Text"=>mb_convert_encoding($res['Text'], "UTF-8") );
    }

    // Construction du tableau retour
    $response['ErrorMessage'] = null;
    $response['ErrorStackTrace'] = null;
    $response['Items'] = $items;

    // Conversion de la réponse en JSON et retour
    return $app->json($response);
});

/*****************************************************************************************
 *                                                                                       *
 * Requête de récupération de la liste des bioagresseur filtrée par un mot clé           *
 *                                                                                       *
 *****************************************************************************************/
$app->post('/Services/Vespa.svc/GetBugs', function(Request $request) use ($app) {
    // Récupération du filtre text
    $req = json_decode( $request->getContent(), true );
    if ( ( is_null( $req ) ) || (count($req) == 0 ) ) {
        $text = $request->get('text');
    } else {
        $text = $req['text'];
    }
    $searchText = "%".$text."%";

    
    // Construction de la requête
    $sql = "SELECT Id, Name AS Text FROM bioagressor WHERE Name LIKE ? ORDER BY Name";

    // Appel à la BDD
    $results = $app['db']->fetchAll($sql, array( $searchText ) );

    // Reformatage des résultats
    foreach( $results as $res) {
        $items[] = array( "Id"=>(int) $res['Id'], "Text"=>mb_convert_encoding($res['Text'], "UTF-8") );
    }

    // Construction du tableau retour
    $response['ErrorMessage'] = null;
    $response['ErrorStackTrace'] = null;
    $response['Items'] = $items;

    // Conversion de la réponse en JSON et retour
    return $app->json($response);
});

/*****************************************************************************************
 *                                                                                       *
 * Requête de récupération de la liste des maladies filtrée par un mot clé               *
 *                                                                                       *
 *****************************************************************************************/
$app->post('/Services/Vespa.svc/GetDiseases', function(Request $request) use ($app) {
    // Récupération du filtre text
    $req = json_decode( $request->getContent(), true );
    if ( ( is_null( $req ) ) || (count($req) == 0 ) ) {
        $text = $request->get('text');
    } else {
        $text = $req['text'];
    }
    $searchText = "%".$text."%";
    
    // Construction de la requête
    $sql = "SELECT Id, Name AS Text FROM disease WHERE Name LIKE ? ORDER BY Name";

    // Appel à la BDD
    $results = $app['db']->fetchAll($sql, array( $searchText ) );

    // Reformatage des résultats
    foreach( $results as $res) {
        $items[] = array( "Id"=>(int) $res['Id'], "Text"=>mb_convert_encoding($res['Text'], "UTF-8") );
    }

    // Construction du tableau retour
    $response['ErrorMessage'] = null;
    $response['ErrorStackTrace'] = null;
    $response['Items'] = $items;

    // Conversion de la réponse en JSON et retour
    return $app->json($response);
});

/*****************************************************************************************
 *                                                                                       *
 * Requête de récupération des infos sur la zone géographique                            *
 *                                                                                       *
 *****************************************************************************************/
$app->post('/Services/Vespa.svc/GetAreaDetails', function(Request $request) use ($app) {
    // Récupération des critères de recherche
    $req = json_decode( $request->getContent(), true );
    if ( ( is_null( $req ) ) || (count($req) == 0 ) ) {
        $idPlant = $request->get('Id_Plant');
        $idBioagressor = $request->get('Id_Bioagressor');
        $idDisease = $request->get('Id_Disease');
        $idArea = $request->get('Id_Area');
        $dateStart = $request->get('DateStart');
        $dateEnd = $request->get('DateEnd');
        $searchText = $request->get('SearchText');
    } else {
        $idPlant = $req['Id_Plant'];
        $idBioagressor = $req['Id_Bioagressor'];
        $idDisease = $req['Id_Disease'];
        $idArea = $req['Id_Area'];
        $dateStart = $req['DateStart'];
        $dateEnd = $req['DateEnd'];
        $searchText = $req['SearchText'];
    }

    // Champ vide transformé en null pour utiliser le text mysql NULL IS NULL qui neutralise la valeur NULL mais pas la valeur vide
    if ( $idPlant == "" ) $idPlant = null ;
    if ( $idBioagressor == "" ) $idBioagressor = null ;
    if ( $idDisease == "" ) $idDisease = null ;

    // Petit reformatage du motif de recherche textuel
    if ( is_null( $searchText ) || $searchText == '' ) {
        $textLike = null;
    } else {
        $textLike = "%".$searchText."%";
    }

    // Recherche du nom de zone
    if ( is_null( $idArea ) || $idArea == "" ) {
        $areaname = null;
    } else {
        $sql = "SELECT Name FROM area WHERE id = ?";
        $res = $app['db']->fetchAssoc( $sql, array( $idArea ) );
        foreach( $res as $key=>$value )
            $areaname = $value ;
    }

    // Récupération des plantes de la zone
    if ( ! ( is_null( $idDisease ) ) ) {
        $sql = "SELECT DISTINCT plant.id AS Id, plant.name AS Text
                FROM area
                LEFT OUTER JOIN report ON area.id = report.id_area
                LEFT OUTER JOIN plant_disease ON plant_disease.id_report = report.id
                LEFT OUTER JOIN plant ON plant_disease.id_plant = plant.id
                WHERE ( ? IS NULL OR report.date > STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( ? IS NULL OR report.date < STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( plant_disease.id_disease = ? )
                AND ( report.id_area = ? )
                AND ( ? IS NULL OR report.content LIKE ? )
                ORDER BY plant.name";
        $results = $app['db']->fetchAll($sql, array( $dateStart, $dateStart, $dateEnd, $dateEnd, $idDisease, $idArea, $textLike, $textLike ) );
    } else {
        $sql = "SELECT DISTINCT plant.id AS Id, plant.name AS Text
                FROM area
                LEFT OUTER JOIN report ON area.id = report.id_area
                LEFT OUTER JOIN plant_bioagressor ON plant_bioagressor.id_report = report.id
                LEFT OUTER JOIN plant ON plant_bioagressor.id_plant = plant.id
                WHERE ( ? IS NULL OR report.date > STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( ? IS NULL OR report.date < STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( plant_bioagressor.id_bioagressor = ? )
                AND ( report.id_area = ? )
                AND ( ? IS NULL OR report.content LIKE ? )
                ORDER BY plant.name";
        $results = $app['db']->fetchAll($sql, array( $dateStart, $dateStart, $dateEnd, $dateEnd, $idBioagressor, $idArea, $textLike, $textLike ) );
    }

    // Reformatage des résultats
    $res_plants = array();
    foreach( $results as $res) {
        $res_plants[] = array( "Id"=>(int) $res['Id'], "Text"=>mb_convert_encoding($res['Text'], "UTF-8") );
    }

    // Récupération des maladies de la zone
    $sql = "SELECT DISTINCT disease.id AS Id, disease.name AS Text
            FROM area
            LEFT OUTER JOIN report ON area.id = report.id_area
            LEFT OUTER JOIN plant_disease ON plant_disease.id_report = report.id
            LEFT OUTER JOIN disease ON plant_disease.id_disease = disease.id
            WHERE ( ? IS NULL OR report.date > STR_TO_DATE( ? , '%d/%m/%Y' ) )
            AND ( ? IS NULL OR report.date < STR_TO_DATE( ? , '%d/%m/%Y' ) )
            AND ( ? IS NULL OR plant_disease.id_plant = ? )
            AND ( report.id_area = ? )
            AND ( ? IS NULL OR report.content LIKE ? )
            ORDER BY disease.name";
    $results = $app['db']->fetchAll($sql, array( $dateStart, $dateStart, $dateEnd, $dateEnd, $idPlant, $idPlant, $idArea, $textLike, $textLike ) );

    // Reformatage des résultats
    $res_diseases = array();
    foreach( $results as $res) {
        $res_diseases[] = array( "Id"=>(int) $res['Id'], "Text"=>mb_convert_encoding($res['Text'], "UTF-8") );
    }

    // Récupération des nuisibles de la zone
    $sql = "SELECT DISTINCT bioagressor.id AS Id, bioagressor.name AS Text
            FROM area
            LEFT OUTER JOIN report ON area.id = report.id_area
            LEFT OUTER JOIN plant_bioagressor ON plant_bioagressor.id_report = report.id
            LEFT OUTER JOIN bioagressor ON plant_bioagressor.id_bioagressor = bioagressor.id
            WHERE ( ? IS NULL OR report.date > STR_TO_DATE(  ? , '%d/%m/%Y' ) )
            AND ( ? IS NULL OR report.date < STR_TO_DATE( ? , '%d/%m/%Y' ) )
            AND ( ? IS NULL OR plant_bioagressor.id_plant = ? )
            AND ( report.id_area = ? )
            AND ( ? IS NULL OR report.content LIKE ? )
            ORDER BY bioagressor.name";
    $results = $app['db']->fetchAll($sql, array( $dateStart, $dateStart, $dateEnd, $dateEnd, $idPlant, $idPlant, $idArea, $textLike, $textLike ) );

    // Reformatage des résultats
    $res_bugs = array();
    foreach( $results as $res) {
        $res_bugs[] = array( "Id"=>(int) $res['Id'], "Text"=>mb_convert_encoding($res['Text'], "UTF-8") );
    }

    // Calcul des occurences de la zone
    if ( ! ( is_null( $idDisease ) ) ) {
        $sql = "SELECT report.id AS Id, plant_disease.Comment AS Text, DATE_FORMAT(report.date,'%d/%m/%Y') AS Date
                FROM report
                LEFT OUTER JOIN plant_disease ON plant_disease.id_report = report.id
                WHERE ( ? IS NULL OR report.date > STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( ? IS NULL OR report.date < STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( plant_disease.id_disease = ? )
                AND ( report.id_area = ? )
                AND ( ? IS NULL OR report.content LIKE ? )
                AND ( ? IS NULL OR plant_disease.id_plant = ? )
                ORDER BY report.date";
        $results = $app['db']->fetchAll($sql, array( $dateStart, $dateStart, $dateEnd, $dateEnd, $idDisease, $idArea, $textLike, $textLike, $idPlant, $idPlant ) );
    } else {
        $sql = "SELECT report.id AS Id, plant_bioagressor.Comment AS Text, DATE_FORMAT(report.date,'%d/%m/%Y') AS Date
                FROM report
                LEFT OUTER JOIN plant_bioagressor ON plant_bioagressor.id_report = report.id
                WHERE ( ? IS NULL OR report.date > STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( ? IS NULL OR report.date < STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( plant_bioagressor.id_bioagressor = ? )
                AND ( report.id_area = ? )
                AND ( ? IS NULL OR report.content LIKE ? )
                AND ( ? IS NULL OR plant_bioagressor.id_plant = ? )
                ORDER BY report.date";
        $results = $app['db']->fetchAll($sql, array( $dateStart, $dateStart, $dateEnd, $dateEnd, $idBioagressor, $idArea, $textLike, $textLike, $idPlant, $idPlant ) );
    }
    
    // Reformatage des résultats
    $res_occ = array();
    foreach( $results as $res) {
        $res_occ[] = array( "Id"=>(int) $res['Id'], "Text"=>mb_convert_encoding($res['Text'], "UTF-8"), "Date"=>$res['Date'] );
    }

    /* En fonction des champs fournis en entrée (donc de l'état d'avancement du processus de recherche de l'utilisateur)
       on ne lui présente pas les mêmes types de résultats, même s'ils pourraient être disponible. Ce filtre pourrait
       être en amont pour limiter le nombre de requête */
    if ( ! ( is_null($idPlant) && ((!is_null($idBioagressor)) || (!(is_null($idDisease))))) )
        $res_plants = array();
    if (!((!is_null($idPlant)) && (is_null($idBioagressor)) && (is_null($idDisease)))) {
        $res_bugs = array();
        $res_diseases = array();
    }
    if (!((!is_null($idPlant)) && ((!is_null($idBioagressor)) || (!is_null($idDisease)))))
        $res_occ = array();

    // Construction de la réponse
    $response['ErrorMessage'] = null;
    $response['ErrorStackTrace'] = null;
    $response['AreaName'] = $areaname;
    $response['Bioagressors'] = $res_bugs;
    $response['Diseases'] = $res_diseases;
    $response['Id_Area'] = (int) $idArea;
    $response['Occurences'] = $res_occ;
    $response['Plants'] = $res_plants;
    return $app->json($response);
});



/*****************************************************************************************
 *                                                                                       *
 * Requête de récupération de la liste des rapports avec les filtres en place            *
 *                                                                                       *
 *****************************************************************************************/
$app->post('/Services/Vespa.svc/GetSearchReportList', function(Request $request) use ($app) {
    // Récupération des critères de recherche
    $req = json_decode( $request->getContent(), true );
    if ( ( is_null( $req ) ) || (count($req) == 0 ) ) {
        $idPlant = $request->get('Id_Plant');
        $idBioagressor = $request->get('Id_Bioagressor');
        $idDisease = $request->get('Id_Disease');
        $dateStart = $request->get('DateStart');
        $dateEnd = $request->get('DateEnd');
        $searchText = $request->get('SearchText');
    } else {
        $idPlant = $req['Id_Plant'];
        $idBioagressor = $req['Id_Bioagressor'];
        $idDisease = $req['Id_Disease'];
        $dateStart = $req['DateStart'];
        $dateEnd = $req['DateEnd'];
        $searchText = $req['SearchText'];
    }

    // Champ vide transformé en null pour utiliser le text mysql NULL IS NULL qui neutralise la valeur NULL mais pas la valeur vide
    if ( $idPlant == "" ) $idPlant = null ;
    if ( $idBioagressor == "" ) $idBioagressor = null ;
    if ( $idDisease == "" ) $idDisease = null ;

    // Petit reformatage du motif de recherche textuel
    if ( is_null( $searchText ) || $searchText == '' ) {
        $textLike = null;
    } else {
        $textLike = "%".$searchText."%";
    }

    // Construction de la requête en fonction des critères de recherche en paramètre
    if ( ! ( is_null( $idBioagressor ) || $idBioagressor == "" ) ) {
        $sql = "SELECT report.id as id, report.date as date, report.datestr as datestring, report.name as name, 
                       area.name as areaname, report.id_area as id_area, YEAR(report.date) as year
                FROM report
                INNER JOIN area ON report.id_area = area.id
                INNER JOIN plant_bioagressor ON report.id = plant_bioagressor.id_report
                WHERE ( ? IS NULL OR plant_bioagressor.id_plant = ? )
                AND ( plant_bioagressor.id_bioagressor = ? )
                AND ( ? IS NULL OR report.date > STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( ? IS NULL OR report.date < STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( ? IS NULL OR report.content LIKE ? )
                GROUP BY report.id
                ORDER BY report.date";
        $res_reports = $app['db']->fetchAll($sql, array( $idPlant, $idPlant, $idBioagressor, $dateStart, $dateStart, $dateEnd, $dateEnd, $textLike, $textLike ) );
    } else if ( ! ( is_null( $idDisease ) || $idDisease == "" ) ) {
        $sql = "SELECT report.id as id, report.date as date, report.datestr as datestring, report.name as name, 
                       area.name as areaname, report.id_area as id_area, YEAR(report.date) as year
                FROM report
                INNER JOIN area ON report.id_area = area.id
                INNER JOIN plant_disease ON report.id = plant_disease.id_report
                WHERE ( ? IS NULL OR plant_disease.id_plant = ? )
                AND ( plant_disease.id_disease = ? )
                AND ( ? IS NULL OR report.date > STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( ? IS NULL OR report.date < STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( ? IS NULL OR report.content LIKE ? )
                GROUP BY report.id
                ORDER BY report.date";
        $res_reports = $app['db']->fetchAll($sql, array( $idPlant, $idPlant, $idDisease, $dateStart, $dateStart, $dateEnd, $dateEnd, $textLike, $textLike ) );
    } else {
        $sql = "SELECT report.id as id, report.date as date, report.datestr as datestring, report.name as name, 
                       area.name as areaname, report.id_area as id_area, YEAR(report.date) as year
                FROM report
                INNER JOIN area ON report.id_area = area.id
                LEFT JOIN plant_bioagressor ON plant_bioagressor.id_plant = ? AND report.id = plant_bioagressor.id_report
                LEFT JOIN plant_disease ON plant_disease.id_plant = ? AND report.id = plant_disease.id_report
                WHERE ( plant_bioagressor.id_plant = ? OR plant_disease.id_plant = ? )
                AND ( ? IS NULL OR report.date > STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( ? IS NULL OR report.date < STR_TO_DATE( ? , '%d/%m/%Y' ) )
                AND ( ? IS NULL OR report.content LIKE ? )
                GROUP BY report.id
                ORDER BY report.date";
        $res_reports = $app['db']->fetchAll($sql, array( $idPlant, $idPlant, $idPlant, $idPlant, $dateStart, $dateStart, $dateEnd, $dateEnd, $textLike, $textLike ) );
    }

    // Reformatage des résultats
    if ( count( $res_reports ) ) {
        foreach( $res_reports as $report) {
            // Stockage des Ids pour la requête sur les années un peu plus loin
            $ids[] = (int) $report['id'];
            
            // Conversion de l'encodage
            $reports[] = array( "AreaName"=>mb_convert_encoding($report['areaname'], "UTF-8"),
                                "Date"=>mb_convert_encoding($report['date'], "UTF-8"),
                                "DateString"=>str_replace('.','/',mb_convert_encoding($report['datestring'], "UTF-8")),
                                "Id"=>(int) $report['id'],
                                "Id_Area"=>(int) $report['id_area'],
                                "Name"=>mb_convert_encoding($report['name'], "UTF-8"),
                                "Year"=>(int) $report['year'] );
        }
    } else {
        $reports = array();
        $ids = array();
    }

    // Préparation de la liste des reports ID pour la requête sur les années
    $ids = "( ".implode(",",$ids)." )";
    // Pour éviter un bug en cas de liste vide
    if ( $ids == "(  )" )
        $ids = "( '' )";

    // Comptage des reports par année
    $sql = "SELECT YEAR(report.date) AS id, YEAR(report.Date) AS text, COUNT( report.Id ) AS count
            FROM report
            WHERE id IN ".$ids."
            GROUP BY YEAR(report.date)
            ORDER BY date";
    $res_years = $app['db']->fetchAll($sql);

    // Reformatage des résultats
    if ( count( $res_years ) ) {
        foreach( $res_years as $year) {
            $years[] = array( "Id"=>(int) $year['id'],
                              "Text"=>$year['id'],
                              "Count"=>(int) $year['count'] );
        }
    } else {
        $years = array();
    }

    // Récupération des noms des critères de recherche s'ils sont présents
    if ( is_null( $idBioagressor ) || $idBioagressor == "" ) {
        $response['BioagressorName'] = null;
    } else {
        $sql = "SELECT Name FROM bioagressor WHERE id = ?";
        $res = $app['db']->fetchAssoc( $sql, array( $idBioagressor   ) );
        foreach( $res as $key=>$value )
            $response['BioagressorName'] = $value ;
    }

    if ( is_null( $idDisease ) || $idDisease == "" ) {
        $response['DiseaseName'] = null;
    } else {
        $sql = "SELECT Name FROM disease WHERE id = ?";
        $res = $app['db']->fetchAssoc( $sql, array( $idDisease ) );
        foreach( $res as $key=>$value )
            $response['DiseaseName'] = $value ;
    }

    if ( is_null( $idPlant ) || $idPlant == "" ) {
        $response['PlantName'] = null;
    } else {
        $sql = "SELECT Name FROM plant WHERE id = ?";
        $res = $app['db']->fetchAssoc( $sql, array( $idPlant ) );
        foreach( $res as $key=>$value )
            $response['PlantName'] = $value ;
    }

    // Construction du tableau retour
    $response['ErrorMessage'] = null;
    $response['ErrorStackTrace'] = null;
    $response['DateStart'] = $dateStart ;
    $response['DateEnd'] = $dateEnd ;
    $response['Reports'] = $reports;
    $response['SearchText'] = $searchText ;
    $response['Years'] = $years;

    // Conversion de la réponse en JSON et retour
    return $app->json($response);
});

/*****************************************************************************************
 *                                                                                       *
 * Transfert des documents pdf pour tracer leur telechargement.                          *
 *                                                                                       *
 *****************************************************************************************/
$app->get('/files/{path}', function ($path) use ($app) {
    if (!file_exists(__DIR__ . '/reports/' . $path)) {
        $app->abort(404, "Le fichier " . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . " n'existe pas.");
    }

    return $app->sendFile(__DIR__ . '/reports/' . $path);
});

/*****************************************************************************************
 *                                                                                       *
 * SimpleUser options. See config reference below for details.                           *
 *                                                                                       *
 *****************************************************************************************/
$app['user.options'] = array(
    'templates' => array(
        'layout' => 'layout.twig',
        'register' => 'register.twig',
        'register-confirmation-sent' => 'register-confirmation-sent.twig',
        'login' => 'login.twig',
        'login-confirmation-needed' => 'login-confirmation-needed.twig',
        'forgot-password' => 'forgot-password.twig',
        'reset-password' => 'reset-password.twig',
        'view' => 'view.twig',
        'edit' => 'edit.twig',
        'list' => 'list.twig',
        'vespa' => 'vespa.twig',
    ),

    // Configure the user mailer for sending password reset and email confirmation messages.
    'mailer' => array(
        'enabled' => true, // When false, email notifications are not sent (they're silently discarded).
        'fromEmail' => array(
            'address' => 'vespa@cortext.fr',
            'name' => 'Vespa',
        ),
    ),

    'emailConfirmation' => array(
        'required' => true, // Whether to require email confirmation before enabling new accounts.
        'template' => 'email/confirm-email.twig',
    ),

    'passwordReset' => array(
        'template' => 'email/reset-password.twig',
        'tokenTTL' => 86400, // How many seconds the reset token is valid for. Default: 1 day.
    ),

    'editCustomFields' => array(
        'region' => 'Région d\'affectation',
        'plantes' => 'Plantes',
        'maladies' => 'Maladies',
        'bioagresseurs' => 'Bio-agresseurs',
    ),

);

/*********************************************************************************************
 *                                                                                           *
 * Security config. See http://silex.sensiolabs.org/doc/providers/security.html for details. *
 *                                                                                           *
 ********************************************************************************************/
$app['security.firewalls'] = array(
    'login' => array(
        'pattern' => '^/user/login$',
        'anonymous' => true,
    ),
    'register' => array(
        'pattern' => '^/user/register$',
        'anonymous' => true,
    ),
    'forgot-password' => array(
        'pattern' => '^/user/forgot-password$',
        'anonymous' => true,
    ),
    'reset-password' => array(
        'pattern' => '^/user/reset-password/.*$',
        'anonymous' => true,
    ),
    'confirm-email' => array(
        'pattern' => '^/user/confirm-email/.*$',
        'anonymous' => true,
    ),
    'secured_area' => array(
        'pattern' => '^.*$',
        'anonymous' => false,
        'remember_me' => array(),
        'form' => array(
            'login_path' => '/user/login',
            'check_path' => '/user/login_check',
        ),
        'logout' => array(
            'logout_path' => '/user/logout',
        ),
        'users' => $app->share(function($app) { return $app['user.manager']; }),
    ),
);

/*********************************************************************************************
 *                                                                                           *
 * Définition de l'emplacement des templates pour utiliser ceux de Vespa.                    *
 *                                                                                           *
 ********************************************************************************************/
$app['twig.path'] = array(__DIR__.'/../views');

/*********************************************************************************************
 *                                                                                           *
 * Définition de la fonction de validation de la complexité du mot de passe.                 *
 *                                                                                           *
 ********************************************************************************************/
$app['user.passwordStrengthValidator'] = $app->protect(function(SimpleUser\User $user, $password) {
    if (strlen($password) < 4) {
        return 'Password must be at least 4 characters long.';
    }
    if (strtolower($password) == strtolower($user->getName())) {
        return 'Your password cannot be the same as your name.';
    }
});

/*********************************************************************************************
 *                                                                                           *
 * Mailer config. See http://silex.sensiolabs.org/doc/providers/swiftmailer.html             *
 *                                                                                           *
 ********************************************************************************************/
$app['swiftmailer.options'] = array();
$app['swiftmailer.use_spool'] = false;

/*********************************************************************************************
 *                                                                                           *
 * Database config. See http://silex.sensiolabs.org/doc/providers/doctrine.html              *
 *                                                                                           *
 ********************************************************************************************/
$app['db.options'] = array(
    'driver'   => $app['parameters']['db.options']['driver'],
    'host' => $app['parameters']['db.options']['host'],
    'dbname' => $app['parameters']['db.options']['dbname'],
    'user' => $app['parameters']['db.options']['user'],
    'password' => $app['parameters']['db.options']['password'],
    'charset' => $app['parameters']['db.options']['charset'],
);

/*********************************************************************************************
 *                                                                                           *
 * Monolog Mysql PDO                                                                         *
 *                                                                                           *
 ********************************************************************************************/
// Create new logger to MySQL
$app['mysqlog'] = new \Monolog\Logger('Vespa'); // Define the channel
$app['mysqlog']->pushHandler(
   new MySQLHandler(
     new PDO(
        'mysql:host='.$app['db.options']['host'].';dbname='.$app['db.options']['dbname'],
        $app['db.options']['user'],
        $app['db.options']['password'],
        array( PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'' )
     ),
     "log",
     // La liste doit correspondre aux champs placés dans les logs dans la fonction finish
     array('watchpoint','tag','user','session','route','parameters','type','status','response','duration','ip','msg'), 
     \Monolog\Logger::DEBUG
   )
);

/*********************************************************************************************
 *                                                                                           *
 * Création de la ligne de log pour le suivi des actions utilisateurs en se placant à la fin *
 * de la génération de la route cela permet de logger la requete aussi bien que la réponse.  *
 *                                                                                           *
 ********************************************************************************************/
$app->finish(function ($request, $response) use ($app) {
    // Définition du point de log
    $ctLog['watchpoint'] = "@".basename(__FILE__).".before.".__LINE__;
    $ctLog['tag'] = "CORTEXT-VESPA";

    // Log de l'user id si l'utilisateur est loggé, sinon on log 0
    $token=$app['security']->getToken();
    if ( $token !== null ) {
        try {
            $userId = $token->getUser()->getId();
        } catch ( Exception $e ) {
            $userId = 0;
        }
    } else {
        $userId = 0;
    }
    $ctLog['user'] = $userId ;

    // Log de l'id de session pour mieux suivre les parcours utilisateurs
    $ctLog['session'] = $app['session']->getId();

    $ctLog['route'] = $request->getPathInfo();

    $ctLog['parameters'] = $request->request->all();
    if ( is_null( $ctLog['parameters'] ) || count( $ctLog['parameters'] ) == 0 ) 
        $ctLog['parameters'] = $request->query->all();
    if ( is_null( $ctLog['parameters'] ) || count( $ctLog['parameters'] ) == 0 ) 
        $ctLog['parameters'] = json_decode( $request->getContent(), true );
    if ( is_null( $ctLog['parameters'] ) || count( $ctLog['parameters'] ) == 0 ) 
        $ctLog['parameters'] = $request->attributes->get("_route_params");
    if ( ! is_null( $ctLog['parameters'] ) && count( $ctLog['parameters'] ) > 0 )
        array_walk( $ctLog['parameters'], function( &$v, $k ){ if ( in_array( $k, array( "_password", "password", "confirm-password" ), TRUE ) ) $v = ""; });

    $ctLog['type'] = $request->getMethod();

    $ctLog['status'] = $response->getStatusCode();

    $ctLog['response'] = json_decode($response->getContent());
    if ( !$ctLog['response'] ) $ctLog['response'] = "Not JSON";

    $duration = $app['stopwatch']->stop('vespa');
    $ctLog['duration'] = $duration->getDuration();

    $ctLog['ip'] = $request->getClientIp();

    $ctLog['msg'] = "";

    // Output de la ligne de log
    $app['monolog']->addInfo( "[VESPA] ".json_encode( $ctLog, JSON_UNESCAPED_SLASHES ) );

    // Output vers mysql
    array_walk( $ctLog, function (&$v, $k){
        // Les objects provoquent une erreur et les array apparaissent comme "array" en bdd, alors on encode tout ça pour les avoir en base
        if ( in_array( gettype( $v ), array( "object", "array" ), TRUE ) ) $v = json_encode( $v, JSON_UNESCAPED_SLASHES ); 
    });
    $app['mysqlog']->addInfo( "[VESPA]", $ctLog );
});

$app->run();

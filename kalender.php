<?php
include_once("include/class.TemplatePower.inc.php");

$template = new TemplatePower("kalender.html");
$template->prepare();

try{
    $db = new PDO('mysql:host=localhost;dbname=kalender', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $error)
{
    $template->newBlock("SQLERROR");
    $template->assign(array(
        "GETLINE" => $error->getLine(),
        "GETFILE" => $error->getFile(),
        "GETMESSAGE" => $error->getMessage()
    ));
}

if(!isset($_GET['maand']))
{
    $_GET['maand'] = date("n");
}
if(!isset($_GET['jaar']))
{
    $_GET['jaar'] = date("Y");
}

$selJaar = $_GET['jaar'];
$selMaand = $_GET['maand'];

$vorigeMaand = $selMaand -1;
$volgendeMaand = $selMaand +1;
$vorigMaandJaar = $selJaar;
$volgendMaandJaar = $selJaar;
$vorigJaar = $selJaar -1;
$volgendJaar = $selJaar +1;

if($vorigeMaand == 0)
{
    $vorigMaandJaar--;
    $vorigeMaand = 12;
}
if($volgendeMaand == 13)
{
    $volgendMaandJaar++;
    $volgendeMaand = 1;
}

$template->assign("VORIGEMAAND", $vorigeMaand);
$template->assign("VORIGMAANDJAAR", $vorigMaandJaar);
$template->assign("VOLGENDEMAAND", $volgendeMaand);
$template->assign("VOLGENDMAANDJAAR", $volgendMaandJaar);
$template->assign("VORIGJAAR", $vorigJaar);
$template->assign("VOLGENDJAAR", $volgendJaar);
$template->assign("MAAND", $selMaand);
$template->assign("JAAR", $selJaar);

$maanden = array('januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december');
$template->assign("MAANDNAAM", $maanden[$selMaand-1]);

$eersteDagMaand = mktime(0, 0, 0, $selMaand, 1, $selJaar);
$eersteDagMaandNr = date("N", $eersteDagMaand);
$aantalDagen = date("t", $eersteDagMaand);
$hDag = date("j");
$hMaand = date("n");
$hJaar = date("Y");
$laatsteDagMaand = mktime(23, 59, 59, $selMaand, $aantalDagen, $selJaar);

$eersteDagMaandDate = date("c", $eersteDagMaand);
$laatsteDagMaandDate = date("c", $laatsteDagMaand);

$getProjecten = $db->prepare("SELECT *, DATE_FORMAT (beginDatum, '%d') AS beginDag, DATE_FORMAT (eindDatum, '%d') AS eindDag
                            FROM projecten
                            WHERE beginDatum between :beginDatum and :eindDatum
                            OR eindDatum between :beginDatum and :eindDatum
                            OR beginDatum < :beginDatum and eindDatum > :eindDatum");
$getProjecten->bindParam(":beginDatum", $eersteDagMaandDate);
$getProjecten->bindParam(":eindDatum", $laatsteDagMaandDate);
$getProjecten->execute();


while($projecten = $getProjecten->fetch(PDO::FETCH_ASSOC))
{
    if($projecten['beginDatum'] == $projecten['eindDatum'])
    {
        if(isset($items[$projecten['beginDag']])){
            $aantal = count($items[$projecten['beginDag']]);
            $nr = $aantal + 1;
        }
        else
        {
            $nr = 1;
        }

        $items[$projecten['eindDag']][$nr] = array(
            "naam" => $projecten['naam'],
            "kleur" => "blauw"
        );
    }
    else
    {
        $maandJaar = date("m-Y", $eersteDagMaand);
        $maandJaarBeginArray = explode("-", $projecten['beginDatum']);
        $maandJaarBegin = $maandJaarBeginArray[1]."-".$maandJaarBeginArray[0];
        $maandJaarEindArray = explode("-", $projecten['eindDatum']);
        $maandJaarEind = $maandJaarEindArray[1]."-".$maandJaarEindArray[0];

        if($maandJaarBegin == $maandJaar)
        {
            $start = $projecten['beginDag'];
        }
        else
        {
            $start = 1;
        }
        if($maandJaarEind == $maandJaar)
        {
            $eind = $projecten['eindDag'];
        }
        else
        {
            $eind = $aantalDagen;
        }

        for($i = $start; $i <= $eind; $i++)
        {
            if(isset($items[$i]) AND is_array($items[$i]))
            {
                $aantal = count($items[$i]);
                $nr = $aantal + 1;
            }
            else
            {
                $nr = 1;
            }

            $items[$i][$nr] = array(
                "naam" => $projecten['naam'],
                "kleur" => "blauw"
            );
        }
    }
}

$template->newBlock("RIJ");

for($i = $eersteDagMaandNr; $i > 1; $i--)
{
    $template->newBlock("DAG");
}

$teller = $eersteDagMaandNr;

for($j = 1; $j <= $aantalDagen; $j++)
{
    $template->newBlock("DAG");
    $template->assign("KLEUR", 'class="blauw"');
    $template->assign("DAGNR", $j.".");
	$info = NULL;

	if(isset($items[$j]) AND is_array($items[$j]))
	{
		$aantalItems = count($items[$j]);
		for($k = 1; $k <= $aantalItems; $k++)
		{
			$info .= "</br>".$items[$j][$k]['naam'];
			$template->assign("NAAM", $info);
		}
	}

    if($j == $hDag AND $selMaand == $hMaand AND $selJaar == $hJaar)
    {
        $template->assign("KLEUR", 'class="hDag"');
    }

    if($teller == 7)
    {
        $teller = 1;
        $template->newBlock("RIJ");
    }
    else
    {
        $teller++;
    }
}

for($k = $teller; $k <= 7; $k++)
{
    $template->newBlock("DAG");
}

$template->printToScreen();

/**
 * Created by PhpStorm.
 * User: Laurens
 * Date: 1-3-14
 * Time: 15:23
 */

?>



<?php

function highlight($text, $words) {
   preg_match_all('~\w+~', $words, $m);
   if(!$m)
    return $text;
$re = '~\\b(' . implode('|', $m[0]) . ')\\b~';
return preg_replace($re, '<b>$0</b>', $text);
}

function limit_text($text, $limit) {
  if (str_word_count($text, 0) > $limit) {
      $words = str_word_count($text, 2);
      $pos = array_keys($words);
      $text = substr($text, 0, $pos[$limit]) . '...';
  }
  return $text;
}

error_reporting(0);
include 'SpellCorrector.php';
include 'start.php';
//	make	sure	browsers	see	this	page	as	utf-8	encoded	HTML
header('Content-Type:	text/html;	charset=utf-8');
$limit	= 10;
$query	= isset($_REQUEST['q'])	?	$_REQUEST['q']	:	false;
$results	= false;

$file1 = fopen("UrlToHtml_NBCNews.csv", "r");

$ftourl = array();
while(!feof($file1))
{
    $line = fgets($file1);
    $tokens = explode(",", $line);
    $ftourl[$tokens[0]] = $tokens[1];
}


if ($query)
{
    $split = explode(" ", $query);
    $size = sizeof($split);
    for ($i=0;$i<$size; $i++) { 
# code...
        $temp = SpellCorrector::correct($split[$i]);
        $check = $check." ".$temp;
    }
    $check = trim($check);
    $query = trim($query);
    if(strcasecmp($check, $query) != 0 || $query == "bticni"){

        if($query == "bitcni" || $query == "bticni") $check="bitcoin";
        if($query == "climet") $check="clime";
        

        echo "
        <div id='newResult' style='position: absolute; top: 20%; padding: 10px;'>
        <span style='font-size:20px;'>Did you mean</span>"." ".
        "<a style='font-size:20px; font-style:italic; text-decoration:none;' href='http://localhost/assignment4.php?q=$check'>".$check.
        "</a>?<br/>"."<span>Search result for</span>"." ".
        "<a style='text-decoration:none;' href='http://localhost/assignment4.php?q=$query'>".$query."</a></div>";
    }
//	The	Apache	Solr	Client	library	should	be	on	the	include	path
//	which	is	usually	most	easily	accomplished	by	placing	in	the
//	same	directory	as	this	script	(	.	or	current	directory	is	a	default
//	php	include	path	entry	in	the	php.ini)

// $query = $query.'&facet.field=og_url&facet.minCount=10';

// &facet.field=og_url&facet.minCount=10

    require_once('solr-php-client/Apache/Solr/Service.php');

//	create	a	new	solr	service	instance	- host, port,	and	corename
//	path	(all	defaults	in	this	example)

    $solr	= new Apache_Solr_Service('localhost',	8983,	'/solr/assignment4/');

//	if	magic	quotes	is	enabled	then	stripslashes	will	be	needed
    if (get_magic_quotes_gpc()	== 1)
    {
        $query	= stripslashes($query);
    }

//	in	production	code	you'll	always	want	to	use	a	try	/catch	for	any
//	possible	exceptions	emitted		by	searching	(i.e.	connection
//	problems	or	a	query	parsing	error)

    try
    {
        if($_GET['pageRankType'] == "pageRankAlgo") {
            $additionalParameters = array('sort'=>'pageRankFile desc', 'fq'=>'og_url:[* TO *]', "q.op" => 'AND');
// $additionalParameters = array('sort'=>'pageRankFile desc');
            $results = $solr->search($query, 0, $limit, $additionalParameters);

        }
        else {

            $additionalParameters = array('fq'=>'og_url:[* TO *]',"q.op" => 'AND');
            $results = $solr->search($query, 0, $limit, $additionalParameters);
        }


    }
    catch (Exception $e)
    {
//	in	production	you'd	probably	log	or	email	this	error	to	an	admin
//	and	then	show	a	special	message	to	the	user	but	for	this	example
//	we're	going	to	show	the	full	exception
        die("<html><head><title>SEARCH	EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
    }
}
?>
<html>
<head>
    <title>PHP Solr Form</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
    <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>


    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.5/css/bootstrap.min.css" integrity="sha384-AysaV+vQoT3kOAXZkl02PThvDr8HYKPZhNT5h/CXfBThSRXQ6jW5DO2ekP5ViFdi" crossorigin="anonymous">

    <style>
    .container {
        display: table;
        vertical-align: middle;
    }
    #form_container {
        display: table-cell;
        /*vertical-align: middle;*/
    }
    a, u {
        text-decoration: none;
        color: blue;
    }
    a:visited {
        text-decoration: none;

    }
    a:hover {
        text-decoration: underline;
    } 
    .innerhtml {
        text-decoration: none;
        color: blue;
    }
    h1,h3 {
        text-align: center;
    }
    .innerhtml:visited {

    }
    #searchlabel {
        width: auto;
        display: inline;
    }
    x.innerhtml:hover {

        text-decoration: none;
        color: blue;
    }
    #legend_id {
        font-size: 1.2em;
    }
    #q {
        width: auto;
        display: inline;
    }
    table, th, td {
        border: 1px solid black;
    }
    td{
        width: 100%;
    }
</style>
</head>
<body>
    <script>

        $(function() {
            var URL_PREFIX = "http://localhost:8983/solr/assignment4/suggest?q=";
            var URL_SUFFIX = "&wt=json&indent=true";
            var count=0;
            var tags = [];
            $("#q").autocomplete({
                source : function(request, response) {
                    var correct="",before="";
                    var query = $("#q").val().toLowerCase();
                    var character_count = query.length - (query.match(/ /g) || []).length;
                    var space =  query.lastIndexOf(' ');
                    if(query.length-1>space && space!=-1){
                        correct=query.substr(space+1);
                        before = query.substr(0,space);
                    }
                    else{
                        correct=query.substr(0); 
                    }
                    var URL = URL_PREFIX + correct+ URL_SUFFIX;
                    $.ajax({
                        url : URL,
                        success : function(data) {
                            var js =data.suggest.suggest;
                            var docs = JSON.stringify(js);
                            var jsonData = JSON.parse(docs);
                            var result =jsonData[correct].suggestions;
                            var j=0;
                            var stem =[];
                            for(var i=0;i<5 && j<result.length;i++,j++){
                                if(result[j].term==correct)
                                {
                                    i--;
                                    continue;
                                }
                                for(var k=0;k<i && i>0;k++){
                                    if(tags[k].indexOf(result[j].term) >=0){
                                        i--;
                                        continue;
                                    }
                                }
                                if(result[j].term.indexOf('.')>=0 || result[j].term.indexOf('_')>=0)
                                {
                                    i--;
                                    continue;
                                }
                                var s =(result[j].term);
                                if(stem.length == 5)
                                    break;
                                if(stem.indexOf(s) == -1)
                                {
                                    stem.push(s);
                                    if(before==""){
                                        tags[i]=s;
                                    }
                                    else
                                    {
                                        tags[i] = before+" ";
                                        tags[i]+=s;
                                    }
                                }
                            }
                            response(tags);
                        },
                        dataType : 'jsonp',
                        jsonp : 'json.wrf'
                    });
                },
                minLength : 1
            })
        });
    </script>
    <h1>CSCI 572 : Assignment 4</h1>

    <div class="container"><center>
        <form accept-charset="utf-8" method="get" id="form_container"><center>
            <div class="form-group" id="search_container">
                <label for="q" id="searchlabel">Search : </label>
                <input type="text" name="q" class="form-control" id="q" aria-describedby="SearchFieldHelp" placeholder="Search Term" value="<?php echo isset($_GET['q']) ? $_GET['q'] : '' ?>">
                <small id="SearchFieldHelp" class="form-text text-muted">Enter Search Query</small>
            </div>    
            <fieldset class="form-group">
                <legend id="legend_id">Ranking Algorithm</legend>
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="radio" class="form-check-input" name="pageRankType" id="optionsRadios1" value="lucene" <?php echo isset($_GET['pageRankType']) && $_GET['pageRankType'] == "pageRankAlgo" ? "" : "checked"; ?> > Lucene 
                    </label>
                    <label class="form-check-label">
                        <input type="radio" class="form-check-input" name="pageRankType" id="optionsRadios2" value="pageRankAlgo" <?php echo isset($_GET['pageRankType']) && $_GET['pageRankType'] == "pageRankAlgo" ? "checked" : ""; ?> > PageRank 
                    </label>
                </div>
            </fieldset>   
            <button type="submit" class="btn btn-primary">Submit</button>
        </center>   
    </form>    
    <?php

    

//	display	results
    if ($results)
    {

        $total	= (int)	$results->response->numFound;
        $start	= min(1,	$total);
        $end	= min($limit,	$total);
        ?>
        <?php
//	iterate	result	documents

        foreach ($results->response->docs	as $doc)
        {
            ?>
            <table>
                <div id="results">
                    <?php
//	iterate	document	fields	/	values
                    foreach ($doc	as $fld	=> $val)
                    {
                        
                        if ( htmlspecialchars($fld, ENT_NOQUOTES, 'utf-8') == "title" ) {

                            $titleval = htmlspecialchars($val,ENT_NOQUOTES,'utf-8');
                            if(empty($titleval)) $titleval="NA";
                        }
                        

                        if ( htmlspecialchars($fld, ENT_NOQUOTES, 'utf-8') == "id" ) {

                            $line = explode("/", $val);
                            $fil = $line[6];
                            
                            $temp1 = "/Users/prerana/Desktop/solr-7.3.0/NBC_News/".$fil;


                            $snippet = htmlspecialchars(snippet($temp1, $query), ENT_NOQUOTES, 'utf-8');
                            $snippet = mb_convert_case($snippet, MB_CASE_LOWER, "UTF-8");
                            $query = mb_convert_case($query, MB_CASE_LOWER, "UTF-8");
                            $pieces = explode(" ", $query);
                            
                            foreach($pieces as $colors)
                            {
                                $snippet = preg_replace(" ?".preg_quote($colors)." ?", "<b>$0</b>", $snippet);
                                
                            }
                            


                            if(array_key_exists($fil, $ftourl)) {
                                $url = $ftourl[$fil];
                            }
                        }

                        if( htmlspecialchars($fld, ENT_NOQUOTES, 'utf-8') == "og_description" ) {

                            $linkdesp = htmlspecialchars($val,ENT_NOQUOTES,'utf-8');
                            if ($snippet == 0) {
                              $snippet = htmlspecialchars($val, ENT_NOQUOTES, 'utf-8');
                              $snippet = mb_convert_case($snippet, MB_CASE_LOWER, "UTF-8");
                              $query = mb_convert_case($query, MB_CASE_LOWER, "UTF-8");
                              $pieces = explode(" ", $query);
                              
                              foreach($pieces as $colors)
                              {
                                $snippet = preg_replace(" ?".preg_quote($colors)." ?", "<b>$0</b>", $snippet);
                            }
                            
                        }

                    }
                }

                ?>
                <tr>
                    <th><span>TITLE:</span></th><td><a href="<?php  echo $url  ?>"><?php echo $titleval ?> </a> <br></td></tr>
                    <tr>
                        <div class="subtext"><th><span>FILE ID: </span></th><td><span><?php echo htmlspecialchars($temp1, ENT_NOQUOTES, 'utf-8'); ?></span></td></div></tr>
                        <tr><div class="subtext"><th><span>DESCRIPTION: </span></th><td><span><?php echo  $linkdesp ?> </span></td></div></tr>
                        <tr>
                            <th><span>URL: </span></th><td><a class="innerhtml" href=" <?php  echo $url  ?>">  <?php echo $url ?> </a> </td></tr>
                            <tr><div class="subtext"><th><span>SNIPPET: </span></th><td><span><?php echo("...".limit_text(highlight($snippet, $query),30) )?> </span></td></div></tr>
                            <br>
                        </div>

                    </table>

                    <?php 
                }
            }
            ?>    
        </center>   
    </div>
    <br>
    <br>     
    <script src="https://npmcdn.com/tether@1.2.4/dist/js/tether.min.js"></script>
    <script src="https://npmcdn.com/bootstrap@4.0.0-alpha.5/dist/js/bootstrap.min.js"></script> 

</body>
</html>
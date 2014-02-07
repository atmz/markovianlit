 <?php
//DB logic
$conn = new mysqli("localhost","atmz_markov","mncu92348HDNe3");
$conn->select_db("atmz_markov");
$corpus = $conn->real_escape_string($_GET["corpus"]);
$corpusArray = explode("|", $corpus);
//$numberOfSentences = $conn->real_escape_string($_GET["numberSentences"]);
//$minWords = $conn->real_escape_string($_GET["minWords"]);
//$maxWords = $conn->real_escape_string($_GET["maxWords"]);
$numberOfSentences=10;
$minWords=5;
$maxWords=20;

function getNextWord($tuple, $conn, $corpusArray)
{
	$corpi=0;
	$corpus = $corpusArray[0];
	if(!$corpus || $corpus == "all")
	{	
		//get sum
		$query = "SELECT sum(probability) FROM n2 WHERE firstword=\"".$tuple."\"";
		if ($res = $conn->query($query)) {
			$row = $res->fetch_assoc();
			$corpi = floatval($row["sum(probability)"]);
		}
		else
		{
			echo("Error getting data from DB:". $conn->error);
		}
		if($corpi == 0)
		{
			$corpi = 1;
		}
		$query = "SELECT probability, secondword FROM n2 WHERE firstword=\"".$tuple."\" ORDER BY secondword DESC";
		if ($res = $conn->query($query)) {
			$total=0;
			$rand = mt_rand(0, mt_getrandmax() - 1) / (mt_getrandmax()/$corpi);
			//echo $tuple . "<br>";
			//echo $corpi . "<br>";
			//echo $rand . "<br>";
			while ($row = $res->fetch_assoc()) {
				//var_dump($row);
				//echo "<br>row[probability]: " .$row[probability];
				$total = $total + floatval($row[probability]);
				if($total>$rand)
				{
					return($row[secondword]);
				}
			}
		}
		else
		{
			error_log("Error getting data from DB:". $conn->error);
			return "$";
		}
	}
	else if(count($corpusArray)>1)
	{	
		$corpusString="";
		foreach ($corpusArray as $word)
		{
			$corpusString = $corpusString."\"".$word."\",";
		}
		$corpusString = substr($corpusString, 0, -1);
		//get sum
		$query = "SELECT sum(probability) FROM n2 WHERE firstword=\"".$tuple."\" and corpus IN (".$corpusString.")";
		if ($res = $conn->query($query)) {
			$row = $res->fetch_assoc();
			$corpi = floatval($row["sum(probability)"]);
		}
		else
		{
			echo("Error getting data from DB:". $conn->error);
		}
		if($corpi == 0)
		{
			$corpi = 1;
		}
		$query = "SELECT probability, secondword FROM n2 WHERE firstword=\"".$tuple."\" and corpus IN (".$corpusString.") ORDER BY secondword DESC";
		if ($res = $conn->query($query)) {
			$total=0;
			$rand = mt_rand(0, mt_getrandmax() - 1) / (mt_getrandmax()/$corpi);
			//echo $tuple . "<br>";
			//echo $corpi . "<br>";
			//echo $rand . "<br>";
			while ($row = $res->fetch_assoc()) {
				//var_dump($row);
				//echo "<br>row[probability]: " .$row[probability];
				$total = $total + floatval($row[probability]);
				if($total>$rand)
				{
					return($row[secondword]);
				}
			}
		}
		else
		{
			error_log("Error getting data from DB:". $conn->error);
			return "$";
		}
	}
	else
	{	
		$query = "SELECT probability, secondword FROM n2 WHERE firstword=\"".$tuple."\" and corpus=\"".$corpus."\" ORDER BY secondword DESC";
		$total=0;
		$rand = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
		if ($res = $conn->query($query)) {
			while ($row = $res->fetch_assoc()) {
				//var_dump($row);
				//echo "<br>row[probability]: " .$row[probability];
				$total = $total + floatval($row[probability]);
				if($total>$rand)
				{
					return($row[secondword]);
				}
			}
		}
		else
		{
			error_log("Error getting data from DB:". $conn->error);
			return "$";
		}
	}
	//echo $query."<br>";
	return "$";
	
}
$sentenceArray=array();
$i=0;
while($i<$numberOfSentences)
{
	$word="^";
	$word1="^";
	$wordCount=0;
	$sentence="";
	while($word!="$")
	{
		$tuple = $word1." ".$word;
		$word1 = $word;
		$word = getNextWord($tuple, $conn, $corpusArray);
		if($word!="$"){
			$sentence = $sentence." ".$word;
			$wordCount = $wordCount+1;
		}
		if($wordCount>20) break;
	}
	if($wordCount>=$minLength)
	{
		$sentence=str_replace(array('.'), '' , $sentence);
		$sentence=rtrim($sentence);
		$sentenceArray[$i]=$sentence.".";
		$i++;
	}
}
	echo json_encode($sentenceArray);

//get local time in server timezone (to avoid JS hackiness
//echo "var serverDatetime = new Date(\"".date("Y-m-d H:i:s")."\");";
//echo "var newArr = \"".date("Y-m-d H:i:s")."\".split(/[- :]/);\n";
//echo "var serverDatetime = new Date(arr[0], arr[1]-1, arr[2], arr[3], arr[4], arr[5]);\n";

?>

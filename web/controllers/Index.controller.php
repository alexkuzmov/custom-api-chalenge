<?php
/**
* Index Controller class
* 
* Controll class for the home page
* 
* @file			Index.controller.php
* @author		Alex Kuzmov <alexkuzmov@gmail.com>
*   	
*/
class IndexController extends MainController {
    
    private $stopWords = [
        'the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have',
        'I', 'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you',
        'do', 'at', 'this', 'but', 'his', 'by', 'from', 'they', 'we',
        'say', 'she', 'or', 'an', 'will', 'my', 'one', 'all', 'would',
        'there', 'their', 'what', 'so', 'up', 'out', 'if', 'about',
        'who', 'get', 'which', 'go', 'me', 'when',
    ];
    
	public function __construct()
	{
        parent::__construct();
	}
    
	public function indexAction()
	{
        
        if(isset($_SESSION['user'])){
            $xml = new SimpleXMLElement(file_get_contents('https://www.theregister.co.uk/software/headlines.atom'));
            $articles = [];
            $articleTexts = '';
            
            // Gather the articles and prepare string for extracting words
            foreach($xml AS $elementKey => $element){
                if($elementKey == 'entry'){
                    $articles[] = $element;
                    
                    $articleTexts .= $element->title . ' ' . $element->summary . ' ';
                }
            }
            
            $foundWords = $this->extractWords($articleTexts, $this->stopWords);
            
            $this->_smarty->assign('articles', $articles);
            $this->_smarty->assign('foundWords', $foundWords);
        }
        
        $this->_smarty->display('index.html');
	}
    
    private function extractWords($string, $stop_words = [], $max_count = 10){
        $string = preg_replace('/\s\s+/i', '', $string); // replace whitespace
        $string = trim($string); // trim the string
        $string = preg_replace('/[^a-zA-Z.\/\&nbsp\;() - ]/', '', $string); // only take alpha characters, but keep the spaces and dashes too… remove / . 
        $string = strtolower($string); // make it lowercase
        $string = strip_tags($string);

        preg_match_all('/\b.*?\b/i', $string, $matchWords);
        $matchWords = $matchWords[0];

        foreach($matchWords AS $key => $item){
            if ( $item == '' || in_array($item, $stop_words) || strlen($item) <= 3 ) {
                unset($matchWords[$key]);
            }
        }
        
        $wordCountArr = array();
        
        if(is_array($matchWords)){
            foreach($matchWords AS $key => $val){
                $val = strtolower($val);
                if (isset($wordCountArr[$val])){
                    $wordCountArr[$val]++;
                } else {
                    $wordCountArr[$val] = 1;
                }
            }
        }
        
        arsort($wordCountArr);
        $wordCountArr = array_slice($wordCountArr, 0, $max_count);
        return $wordCountArr;
    }
}
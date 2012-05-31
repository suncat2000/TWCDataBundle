<?php
namespace SunCat\TWCDataBundle\ApiData;

use Buzz\Client\FileGetContents;
use Buzz\Message\Request as BuzzRequest;
use Buzz\Message\Response as BuzzResponse;

use SunCat\Exception\TWCException;

/**
 * TWC Api Data
 *
 * @author Nikolay Ivlev
 */
class ApiData {
    
    private $apiKey;
    private $method;
    private $command;
    private $units;
    private $format;
    private $host;
    private $locale;
    private $country;
    private $enabledCountry;
    private $params;
    private $resourcePart;
    
    private $availCommands = array('locsearch', 'loc', 'trupoint_cc', 'svr', 'ss', 'df', 'dn', 'avg');
    private $availParam = array('day', 'days', 'start', 'end', 'cb');
    private $availMethods = array('GET', 'POST', 'DELETE', 'PUT');



    /**
     * Class constructor
     * @param string $apiKey
     * @param string $format
     * @param string $units
     * @param string $host
     * @param string $locale
     * @param string $country
     */
    public function __construct($apiKey, $format = 'json', $units = 'm', $host = 'http://api.theweatherchannel.com', $locale = 'en_GB', $country = 'UK')
    {
        $this->apiKey = $apiKey;
        $this->units = $units;
        $this->format = $format;
        $this->host = $host;
        $this->locale = $locale;
        $this->country = $country;
        $this->enabledCountry = true;
        $this->params = array();
        $this->method = 'GET';
    }
    
    /**
     * Set command
     * @param string $command Available: 'locsearch', 'loc', 'trupoint_cc', 'svr', 'ss', 'df', 'dn', 'avg'
     */
    public function setCommand($command)
    {
        if(!in_array($command, $this->availCommands)){
            throw new TWCException('This command not available in TWC API');
        }

        $this->command = $command;
    }

    /**
     * Set resource part (Location ID, zip code)
     * @param string $resoursePart 
     * @param bool $spacesEscaped If true escaped space symbols '%20'
     * @param bool $cleaned If true clear not valid symbols
     */
    public function setResourcePart($resoursePart, $spacesEscaped = false, $cleaned = false)
    {
        if(!$cleaned){
            $resoursePart = $this->cleanPart($resoursePart);
        }
        
        if(!$spacesEscaped){
            $resoursePart = $this->escapeSpaces($resoursePart);
        }
        
        $this->resourcePart = $resoursePart;
    }

    /**
     * Set country
     * @param string $countryCode Example: 'RS' - Russia, 'UK' - United Kingdom, 'GM' - Germany, 'US' - United States, 'FR' - France
     */
    public function setCountry($countryCode)
    {
        if(is_string($countryCode) && strlen($countryCode) == 2){
            $this->country =  strtoupper($countryCode);
        }
    }

    /**
     * Enabled/disabled country
     * @param boolean $enabled If false disabled 'country' param in query string to TWC API, else on the contrary
     */
    public function enabledCountry($enabled)
    {
        if(is_bool($enabled)){
            $this->enabledCountry = $enabled;
        } 
    }

    /**
     * Set query string parameters
     * @param array $params Available: 'day', 'days', 'start', 'end', 'cb'
     */
    public function setParams(array $params)
    {
        foreach($params as $paramsName => $paramsValue){
            if(!in_array($paramsName, $this->availParam)) unset($params[$paramsName]);
        }
        
        $this->params = $params;
    }
    
    /**
     * Set HTTP Method
     * @param string $method Available: 'GET', 'POST', 'DELETE', 'PUT'
     */
    public function setMethod($method)
    {
        if(!in_array($method, $this->availMethods)){
            throw new TWCException("This method don't support");
        }
        
        $this->method = $method;
    }
    
    /**
     * Get content format
     * @return string json|xml 
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Get data from TWC API
     * @return array|SimpleXMLElement|false If format 'json' return array, else if 'xml' return SimpleXMLElement
     */
    public function getData()
    {   
        $resource = $this->getDataResourse(); // resourse '/data/'
        
        $request = new BuzzRequest($this->method, $resource, $this->host);
        $response = new BuzzResponse();
        
        $client = new FileGetContents();
        
        // Processing get data from TWC API
        $attempt = 0;
        do {
            if($attempt){
                sleep($attempt);
            }
            
            try{
                $client->send($request, $response);
            } catch(TWCException $e){
                continue;
            }
        } while (!($response instanceof BuzzResponse) && ++$attempt < 5);
        
        // BuzzResponse is fail
        if(!($response instanceof BuzzResponse) || !$response->isOk()){
            throw new TWCException("Response to TWC API is fail.");
        }
        
        // Parse data from BuzzResponse content
        switch($this->getFormat()){
            case 'json':
                $data = json_decode($response->getContent());
                break;
            case 'xml':
                $data = simplexml_load_string($response->getContent());
                break;
            default :
                $data = false;
        }

        return $data;
    }
    
    /**
     * Get resource for BuzzRequest
     * @return string Return resource for request to TWC API
     */
    protected function getDataResourse()
    {
        if(!isset($this->command{0})){
            throw new TWCException("You must set the command! Use setCommand() method.");
        }
        
        if(!isset($this->resourcePart{0})){
            throw new TWCException("You must set location ID or zip code! Use setResourcePart() method.");
        }
        
        $paramsStr = '';
        if(count($this->params) > 0){
            foreach($this->params as $paramName => $paramValue){
                $paramsStr .= '&' . $paramName . '=' .$paramValue;
            }
        }   
        
        $countryStr = '';
        if(true === $this->enabledCountry){
            $countryStr .= '&country=' . $this->country;
        }   
        
        return  '/data/' . $this->command . '/' . $this->resourcePart . 
                '?doctype=' . $this->format . 
                '&locale=' . $this->locale . 
                '&units=' . $this->units . 
                '&apikey=' . $this->apiKey . 
                $countryStr . 
                $paramsStr;
    }
    
    /**
     * Clean resourcePart
     * @param string $partStr
     * @return string 
     */
    protected function cleanPart($partStr)
    {
        $badSymbols = array(
                "<!--",
                "-->",
                "<",
                ">",
                '"',
                '&',
                '$',
                '=',
                ';',
                '?',
                '/',
                "%22",
                "%3c",		// <
                "%253c",        // <
                "%3e", 		// >
                "%0e", 		// >
                "%28", 		// (
                "%29", 		// )
                "%2528",        // (
                "%26", 		// &
                "%24", 		// $
                "%3f", 		// ?
                "%3b", 		// ;
                "%3d"		// =
        );

        return stripslashes(str_replace($badSymbols, '', $partStr));
    }    
    
    /**
     * Escape spaces in $resourcePart
     * @param string $partStr
     * @return string
     */
    protected function escapeSpaces($partStr)
    {
        return str_replace(' ', '%20', $partStr);
    }
}

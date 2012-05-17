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
        
        $this->params = array();
        $this->method = 'GET';
    }
    
    /**
     * Set command
     * @param string $command 
     */
    public function setCommand($command)
    {
        if(!in_array($command, $this->availCommands)){
            throw new TWCException('This command not available in API');
        }

        $this->command = $command;
    }

    /**
     * Set resource part (Location ID, zip code)
     * @param string $resoursePart 
     * @param bool $urlencoded 
     */
    public function setResourcePart($resoursePart, $cleaned = false, $spaceEscaped = false)
    {
        if(!$cleaned){
            $resoursePart = $this->cleanPart($resoursePart);
        }
        
        if(!$spaceEscaped){
            $resoursePart = $this->escapeSpace($resoursePart);
        }
        
        $this->resourcePart = $resoursePart;
    }

    /**
     * Set query string parameters (day, start, end)
     * @param array $params 
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
     * @param string $method 
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
     * Get data from api wheather.com
     * @return Buzz\Message\Response 
     */
    public function getData()
    {   
        $resource = $this->getDataResourse();
        
        $request = new BuzzRequest($this->method, $resource, $this->host);
        $response = new BuzzResponse();
        
        $client = new FileGetContents();
        $client->send($request, $response);
        
        if(!$response->isOk()){
            throw new TWCException("Problems with getting data.");
        }

        return $response;
    }
    
    /**
     * Get resource for Request
     * @return string
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
        
        return  '/data/' . $this->command . '/' . $this->resourcePart . 
                '?doctype=' . $this->format . 
                '&country=' . $this->country . 
                '&locale=' . $this->locale . 
                '&units=' . $this->units . 
                '&apikey=' . $this->apiKey . 
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
                "'",
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
    protected function escapeSpace($partStr)
    {
        return str_replace(' ', '%20', $partStr);
    }
}

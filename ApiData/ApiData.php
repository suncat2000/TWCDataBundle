<?php
namespace SunCat\TWCDataBundle\ApiData;

use Buzz\Client\FileGetContents;
use Buzz\Message\Request as BuzzRequest;
use Buzz\Message\Response as BuzzResponse;

use SunCat\Exception\TWCException;

/**
 * TWC Api Data
 *
 * @author Nikolay Ivlev <sun_cat2000@mail.ru>
 */
class ApiData
{
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
     * 
     * @param string $apiKey  API key
     * @param string $format  Format json|xml
     * @param string $units   Units
     * @param string $host    HOST (http://api.theweatherchannel.com)
     * @param string $locale  Locale
     * @param string $country Country
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
     * @param string $command 
     */
    public function setCommand($command)
    {
        if (!in_array($command, $this->availCommands)) {
            throw new TWCException('This command not available in API');
        }

        $this->command = $command;
    }

    /**
     * Set resource part (Location ID, zip code)
     * 
     * @param string $resoursePart  Part of resource for query string
     * @param bool   $spacesEscaped Escaped spaces
     * @param bool   $cleaned       Cleaned
     */
    public function setResourcePart($resoursePart, $spacesEscaped = false, $cleaned = false)
    {
        if (!$cleaned) {
            $resoursePart = $this->cleanPart($resoursePart);
        }

        if (!$spacesEscaped) {
            $resoursePart = $this->escapeSpaces($resoursePart);
        }

        $this->resourcePart = $resoursePart;
    }

    /**
     * Set country
     * @param string $countryCode 
     */
    public function setCountry($countryCode)
    {
        if (is_string($countryCode) && strlen($countryCode) == 2) {
            $this->country =  strtoupper($countryCode);
        }
    }

    /**
     * Enabled/disabled country
     * @param boolean $enabled 
     */
    public function enabledCountry($enabled)
    {
        if (is_bool($enabled)) {
            $this->enabledCountry = $enabled;
        }
    }

    /**
     * Set query string parameters (day, start, end)
     * @param array $params 
     */
    public function setParams(array $params)
    {
        foreach ($params as $paramsName => $paramsValue) {
            if (!in_array($paramsName, $this->availParam)) {
                unset($params[$paramsName]);
            }
        }

        $this->params = $params;
    }

    /**
     * Set HTTP Method
     * @param string $method 
     */
    public function setMethod($method)
    {
        if (!in_array($method, $this->availMethods)) {
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

        try {
            $client->send($request, $response);

            if ($response->isOk()) {
                return $response;
            }
        } catch (TWCException $e) {
            return null;
        }

        return null;
    }

    /**
     * Get resource for Request
     * @return string
     */
    protected function getDataResourse()
    {
        if (!isset($this->command{0})) {
            throw new TWCException("You must set the command! Use setCommand() method.");
        }

        if (!isset($this->resourcePart{0})) {
            throw new TWCException("You must set location ID or zip code! Use setResourcePart() method.");
        }

        $paramsStr = '';
        if (count($this->params) > 0) {
            foreach ($this->params as $paramName => $paramValue) {
                $paramsStr .= '&' . $paramName . '=' .$paramValue;
            }
        }

        $countryStr = '';
        if (true === $this->enabledCountry) {
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
     * 
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
     * 
     * @return string
     */
    protected function escapeSpaces($partStr)
    {
        return str_replace(' ', '%20', $partStr);
    }
}

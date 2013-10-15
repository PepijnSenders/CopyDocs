<?php

namespace Pepijnsenders\Copydocs;

use Illuminate\Support\ServiceProvider as ServiceProvider;

use \OAuth\ServiceFactory as ServiceFactory;
use \OAuth\Common\Consumer\Credentials as Credentials;
use \OAuth\Common\Storage\Session as Session;

use \URL as URL;

class Copydocs {

    private $service;

    public function __construct ($clientId, $clientSecret, $scope = array())
    {
        $serviceFactory = new ServiceFactory();

        $credentials = new Credentials(
            $clientId,
            $clientSecret,
            URL::current()
        );

        $this->service = $serviceFactory->createService('Google', $credentials, new Session(), $scope);
        return $this;
    }

    public function getArray ($fileId, $delimiter = ",", $skipEmptyLines = true, $trimFields = true)
    {
        return $this->parseCsv($this->getCsv($fileId), $delimiter, $skipEmptyLines, $trimFields);
    }

    public function getCsv ($fileId)
    {
        try
        {
            return $this->service->request("https://docs.google.com/feeds/download/spreadsheets/Export?key=$fileId&exportFormat=csv");
        }
        catch (Exception $e)
        {
            throw new Exception('Forbidden');
        }
    }

    public function getFileList ()
    {
        try
        {
            return json_decode($this->service->request("https://www.googleapis.com/drive/v2/files"), true);
        }
        catch (Exception $e)
        {
            throw new Exception('Forbidden');
        }
    }

    public function getService ()
    {
        return $this->service;
    }

    private function parseCsv ($csvString, $delimiter = ",", $skipEmptyLines = true, $trimFields = true)
    {
        $enc = preg_replace('/(?<!")""/', '!!Q!!', $csvString);
        $enc = preg_replace_callback(
            '/"(.*?)"/s',
            function ($field)
            {
                return urlencode(utf8_encode($field[1]));
            },
            $enc
        );
        $lines = preg_split($skipEmptyLines ? ($trimFields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', $enc);
        $isFirstLine = true;
        $result = array();
        foreach ($lines as $line)
        {
            $fields = $trimFields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line);
            if (true === $isFirstLine)
            {
                $isFirstLine = false;
                $keys = $fields;
            }
            else
            {
                $row = array();
                foreach ($keys as $index => $key)
                {
                    $row[$key] = $fields[$index];
                }
                $result[] = $row;
            }
        }
        return $result;
    }
}

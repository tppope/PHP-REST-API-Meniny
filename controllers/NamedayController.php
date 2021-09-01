<?php
require_once "/home/xpopikt/public_html/nameday/controllers/DatabaseController.php";
require_once "/home/xpopikt/public_html/nameday/models/Event.php";
require_once "/home/xpopikt/public_html/nameday/models/Country.php";

class NamedayController extends DatabaseController
{
    public function getName($date, $countryCode): array
    {
        $statement = $this->mysqlDatabase->prepareStatement("SELECT RECORD.calendar_value
                                                                    FROM EVENT
                                                                    INNER JOIN RECORD ON EVENT.record_id = RECORD.id
                                                                    INNER JOIN COUNTRY ON EVENT.country_id = COUNTRY.id
                                                                    INNER JOIN DAY ON EVENT.day_id = DAY.id
                                                                    WHERE DAY.date LIKE :date AND COUNTRY.code LIKE :countryCode
                                                                    ORDER BY RECORD.calendar_value");

        try {
            $statement->bindValue(':date', ($date =='%'?$date:$this->getMysqlDateFormat($date)), PDO::PARAM_STR);
            $statement->bindValue(':countryCode', $countryCode, PDO::PARAM_STR);
            $statement->execute();
            $names = $statement->fetchAll(PDO::FETCH_COLUMN);
            return array("names" => $names);
        } catch (Exception $exception) {
            http_response_code(404);
            return array("error_message" => $exception->getMessage());
        }
    }

    public function getDate($name, $countryCode): array
    {
        $statement = $this->mysqlDatabase->prepareStatement("SELECT DISTINCT DAY.date
                                                                    FROM EVENT
                                                                    INNER JOIN RECORD ON EVENT.record_id = RECORD.id
                                                                    INNER JOIN COUNTRY ON EVENT.country_id = COUNTRY.id
                                                                    INNER JOIN DAY ON EVENT.day_id = DAY.id
                                                                    WHERE RECORD.calendar_value LIKE :name AND COUNTRY.code LIKE :countryCode
                                                                    ORDER BY DAY.date");

        try {
            $statement->bindValue(':name', $name, PDO::PARAM_STR);
            $statement->bindValue(':countryCode', $countryCode, PDO::PARAM_STR);
            $statement->execute();
            $date = $statement->fetchAll(PDO::FETCH_COLUMN);
            return array("date" => $this->getOurDateFormat($date));
        } catch (Exception $exception) {
            http_response_code(404);
            return array("error_message" => $exception->getMessage());
        }
    }

    public function getCountries(): array{
        $statement = $this->mysqlDatabase->prepareStatement("SELECT COUNTRY.code, COUNTRY.name
                                                                    FROM COUNTRY");

        try {
            $statement->setFetchMode(PDO::FETCH_CLASS, "Country");
            $statement->execute();
            $countries = $statement->fetchAll();
            return array("countries" => $countries);
        } catch (Exception $exception) {
            http_response_code(404);
            return array("error_message" => $exception->getMessage());
        }
    }

    public function getRecords($countryCode, $type): object
    {
        $statement = $this->mysqlDatabase->prepareStatement("SELECT DAY.date AS date, RECORD.calendar_value AS name
                                                                    FROM EVENT
                                                                    INNER JOIN RECORD ON EVENT.record_id = RECORD.id
                                                                    INNER JOIN COUNTRY ON EVENT.country_id = COUNTRY.id
                                                                    INNER JOIN DAY ON EVENT.day_id = DAY.id
                                                                    WHERE EVENT.type= :type AND COUNTRY.code = :countryCode
                                                                    ORDER BY DAY.date");

        try {
            $statement->bindValue(':type', $type, PDO::PARAM_STR);
            $statement->bindValue(':countryCode', $countryCode, PDO::PARAM_STR);
            $statement->setFetchMode(PDO::FETCH_CLASS, "Event");
            $statement->execute();
            $holidays = $statement->fetchAll();
            return (object)array($type."s"=>$this->convertRecordDate($holidays));
        } catch (Exception $exception) {
            http_response_code(404);
            return (object)array("error_message" => $exception->getMessage());
        }
    }
    public function getCountryEvent($countryCode): object
    {
        try {
            $country = new Country();
            $country->setNames($this->getRecords($countryCode,'name')->names);
            $country->setHolidays($this->getRecords($countryCode,'holiday')->holidays);
            $country->setMemorables($this->getRecords($countryCode,'memorable')->memorables);
            return $country;
        } catch (Exception $exception) {
            http_response_code(404);
            return (object)array("error_message" => $exception->getMessage());
        }
    }
    public function getAll(): object|array
    {
        try {
            $countries = $this->getCountries();
            foreach ($countries["countries"] as $country){
                $countryData = $this->getCountryEvent($country->getCode());
                $country->setNames($countryData->getNames());
                $country->setHolidays($countryData->getHolidays());
                $country->setMemorables($countryData->getMemorables());
            }
            return $countries;
        } catch (Exception $exception) {
            http_response_code(404);
            return (object)array("error_message" => $exception->getMessage());
        }
    }

    public function addNameOnDate($name,$date,$countryCode): object
    {
        try {
            if ($name&&$date){
                $countryId = $this->getCountryId($countryCode);
                $dateId = $this->getDayId($this->getMysqlDateFormat($date));
                $nameId = $this->insertRecord($name);
                if (!$dateId)
                    throw new Exception("Date doesn't exist");
                $statement = $this->mysqlDatabase->prepareStatement("INSERT INTO EVENT (day_id,country_id,record_id,type)
                                                                    VALUES (:dayId,:countryId,:recordId,:type)");
                $statement->bindValue(':type', "name", PDO::PARAM_STR);
                $statement->bindValue(':dayId', $dateId, PDO::PARAM_INT);
                $statement->bindValue(':countryId', $countryId, PDO::PARAM_INT);
                $statement->bindValue(':recordId', $nameId, PDO::PARAM_INT);
                $statement->execute();
                return (object)array("insertedId"=>$this->mysqlDatabase->getConnection()->lastInsertId());
            }
            else
                throw new Exception("Bad POST body format");
        } catch (Exception $exception) {
            if ($exception->getMessage() == "name already exist")
                http_response_code(409);
            else
                http_response_code(404);
            return (object)array("error_message" => $exception->getMessage());
        }
    }
    /**
     * @throws Exception
     */
    public function getMysqlDateFormat($date): string
    {
        if (substr_count($date, '.') != 2)
            throw new Exception("Bad input date format");
        $dateArray = explode('.', $date);
        return "0004-" . $this->getMonthDayMysqlFormat($dateArray[1]) . '-' . $this->getMonthDayMysqlFormat($dateArray[0]);
    }
    public function getMonthDayMysqlFormat($format): string
    {
        if (strlen($format)==2)
            return $format;
        return '0'.$format;
    }

    public function getOurDateFormat($date): string|array
    {
        $newDateArray = array();
        foreach ($date as $day){
            $dateArray = explode('-', $day);
            array_push($newDateArray, ltrim($dateArray[2], '0') . "." . ltrim($dateArray[1], '0') . ".");
        }
        if (sizeof($newDateArray) == 1)
            return $newDateArray[0];
        return $newDateArray;
    }
    public function getCountryId($countryCode)
    {
        $statement = $this->mysqlDatabase->prepareStatement("SELECT COUNTRY.id FROM COUNTRY WHERE COUNTRY.code = :code");
        $statement->bindValue(':code', $countryCode, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetchColumn();
    }
    public function getDayId($day)
    {
        $statement = $this->mysqlDatabase->prepareStatement("SELECT DAY.id FROM DAY WHERE DAY.date = :day");
        $statement->bindValue(':day', $day, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetchColumn();
    }

    public function  convertRecordDate($holidays): array
    {
        foreach ($holidays as $holiday)
            $holiday->setDate($this->getOurDateFormat((array($holiday->getDate()))));
        return $holidays;
    }

    /**
     * @throws Exception
     */
    public function insertRecord($record){
        try {
            $recordId = $this->getRecord($record);
            if ($recordId > -1){
                if ($this->getCountryCodeByRecordId($recordId) == 'SK')
                    throw new Exception("name already exist");
                else
                    return $recordId;
            }
            $statement = $this->mysqlDatabase->prepareStatement("INSERT INTO RECORD (calendar_value)
                                                                        VALUES (:calendar_value)");
            $statement->bindValue(':calendar_value', $record, PDO::PARAM_STR);
            $statement->execute();
            return $this->mysqlDatabase->getConnection()->lastInsertId();
        } catch (PDOException $PDOException) {
            throw new Exception("name already exist");
        }
    }
    public function getRecord($record)
    {
        $statement = $this->mysqlDatabase->prepareStatement("SELECT RECORD.id, RECORD.calendar_value FROM RECORD WHERE RECORD.calendar_value = :calendar_value");
        $statement->bindValue(':calendar_value', $record, PDO::PARAM_STR);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $statement->execute();
        $response = $statement->fetchAll();
        foreach ($response as $item){
            if (strcmp($item["calendar_value"],$record)==0)
                return $item["id"];
        }
        return -1;
    }

    public function getCountryCodeByRecordId($recordId){
        $statement = $this->mysqlDatabase->prepareStatement("SELECT COUNTRY.code FROM EVENT INNER JOIN COUNTRY on EVENT.country_id = COUNTRY.id WHERE EVENT.record_id = :recordId");
        $statement->bindValue(':recordId', $recordId, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetchColumn();
    }
}

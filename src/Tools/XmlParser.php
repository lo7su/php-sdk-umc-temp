<?php
/**
 * ==================================================
 * Developer: Alexey Nazarov
 * E-mail: jc1988x@gmail.com
 * Copyright (c) 2019 - 2022
 * ==================================================
 * bit-umc-php-sdk - XmlParser.php
 * 04.08.2022 22:08
 * ==================================================
 */

namespace ANZ\BitUmc\SDK\Tools;

use ANZ\BitUmc\SDK\Core\Reusable\Singleton;
use Exception;
use SimpleXMLElement;

/**
 * Class XmlParser
 * @package ANZ\BitUmc\SDK\Tools
 *
 * @method static XmlParser getInstance()
 */
class XmlParser
{
    use Singleton;

    const EMPTY_SPECIALTY_NAME = 'Без основной специализации';

    /**
     * @param SimpleXMLElement $xml
     * @return array
     */
    public function xmlToArray(SimpleXMLElement $xml): array
    {
        return json_decode(json_encode($xml), true);
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return array
     */
    public function prepareClinicData(SimpleXMLElement $xml): array
    {
        
        $xmlArr = $this->xmlToArray($xml);

        $clinicKey      = "Клиника";
        $clinicTitleKey = "Наименование";
        $clinicUidKey   = "УИД";

        $clinics = [];
        if (is_array($xmlArr[$clinicKey]))
        {
            if (Utils::is_assoc($xmlArr[$clinicKey]))
            {
                $clinics[$xmlArr[$clinicKey][$clinicUidKey]] = [
                    'uid' => $xmlArr[$clinicKey][$clinicUidKey],
                    'name' => $xmlArr[$clinicKey][$clinicTitleKey]
                ];
            }
            else
            {
                foreach ($xmlArr[$clinicKey] as $item) {
                    $clinic = [];
                    $clinic['uid'] = $item[$clinicUidKey];
                    $clinic['name'] = $item[$clinicTitleKey];
                    $clinics[$item[$clinicUidKey]] = $clinic;
                }
            }
        }
        return $clinics;
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return array
     */
    public function prepareEmployeesData(SimpleXMLElement $xml): array
    {

        //echo json_encode($xml);

        
        $xmlArr = $this->xmlToArray($xml);

        $employeeKey     = "Сотрудник";
        $organizationKey = "Организация";
        $nameKey         = "Имя";
        $lastNameKey     = "Фамилия";
        $middleNameKey   = "Отчество";
        $photoKey        = "Фото";
        $descriptionKey  = "КраткоеОписание";
        $specialtyKey    = "Специализация";
        $servicesKey     = "ОсновныеУслуги";
        $oneServiceKey   = "ОсновнаяУслуга";
        $durationKey     = "Продолжительность";
        $ratingKey       = "СреднийРейтинг";

        $employees = [];
        if (is_array($xmlArr[$employeeKey]))
        {
            foreach ($xmlArr[$employeeKey] as $item)
            {
                $employee = [];
                $clinicUid = ($item[$organizationKey] == "00000000-0000-0000-0000-000000000000") ? "" : $item[$organizationKey];
                $uid = is_array($item['UID']) ? current($item['UID']) : $item['UID'];

                $specialtyName = !empty($item[$specialtyKey]) ? $item[$specialtyKey] : static::EMPTY_SPECIALTY_NAME;
                $specialtyUid  = $this->getSpecialtyUid($specialtyName);

                $employee['uid']          = $uid;
                $employee['name']         = $item[$nameKey];
                $employee['surname']      = $item[$lastNameKey];
                $employee['middleName']   = $item[$middleNameKey];
                $employee['fullName']     = $item[$lastNameKey] ." ". $item[$nameKey] ." ". $item[$middleNameKey];
                $employee['clinicUid']    = $clinicUid;
                $employee['photo']        = $item[$photoKey];
                $employee['description']  = !empty($item[$descriptionKey]) ? $item[$descriptionKey] : '';
                $employee['rating']       = $item[$ratingKey];
                $employee['specialtyName']= $specialtyName;
                $employee['specialtyUid'] = $specialtyUid;
                $employee['services']     = [];
                $employee['specialties']  = (is_string($item[$specialtyKey])) ? $this->convertStringToObject($item[$specialtyKey]) :  '';

                if (is_array($item[$servicesKey][$oneServiceKey]))
                {
                    foreach ($item[$servicesKey] as $service)
                    {
                        if (!empty($service['UID']))
                        {
                            $employee['services'][$service['UID']] = [
                                'uid'              => $service['UID'],
                                'personalDuration' => strtotime($service[$durationKey])-strtotime('0001-01-01T00:00:00')
                            ];
                        }
                    }
                }

                $employees[$uid] = $employee;
            }
        }
        
        
        return $employees;
    }

    private function convertStringToObject($str) {
        // Удалить первую запятую, если она есть
        if (strpos($str, ',') === 0) {
            $str = substr($str, 1);
        }
    
        // Разделение строки по запятым
        $items = explode(',', $str);
    
        // Создание массива объектов
        $result = [];
        foreach ($items as $item) {
            
            
            $result[$this->getSpecialtyUid($item)] = ['name' => $item];
        }
    
        return $result;
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return array
     */
    public function prepareNomenclatureData(SimpleXMLElement $xml): array
    {
        $xmlArr = $this->xmlToArray($xml);

        $catalogKey     = "Каталог";
        $isFolderKey    = "ЭтоПапка";
        $titleKey       = "Наименование";
        $typeKey        = "Вид";
        $artNumberKey   = "Артикул";
        $priceKey       = "Цена";
        $durationKey    = "Продолжительность";
        $measureUnitKey = "БазоваяЕдиницаИзмерения";
        $parent         = "Родитель";

        $nomenclature = [];
        if (is_array($xmlArr[$catalogKey]))
        {
            foreach ($xmlArr[$catalogKey] as $item)
            {
                if ($item[$isFolderKey] === true){
                    continue;
                }
                $uid = is_array($item['UID']) ? current($item['UID']) : $item['UID'];

                $product = [];
                $product['uid']         = $uid;
                $product['name']        = $item[$titleKey];
                $product['typeOfItem']  = $item[$typeKey];
                $product['artNumber']   = !empty($item[$artNumberKey]) ? $item[$artNumberKey] : '';
                $product['price']       = str_replace("[^0-9]", '', $item[$priceKey]);
                $product['duration']    = DateTime::formatDurationFromIsoToSeconds($item[$durationKey]);
                $product['measureUnit'] = !empty($item[$measureUnitKey]) ? $item[$measureUnitKey] : '';
                $product['specialtyUid']= (is_string($item[$parent])) ? $this->getSpecialtyUid($item[$parent]) :  'Без основной специализации';
                $nomenclature[$uid]     = $product;
            }
        }

        return $nomenclature;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return array
     * @throws \Exception
     */
    public function prepareScheduleData(SimpleXMLElement $xml): array
    {
        $xmlArr           = $this->xmlToArray($xml);
        $scheduleKey      = 'ГрафикДляСайта';
        $scheduleErrorKey = 'ОшибкаПараметров';

        if (array_key_exists($scheduleErrorKey, $xmlArr)){
            throw new Exception((string)$xmlArr[$scheduleErrorKey]);
        }

        $schedule = [];
        if (is_array($xmlArr[$scheduleKey])){
            $schedule = $this->processScheduleData($xmlArr[$scheduleKey]);
        }
        array_shift($schedule);
        return $schedule;
    }

    /**
     * @param array $schedule
     * @return array
     */
    protected function processScheduleData(array $schedule): array
    {
        if (Utils::is_assoc($schedule))
        {
            $schedule = [$schedule];
        }

        $employeeUidKey       = "СотрудникID";
        $employeeFullNameKey  = "СотрудникФИО";
        $scheduleDurationKey  = "ДлительностьПриема";
        $schedulePeriodsKey   = "ПериодыГрафика";
        $scheduleOnePeriodKey = "ПериодГрафика";
        $scheduleFreeTimeKey  = "СвободноеВремя";
        $scheduleBusyTimeKey  = "ЗанятоеВремя";
        $specialtyKey         = "Специализация";
        $clinicKey            = "Клиника";

        $formattedSchedule = [];
        foreach ($schedule as $item)
        {
            if (!empty($item[$clinicKey]))
            {
                $clinicUid = $item[$clinicKey];
                if (!is_array($formattedSchedule[$clinicUid]))
                {
                    $formattedSchedule[$clinicUid] = [];
                }

                $specialtyName = !empty($item[$specialtyKey]) ? $item[$specialtyKey] : static::EMPTY_SPECIALTY_NAME;
                $specialtyUid  = $this->getSpecialtyUid($specialtyName);

                if (!is_array($formattedSchedule[$clinicUid][$specialtyUid]))
                {
                    $formattedSchedule[$clinicUid][$specialtyUid] = [];
                }

                if (!empty($item[$employeeUidKey]))
                {
                    $employeeUid     = $item[$employeeUidKey];
                    $employeeName    = $item[$employeeFullNameKey];

                    $durationSeconds = 1200;//default duration = 30min
                    $durationFrom1C  = '';
                    if (!empty($item[$scheduleDurationKey]))
                    {
                        $durationFrom1C  = $item[$scheduleDurationKey];
                        $durationSeconds = intval(date("H", strtotime($durationFrom1C))) * 3600
                            + intval(date("i", strtotime($durationFrom1C))) * 60;
                    }

                    if (empty($formattedSchedule[$employeeUid]))
                    {
                        $formattedSchedule[$employeeUid] = [
                            'clinicUid' => $clinicUid,
                            'refUid' => $employeeUid,
                            'specialtyName'     => $specialtyName,
                            'employeeName'      => $employeeName,
                            'duration'    => $durationFrom1C,
                            'durationInSeconds' => $durationSeconds,
                            'timetable'         => [
                                'free' => [],
                                'busy'          => [],
                                'freeNotFormatted'          => [],
                            ]
                        ];
                    }

                    $timetable = [];

                    $freeTime = (is_array($item[$schedulePeriodsKey][$scheduleFreeTimeKey]) && count($item[$schedulePeriodsKey][$scheduleFreeTimeKey]) > 0)
                        ? $item[$schedulePeriodsKey][$scheduleFreeTimeKey][$scheduleOnePeriodKey] : [];
                    $busyTime = (is_array($item[$schedulePeriodsKey][$scheduleBusyTimeKey]) && count($item[$schedulePeriodsKey][$scheduleBusyTimeKey]) > 0)
                        ? $item[$schedulePeriodsKey][$scheduleBusyTimeKey][$scheduleOnePeriodKey] : [];

                    if (Utils::is_assoc($freeTime)) {
                        $freeTime = [$freeTime];
                    }
                    if (Utils::is_assoc($busyTime)) {
                        $busyTime = [$busyTime];
                    }

                    $timetable["freeNotFormatted"] = array_merge(
                        $formattedSchedule[$employeeUid]['timetable']["freeNotFormatted"],
                        $this->formatTimetable($freeTime, $durationSeconds, true)
                    );
                    $timetable["busy"] = array_merge(
                        $formattedSchedule[$employeeUid]['timetable']["busy"],
                        $this->formatTimetable($busyTime, $durationSeconds, true)
                    );
                    $timetable["free"] = array_merge(
                        $formattedSchedule[$employeeUid]['timetable']["free"],
                        $this->formatTimetable($freeTime, $durationSeconds, false)
                    );

                    $formattedSchedule[$employeeUid]['timetable'] = $timetable;
                }
            }
        }

        return $formattedSchedule;
    }

    /**
     * @param $array
     * @param int $duration
     * @param false $useDefaultInterval
     * @return array
     */
    public function formatTimetable($array, int $duration, $useDefaultInterval = false): array
    {
        if (!is_array($array) || empty($array)){
            return [];
        }

        if (!$duration > 0){
            $duration = 1800;
        }

        if (!empty($array))
        {
            if (Utils::is_assoc($array)) {
                $array = [$array];
            }

            $scheduleDateKey  = "Дата";
            $scheduleStartKey = "ВремяНачала";
            $scheduleEndKey   = "ВремяОкончания";

            $formattedArray = [];
            foreach ($array as $item)
            {
                $formattedDateKey = date("d-m-Y", strtotime($item[$scheduleDateKey]));

                $timestampTimeBegin = strtotime($item[$scheduleStartKey]);
                $timestampTimeEnd = strtotime($item[$scheduleEndKey]);

                if ($useDefaultInterval)
                {
                    $newTimeTableItem = $this->formatTimeTableItem($item, (int)$timestampTimeBegin, (int)$timestampTimeEnd);
                    array_push($formattedArray, $newTimeTableItem); // Используйте array_push вместо установки ключа
                }
                else
                {
                    $timeDifference = $timestampTimeEnd - $timestampTimeBegin;
                    $appointmentsCount = round($timeDifference / $duration);

                    for ($i = 0; $i < $appointmentsCount; $i++)
                    {
                        $start = $timestampTimeBegin + ($duration * $i);
                        $end = $timestampTimeBegin + ($duration * ($i+1));

                        $newTimeTableItem = $this->formatTimeTableItem($item, (int)$start, (int)$end);
                        array_push($formattedArray, $newTimeTableItem); // Используйте array_push вместо установки ключа
                    }
                }
            }
            return $formattedArray;
        }
        else
        {
            return [];
        }
    }

    /**
     * @param array $item
     * @param int $start
     * @param int $end
     * @return array
     */
    public function formatTimeTableItem(array $item, int $start, int $end): array
    {
        $scheduleDateKey     = "Дата";
        $scheduleTimeTypeKey = "ВидВремени";

        return [
            "typeOfTimeUid" => !empty($item[$scheduleTimeTypeKey]) ? $item[$scheduleTimeTypeKey] : '',
            "date" => $item[$scheduleDateKey],
            "timeBegin" => date("Y-m-d", $start) ."T". date("H:i:s", $start),
            "timeEnd" => date("Y-m-d", $end) ."T". date("H:i:s", $end),
            "formattedDate" => date("d-m-Y", strtotime($item[$scheduleDateKey])),
            "formattedTimeBegin" => date("H:i", $start),
            "formattedTimeEnd" => date("H:i", $end),
        ];
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return array|string[]
     * @throws \Exception
     */
    public function prepareReserveResultData(SimpleXMLElement $xml): array
    {
        $xmlArr = $this->xmlToArray($xml);

        $commonResKey        = 'Результат';
        $commonErrDescKey    = 'ОписаниеОшибки';
        $commonBookingUidKey = 'УИД';

        if ($xmlArr[$commonResKey] === "true" && !empty($xmlArr[$commonBookingUidKey]))
        {
            return [
                'uid'  => $xmlArr[$commonBookingUidKey]
            ];
        }
        else {
            throw new Exception((string)$xmlArr[$commonErrDescKey]);
        }
    }

    /**
     * Parse result for add order, delete order and add wait list requests
     * @param \SimpleXMLElement $xml
     * @return array
     * @throws \Exception
     */
    public function prepareCommonResultData(SimpleXMLElement $xml): array
    {
        $xmlArr = $this->xmlToArray($xml);
        $commonResKey     = "Результат";
        $commonErrDescKey = "ОписаниеОшибки";

        if ($xmlArr[$commonResKey] === "true"){
            return ['success' => true];
        }
        else {
            throw new Exception((string)$xmlArr[$commonErrDescKey]);
        }
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return array
     * @throws \Exception
     */
    public function prepareStatusResultData(SimpleXMLElement $xml): array
    {
        $xmlArr = $this->xmlToArray($xml);

        $commonResKey       = "Результат";
        $commonResDescKey   = "ОписаниеРезультата";
        $commonErrDescKey   = "ОписаниеОшибки";
        $reservedStatusText = "Забронирована";

        if ((int)$xmlArr[$commonResKey] > 0)
        {
            $statusCode  = $xmlArr[$commonResKey];
            $statusTitle = ((int)$statusCode === 9) ? $reservedStatusText : $xmlArr[$commonResDescKey];

            return [
                'statusId'  => $xmlArr[$commonResKey],
                'status'    => (is_array($statusTitle)) ? implode("; ", $statusTitle) : $statusTitle,
            ];
        }
        else {
            throw new Exception($xmlArr[$commonResKey] ." - ". $xmlArr[$commonErrDescKey]);
        }
    }

    /**
     * @param string|null $specialtyName
     * @return string
     */
    protected function getSpecialtyUid(?string $specialtyName): string
    {
        return !empty($specialtyName) ? preg_replace("/[^a-z0-9\s]/", '', strtolower(base64_encode($specialtyName))) : '';
    }
}
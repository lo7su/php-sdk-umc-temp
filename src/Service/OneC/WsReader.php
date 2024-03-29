<?php
/**
 * ==================================================
 * Developer: Alexey Nazarov
 * E-mail: jc1988x@gmail.com
 * Copyright (c) 2019 - 2022
 * ==================================================
 * bit-umc-php-sdk - WsReader.php
 * 04.08.2022 01:43
 * ==================================================
 */


namespace ANZ\BitUmc\SDK\Service\OneC;

use ANZ\BitUmc\SDK\Core\Operation\Result;
use ANZ\BitUmc\SDK\Core\Soap\SoapMethod;
use ANZ\BitUmc\SDK\Tools\DateTime;
use ANZ\BitUmc\SDK\Tools\Utils;
use ANZ\BitUmc\SDK\Service\OneC\Common;
/**
 * Class WsReader
 * @package ANZ\BitUmc\SDK\Service\OneC
 */
class WsReader extends Common
{
    /**
     * @return \ANZ\BitUmc\SDK\Core\Operation\Result
     */
    public function getClinics(): Result
    {
        return $this->getResponse(SoapMethod::CLINIC_ACTION_1C);
    }

    /**
     * @return \ANZ\BitUmc\SDK\Core\Operation\Result
     */
    public function getEmployees(): Result
    {
        return $this->getResponse(SoapMethod::EMPLOYEES_ACTION_1C);
    }

    /**
     * @param string $clinicGuid
     * @return \ANZ\BitUmc\SDK\Core\Operation\Result
     */
    public function getNomenclature(string $clinicGuid): Result
    {
        $params = [
            'Clinic' => $clinicGuid,
            'Params' => []
        ];
        return $this->getResponse(SoapMethod::NOMENCLATURE_ACTION_1C, $params);
    }

    /**
     * @param int $days
     * @param string $clinicGuid
     * @param array $employees
     * @return \ANZ\BitUmc\SDK\Core\Operation\Result
     */
    public function getSchedule(int $days = 14, string $clinicGuid = '', array $employees = []): Result
    {
        $period = $this->getIntervalParams($days);
        $params = array_merge($period, [
            'Params' => [
                'Clinic' => $clinicGuid,
                'Employees' => $employees
            ]
        ]);
        return $this->getResponse(SoapMethod::SCHEDULE_ACTION_1C, $params);
    }

    /**
     * creates array of date interval
     * @param int $intervalDays
     * @return array
     */
    protected function getIntervalParams(int $intervalDays): array
    {
        $start  = DateTime::formatTimestampToISO(strtotime('today + 5 hours'));
        $end    = DateTime::formatTimestampToISO(strtotime('today + ' . $intervalDays . ' days'));
        return [
            "StartDate" => $start,
            "FinishDate" => $end,
        ];
    }

    /**
     * @param string $orderUid
     * @return \ANZ\BitUmc\SDK\Core\Operation\Result
     */
    public function getOrderStatus(string $orderUid): Result
    {
        $params = [
            'GUID' => $orderUid
        ];
        return $this->getResponse(SoapMethod::GET_ORDER_STATUS_ACTION_1C, $params);
    }
}
<?php

namespace App;

use App\Entity\Checkpoint;
use App\Entity\Company;
use App\Entity\Event;
use App\Entity\EventCategory;
use App\Entity\EventModality;
use App\Entity\EventStatus;
use App\Entity\Line;
use App\Entity\LinePoint;
use App\Entity\Obd;
use App\Entity\Parameter;
use App\Entity\ParameterConfiguration;
use App\Entity\ReportType;
use App\Entity\Schedule;
use App\Entity\ScheduleDate;
use App\Entity\Trip;
use App\Entity\TripModality;
use App\Entity\TripStatus;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManager;

const MOLECULAR_MASS = 28.9644;
const R = 8.314472;

final class DAO
{
    public static function getObds(EntityManager $em): array
    {
        $entities = $em->getRepository(Obd::class)
            ->createQueryBuilder('e')
            ->getQuery()
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getResult()
        ;

        $list = [];
        foreach ($entities as $key => $entity) {
            if ($entity->getIsActive() == false) {
                unset($entities[$key]);
                continue;
            }
            $list[$entity->getSerial()] = $entity;
        }

        return $list;
    }

    public static function getVehicles(EntityManager $em): array
    {
        $entities = $em->getRepository(Vehicle::class)
            ->createQueryBuilder('e')
            ->getQuery()
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getResult()
        ;

        $list = [];
        foreach ($entities as $entity) {
            if (!$entity->getObd()) {
                continue;
            }
            $list[$entity->getObd()->getSerial()] = $entity;
        }

        return $list;
    }

    public static function getSchedules(EntityManager $em): array
    {
        $now = (new \DateTime())->setTime(0, 0, 0);
        $entities = $em->getRepository(Schedule::class)
            ->createQueryBuilder('e')
            ->andWhere('e.dataValidity >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;

        $list = [];
        foreach ($entities as $entity) {
            if (!$entity->getVehicle() || !$entity->getVehicle()->getObd()) {
                continue;
            }
            if (!isset($list[$entity->getVehicle()->getObd()->getSerial()])) {
                $list[$entity->getVehicle()->getObd()->getSerial()] = [];
            }

            $list[$entity->getVehicle()->getObd()->getSerial()][] = $entity;
        }

        return $list;
    }

    public static function getTrips(EntityManager $em, ScheduleDate $scheduleDate): ?Trip
    {
        $now = new \DateTime();
        $now = $now->modify('-1 day');

        $entities = $em->getRepository(Trip::class)
            ->createQueryBuilder('e')
            ->andWhere('e.starts_at >= :now')
            ->andWhere('e.scheduleDate = :scheduleDate')
            ->setParameter('now', $now)
            ->setParameter('scheduleDate', $scheduleDate)
            ->getQuery()
            // ->getResult()
            ->getOneOrNullResult()
        ;

        return $entities;
    }

    public static function getTripStatus(EntityManager $em, int $id): ?TripStatus
    {
        $status = $em->getRepository(TripStatus::class)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $status;
    }

    public static function getTripModality(EntityManager $em, int $id): ?TripModality
    {
        $status = $em->getRepository(TripModality::class)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $status;
    }

    public static function getLastCheckpointFromOBD(EntityManager $em, Obd $obd): ?Checkpoint
    {
        return $em->getRepository(Checkpoint::class)
            ->createQueryBuilder('e')
            ->andWhere('e.obd = :id')
            ->setParameter('id', $obd->getId())
            ->orderBy('e.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public static function distance(float $lat1, float $long1, float $lat2, float $long2): float
    {
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($long1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($long2);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) + pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        return atan2(sqrt($a), $b) * 6371230;
    }

    public static function getSchedulesDate(EntityManager $em): array
    {
        $today = new \DateTime();
        $now = (clone $today)->modify('-1 day');

        $entities = $em->getRepository(ScheduleDate::class)
            ->createQueryBuilder('e')
            ->andWhere('e.date >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;

        return $entities;
    }

    public static function getSchedulesByDateAndObd(EntityManager $em, string $serial): array
    {
        $today = new \DateTime();
        $start = (clone $today)->modify('-1 day');
        $today2 = (clone $today)->modify('+1 day');

        $entities = $em->getRepository(ScheduleDate::class)
            ->createQueryBuilder('e')
            ->innerJoin(Vehicle::class, 'vehicle', 'WITH', 'e.vehicle = vehicle.id')
            ->innerJoin(Obd::class, 'obd', 'WITH', 'vehicle.obd = obd.id')
            ->andWhere('e.date BETWEEN :start and :today')
            ->andWhere('obd.serial = :serial')
            ->setParameter('start', $start)
            ->setParameter('today', $today)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getResult()
        ;

        return $entities;
    }

    public static function getLinePoints(EntityManager $em, Line $line): array
    {
        $entities = $em->getRepository(LinePoint::class)
            ->createQueryBuilder('e')
            ->innerJoin(Line::class, 'line', 'WITH', 'e.line = line.id')
            ->andWhere('line.id = :line')
            ->setParameter('line', $line->getId())
            ->addOrderBy('e.sequence', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $entities;
    }

    public static function getAllLinePoints(EntityManager $em): array
    {
        $entities = $em->getRepository(LinePoint::class)
            ->createQueryBuilder('e')
            ->addOrderBy('e.sequence', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $entities;
    }

    public static function generateDataReport(EntityManager $em, Trip $trip)
    {
        $checkpoints = $em->getRepository(Checkpoint::class)
            ->createQueryBuilder('e')
            // ->andWhere('e.trip = :trip')
            ->andWhere('e.vehicle = :vehicle')
            ->andWhere('e.date BETWEEN :start AND :end')
            ->setParameter('vehicle', $trip->getVehicle())
            ->setParameter('start', $trip->getStartsAt())
            ->setParameter('end', $trip->getEndsAt())
            ->setParameter('trip', $trip)
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $tripInterval = $trip->getStartsAt()->diff($trip->getEndsAt());
        $now = new \DateTime();
        $tripTime = $now->setTime(intval($tripInterval->format('%h')), intval($tripInterval->format('%i')), intval($tripInterval->format('%s')));

        $line = $trip->getLine();
        $points = $em->getRepository(LinePoint::class)
            ->createQueryBuilder('e')
            ->andWhere('e.line = :line')
            ->setParameter('line', $line)
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $checkpointStops = [];

        $speedMax = 0;
        $speedAverage = 0;
        $speedAverageNoStops = 0;
        $consumption = 0;
        $consumptionReal = 0;
        $distance = 0;
        $i = 0;
        $qtd = 0;
        foreach ($checkpoints as $key => $checkpoint) {
            if ($checkpoint->getSpeed()) {
                if ($checkpoint->getSpeed() > $speedMax) {
                    $speedMax = $checkpoint->getSpeed();
                }
                $speedAverage += $checkpoint->getSpeed();
                if ($checkpoint->getSpeed() != 0) {
                    $speedAverageNoStops += $checkpoint->getSpeed();
                    ++$qtd;
                }
            }

            if ($key < count($checkpoints) - 2 && !is_null($checkpoint->getLatitude()) && !is_null($checkpoint->getLongitude())) {
                $checkpointNext = $checkpoints[$key + 1];
                if (!is_null($checkpointNext->getLongitude()) && !is_null($checkpointNext->getLatitude())) {
                    $distance += self::distance($checkpoint->getLatitude(), $checkpoint->getLongitude(), $checkpointNext->getLatitude(), $checkpointNext->getLongitude());
                }
            }

            if ($checkpoint->getRpm()) {
                $consumption += self::calculaConsumoPonto($checkpoint, $checkpoint->getVehicle()->getModel());
                ++$i;
            }

            foreach ($points as $key2 => $point) {
                $distancePoint = self::distance($point->getLatitude(), $point->getLongitude(), $checkpoint->getLatitude(), $checkpoint->getLongitude());
                if (!isset($checkpointStops[$key2])) {
                    array_push($checkpointStops, [
                        'point' => $point,
                        'distancia' => $distancePoint,
                        'horario' => $checkpoint->getDate(),
                    ]);
                } else {
                    foreach ($checkpointStops as $key3 => $checkpointStop) {
                        if ($checkpoint->getSpeed() <= 10 && $distance < $checkpointStop['distancia']) {
                            $checkpointStops[$key3] = [
                                'point' => $point,
                                'distancia' => $distancePoint,
                                'horario' => $checkpoint->getDate(),
                            ];
                        }
                    }
                }
            }
        }

        $speedAverage = $speedAverage / count($checkpoints);
        $distance = $distance / 1000;

        if ($i <= 0) {
            $consumption = 0;
        } else {
            $consumption /= $i;
        }

        $type = $em->getRepository(ReportType::class)
            ->createQueryBuilder('e')
            ->andWhere('e.description = :type')
            ->setParameter('type', 'Em escala')
            ->getQuery()
            ->getOneOrNullResult()
        ;

        // falta calcular consumo real(consumo real esta igual consumo para não ficar nulo)
        return [
            'type' => $type,
            'tripTime' => $tripTime,
            'distance' => $distance,
            'speedAverage' => round($speedAverage, 2),
            'speedAverageNoStop' => round(($qtd > 0 ? $speedAverageNoStops / $qtd : 0), 2),
            'speedMax' => $speedMax,
            'consumption' => $consumption ? $speedAverage / $consumption : 0, // km/l
            'consumptionReal' => $consumption ? $speedAverage / $consumption : 0, // km/l
            'checkpointStops' => json_encode($checkpointStops),
            ];
    }

    public static function calculaConsumoPonto($checkpoint, $modelo = null)
    {
        if ($modelo) {
            return self::calculaConsumo(
                $checkpoint->getRpm(),
                $checkpoint->getMap(),
                $checkpoint->getIat(),
                $modelo->getEfficiency(),
                $modelo->getVolume(),
                $modelo->getAirFuelRatio(),
                $modelo->getFuelDensity()
            );
        } else {
            return self::calculaConsumo($checkpoint->getRpm(), $checkpoint->getMap(), $checkpoint->getIat());
        }
    }

    public static function calculaConsumo($rpm, $map, $iat, $volEfficiency = 0.5, $engineDisplacement = 4.8, $airFuelRatio = 14.5 * 10, $fuelDensity = 832, $oxiLambda = 1)
    {
        if (!$rpm || !$map) {
            return 0;
        }
        $imap = $rpm * $map / ($iat + 273);
        $maf = ($imap / 120) * ($volEfficiency) * $engineDisplacement * MOLECULAR_MASS / R;
        $result = self::calculaConsumoMAF($maf, $airFuelRatio, $fuelDensity, $oxiLambda);

        return $result;
    }

    // maf g/s
    // fuel density in g/l
    // adicionado oxiLambda para testar proporção caso necessário
    public static function calculaConsumoMAF($maf, $airFuelRatio = 14.5 * 3, $fuelDensity = 832, $oxiLambda = 1)
    {
        $result = (($maf * 3600) / ($oxiLambda * $airFuelRatio)) / $fuelDensity; // l/h

        return $result;
    }

    public static function getSpeedCompany(EntityManager $em, Obd $obd)
    {
        $parameter = $em->getRepository(ParameterConfiguration::class)
            ->createQueryBuilder('e')
            ->innerJoin(Parameter::class, 'parameter', 'WITH', 'e.parameter = parameter.id')
            ->andWhere('parameter.description = :description')
            ->setParameter('description', 'Velocidade')
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        foreach ($parameter as $key => $speed) {
            if ($speed->getCompany()->getId() == $obd->getCompany()->getId()) {
                return intval($speed->getMaxAllowed());
            }
        }

        return null;
    }

    public static function getParametersVehicle(EntityManager $em, Obd $obd)
    {
        $parameters = $em->getRepository(Vehicle::class)
            ->createQueryBuilder('e')
            ->andWhere('e.obd = :obd')
            ->setParameter('obd', $obd)
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (is_null($parameters)) {
            return null;
        }

        return $parameters->getModel();
    }

    public static function getEventModality(EntityManager $em, string $description)
    {
        $modality = $em->getRepository(EventModality::class)
            ->createQueryBuilder('e')
            ->andWhere('e.description = :description')
            ->setParameter('description', $description)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $modality;
    }

    public static function getEventStatus(EntityManager $em, string $description)
    {
        $status = $em->getRepository(EventStatus::class)
            ->createQueryBuilder('e')
            ->andWhere('e.description = :description')
            ->setParameter('description', $description)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $status;
    }

    public static function getEventCategory(
        EntityManager $em,
        string $description
    ) {
        $category = $em->getRepository(EventCategory::class)
            ->createQueryBuilder('e')
            ->andWhere('e.description = :description')
            ->setParameter('description', $description)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $category;
    }

    public static function getTripByVehicle(
        EntityManager $em,
        Vehicle $vehicle
    ) {
        $now = (new \DateTime())->modify('-120 minutes');
        $trip = $em->getRepository(Trip::class)
            ->createQueryBuilder('e')
            ->andWhere('e.vehicle = :vehicle')
            ->andWhere('e.ends_at is null')
            ->andWhere('e.starts_at >= :now')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $trip;
    }

    public static function getLastEvent(EntityManager $em)
    {
        $today = (new \DateTime())->setTime(0, 0, 0);

        $event = $em->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->andWhere('e.start >= :date')
            ->setParameter('date', $today)
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return end($event);
    }

    public static function getTripsByDate(EntityManager $em, $date)
    {
        $trips = $em->getRepository(Trip::class)
            ->createQueryBuilder('e')
            ->andWhere('e.starts_at >= :date')
            ->setParameter('date', $date)
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return $trips;
    }

    public static function getEventByType(EntityManager $em, Trip $trip, int $id)
    {
        $eventCategory = $em->getRepository(EventCategory::class)
            ->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $event = $em->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->andWhere('e.trip = :trip')
            ->andWhere('e.category = :category')
            ->setParameter('trip', $trip)
            ->setParameter('category', $eventCategory)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $event;
    }

    public static function getSchedulesDateNoTrip(EntityManager $em, Vehicle $vehicle)
    {
        $today = (new \DateTime())->setTime(0, 0, 0);

        $scheduleDates = $em->getRepository(ScheduleDate::class)
            ->createQueryBuilder('e')
            ->andWhere('e.date >= :today')
            ->andWhere('e.vehicle >= :vehicle')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult()
        ;

        $trips = $em->getRepository(Trip::class)
            ->createQueryBuilder('e')
            ->andWhere('e.starts_at >= :today')
            ->andWhere('e.vehicle >= :vehicle')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult()
        ;

        if (!is_null($trips) && !is_null($scheduleDates)) {
            foreach ($trips as $key => $trip) {
                foreach ($scheduleDates as $key => $scheduleDate) {
                    if ($trip->getScheduleDate() == $scheduleDate) {
                        unset($scheduleDates[$key]);
                    }
                }
            }
        }

        return $scheduleDates;
    }

    public static function getLastEventExistByCategory(EntityManager $em, string $category, Vehicle $vehicle)
    {
        $now = (new \DateTime())->modify('-60 minutes');

        $event = $em->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->innerJoin(EventCategory::class, 'category', 'WITH', 'e.category = category.id')
            ->andWhere('category.description = :event_category')
            ->andWhere('e.vehicle = :vehicle')
            ->andWhere('e.start > :time')
            ->setParameter('event_category', $category)
            ->setParameter('vehicle', $vehicle)
            ->setParameter('time', $now)
            ->getQuery()
            ->getResult()
        ;

        return $event;
    }

    public static function getLastCheckpointAllObds(EntityManager $em)
    {
        $now = (new \DateTime())->modify('-60 minutes');

        $obds = $em->getRepository(Obd::class)
            ->createQueryBuilder('e')
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $lastCheckpoints = [];
        foreach ($obds as $key => $obd) {
            $checkpoint = $em->getRepository(Checkpoint::class)
                ->createQueryBuilder('e')
                ->andWhere('e.obd = :obd')
                ->setParameter('obd', $obd)
                ->orderBy('e.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
            ;

            if (!is_null($checkpoint)) {
                $lastCheckpoints += [$obd->getSerial() => $checkpoint];
            } else {
                $lastCheckpoints += [$obd->getSerial() => null];
            }
        }

        return $lastCheckpoints;
    }

    public static function getAllVehicles(EntityManager $em)
    {
        $vehicles = $em->getRepository(Vehicle::class)
                ->createQueryBuilder('e')
                ->orderBy('e.id', 'DESC')
                ->getQuery()
                ->getResult()
            ;

        return $vehicles;
    }

    public static function getParametersConfigurationsCompany(EntityManager $em, Company $company)
    {
        if (is_null($company)) {
            return null;
        }

        $parameters = $em->getRepository(ParameterConfiguration::class)
            ->createQueryBuilder('e')
            ->andWhere('e.company = :company')
            ->setParameter('company', $company)
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (is_null($parameters)) {
            return null;
        }

        return $parameters;
    }
}

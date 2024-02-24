<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="checkpoint")
 */
class Checkpoint
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=15)
     */
    protected $identifier;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive;

    /**
     * @ORM\ManyToOne(targetEntity=Vehicle::class, inversedBy="checkpoints")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $vehicle;

    /**
     * @ORM\ManyToOne(targetEntity=Obd::class, inversedBy="checkpoints")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $obd;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $date;

    /**
     * @ORM\Column(type="float")
     */
    protected $latitude;

    /**
     * @ORM\Column(type="float")
     */
    protected $longitude;

    /**
     * @ORM\Column(type="float")
     */
    protected $distance;

    /**
     * @ORM\Column(type="float")
     */
    protected $angle;

    /**
     * @ORM\Column(type="float")
     */
    protected $hdop;

    /**
     * @ORM\Column(type="float")
     */
    protected $rpm;

    /**
     * @ORM\Column(type="float")
     */
    protected $fuel;

    /**
     * @ORM\Column(type="float")
     */
    protected $speed;

    /**
     * @ORM\Column(type="float")
     */
    protected $map;

    /**
     * @ORM\Column(type="float")
     */
    protected $ect;

    /**
     * @ORM\Column(type="float")
     */
    protected $iat;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $errors;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $alerts;

    /**
     * @ORM\ManyToOne(targetEntity=Trip::class, inversedBy="checkpoints")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $trip;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineTorqueMode;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $driverDemand;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $actualEngine;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $engineSpeed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sourceAddress;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineStarterMode;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vehicleSpeed;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $hrVehicleDistance;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $tripDistance;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vehicleDistance;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineCoolantTemp;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $engineHoursOperation;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineTotalRevolutions;

    private $compassBearing;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $navigationVehicleSpeed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $pitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $altitude;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineTripFuel;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineFuelUsed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $accPedalPosition;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fuelDeliveryPressure;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engExtendedCrankcasePressure;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineOilLevel;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineOilPressure;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineCrankcasePressure;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineCoolantPressure;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineCoolantLevel;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $twoSpeedAxleSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $parkingBrakeSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cruiseControlPauseSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $parkBrakeRelease;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $wheelVehicleSpeed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cruiseControlActive;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cruiseControlEnableSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $brakeSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $clutchSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cruiseControlSetSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cruiseControlCoastSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cruiseControlResumeSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cruiseControlAccelerateSwitch;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cruiseControlSetSpeed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ptoGovernorState;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cruiseControlStates;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineIdleIncrementSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineIdleDecrementSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineTestModeSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineShutdownOverrideSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineDieselParticulateFilter;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineIntakeManifoldPressure;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineIntakeManifoldTemperature;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineAirInletPressure;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineAirFilterDifferentialPressure;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineExhaustGasTemperature;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineCoolantFilterDifferential;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $netBatteryCurrent;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $alternatorCurrent;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $chargingSystempotential;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $batteryPotentialInput;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $keyswitchBatteryPotential;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $washerFluidLevel;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fuelLevelOne;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineFuelFilterPressure;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineOilFilterPressure;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cargoAmbientTemperature;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fuelLevelTwo;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $highResolutionTripDistance;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $engineFuelTemperatureOne;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $engineOilTemperatureOne;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $engineTurbochargerOilTemperature;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $engineIntercoolerTemperature;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $engineIntercoolerThermostatOpening;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $acceleratorPedalOneLowSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $acceleratorPedalKickdownSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $roadSpeedLimitStatus;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $acceleratorPedalTwoLowSwitch;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $malfunctionIndicatorLampStatusOne;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $redStopLampStatusOne;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $amberWarningLampStatusOne;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $protectLampStatusOne;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $spnOne;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fmiOne;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $occurrenceCountOne;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $malfunctionIndicatorLampStatusTwo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $redStopLampStatusTwo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $amberWarningLampStatusTwo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $protectLampStatusTwo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $spnTwo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fmiTwo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $occurrenceCountTwo;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $ActuelEngineHighResolution;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $EngineDemand;

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    public function getObd(): ?Obd
    {
        return $this->obd;
    }

    public function setObd(?Obd $obd): self
    {
        $this->obd = $obd;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(?float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getAngle(): ?float
    {
        return $this->angle;
    }

    public function setAngle(?float $angle): self
    {
        $this->angle = $angle;

        return $this;
    }

    public function getHdop(): ?float
    {
        return $this->hdop;
    }

    public function setHdop(?float $hdop): self
    {
        $this->hdop = $hdop;

        return $this;
    }

    public function getRpm(): ?float
    {
        return $this->rpm;
    }

    public function setRpm(?float $rpm): self
    {
        $this->rpm = $rpm;

        return $this;
    }

    public function getFuel(): ?float
    {
        return $this->fuel;
    }

    public function setFuel(?float $fuel): self
    {
        $this->fuel = $fuel;

        return $this;
    }

    public function getSpeed(): ?float
    {
        return $this->speed;
    }

    public function setSpeed(?float $speed): self
    {
        $this->speed = $speed;

        return $this;
    }

    public function getMap(): ?float
    {
        return $this->map;
    }

    public function setMap(?float $map): self
    {
        $this->map = $map;

        return $this;
    }

    public function getEct(): ?float
    {
        return $this->ect;
    }

    public function setEct(?float $ect): self
    {
        $this->ect = $ect;

        return $this;
    }

    public function getIat(): ?float
    {
        return $this->iat;
    }

    public function setIat(?float $iat): self
    {
        $this->iat = $iat;

        return $this;
    }

    public function getErrors(): ?string
    {
        return $this->errors;
    }

    public function setErrors(?string $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function getAlerts(): ?string
    {
        return $this->alerts;
    }

    public function setAlerts(?string $alerts): self
    {
        $this->alerts = $alerts;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): self
    {
        $this->trip = $trip;

        return $this;
    }

    public function getEngineTorqueMode(): ?int
    {
        return $this->engineTorqueMode;
    }

    public function setEngineTorqueMode(?int $engineTorqueMode): self
    {
        $this->engineTorqueMode = $engineTorqueMode;

        return $this;
    }

    public function getDriverDemand(): ?int
    {
        return $this->driverDemand;
    }

    public function setDriverDemand(?int $driverDemand): self
    {
        $this->driverDemand = $driverDemand;

        return $this;
    }

    public function getActualEngine(): ?int
    {
        return $this->actualEngine;
    }

    public function setActualEngine(?int $actualEngine): self
    {
        $this->actualEngine = $actualEngine;

        return $this;
    }

    public function getEngineSpeed(): ?float
    {
        return $this->engineSpeed;
    }

    public function setEngineSpeed(?float $engineSpeed): self
    {
        $this->engineSpeed = $engineSpeed;

        return $this;
    }

    public function getSourceAddress(): ?int
    {
        return $this->sourceAddress;
    }

    public function setSourceAddress(?int $sourceAddress): self
    {
        $this->sourceAddress = $sourceAddress;

        return $this;
    }

    public function getEngineStarterMode(): ?int
    {
        return $this->engineStarterMode;
    }

    public function setEngineStarterMode(?int $engineStarterMode): self
    {
        $this->engineStarterMode = $engineStarterMode;

        return $this;
    }

    public function getVehicleSpeed(): ?float
    {
        return $this->vehicleSpeed;
    }

    public function setVehicleSpeed(?float $vehicleSpeed): self
    {
        $this->vehicleSpeed = $vehicleSpeed;

        return $this;
    }

    public function getHrVehicleDistance(): ?float
    {
        return $this->hrVehicleDistance;
    }

    public function setHrVehicleDistance(?float $hrVehicleDistance): self
    {
        $this->hrVehicleDistance = $hrVehicleDistance;

        return $this;
    }

    public function getTripDistance(): ?float
    {
        return $this->tripDistance;
    }

    public function setTripDistance(?float $tripDistance): self
    {
        $this->tripDistance = $tripDistance;

        return $this;
    }

    public function getVehicleDistance(): ?float
    {
        return $this->vehicleDistance;
    }

    public function setVehicleDistance(?float $vehicleDistance): self
    {
        $this->vehicleDistance = $vehicleDistance;

        return $this;
    }

    public function getEngineCoolantTemp(): ?int
    {
        return $this->engineCoolantTemp;
    }

    public function setEngineCoolantTemp(?int $engineCoolantTemp): self
    {
        $this->engineCoolantTemp = $engineCoolantTemp;

        return $this;
    }

    public function getEngineHoursOperation(): ?float
    {
        return $this->engineHoursOperation;
    }

    public function setEngineHoursOperation(?float $engineHoursOperation): self
    {
        $this->engineHoursOperation = $engineHoursOperation;

        return $this;
    }

    public function getEngineTotalRevolutions(): ?int
    {
        return $this->engineTotalRevolutions;
    }

    public function setEngineTotalRevolutions(?int $engineTotalRevolutions): self
    {
        $this->engineTotalRevolutions = $engineTotalRevolutions;

        return $this;
    }

    public function getCompassBearing(): ?int
    {
        return $this->compassBearing;
    }

    public function setCompassBearing(?int $compassBearing): self
    {
        $this->compassBearing = $compassBearing;

        return $this;
    }

    public function getNavigationVehicleSpeed(): ?float
    {
        return $this->navigationVehicleSpeed;
    }

    public function setNavigationVehicleSpeed(?float $navigationVehicleSpeed): self
    {
        $this->navigationVehicleSpeed = $navigationVehicleSpeed;

        return $this;
    }

    public function getPitch(): ?int
    {
        return $this->pitch;
    }

    public function setPitch(?int $pitch): self
    {
        $this->pitch = $pitch;

        return $this;
    }

    public function getAltitude(): ?int
    {
        return $this->altitude;
    }

    public function setAltitude(?int $altitude): self
    {
        $this->altitude = $altitude;

        return $this;
    }

    public function getEngineTripFuel(): ?int
    {
        return $this->engineTripFuel;
    }

    public function setEngineTripFuel(?int $engineTripFuel): self
    {
        $this->engineTripFuel = $engineTripFuel;

        return $this;
    }

    public function getEngineFuelUsed(): ?int
    {
        return $this->engineFuelUsed;
    }

    public function setEngineFuelUsed(?int $engineFuelUsed): self
    {
        $this->engineFuelUsed = $engineFuelUsed;

        return $this;
    }

    public function getAccPedalPosition(): ?int
    {
        return $this->accPedalPosition;
    }

    public function setAccPedalPosition(?int $accPedalPosition): self
    {
        $this->accPedalPosition = $accPedalPosition;

        return $this;
    }

    public function getFuelDeliveryPressure(): ?int
    {
        return $this->fuelDeliveryPressure;
    }

    public function setFuelDeliveryPressure(?int $fuelDeliveryPressure): self
    {
        $this->fuelDeliveryPressure = $fuelDeliveryPressure;

        return $this;
    }

    public function getEngExtendedCrankcasePressure(): ?int
    {
        return $this->engExtendedCrankcasePressure;
    }

    public function setEngExtendedCrankcasePressure(?int $engExtendedCrankcasePressure): self
    {
        $this->engExtendedCrankcasePressure = $engExtendedCrankcasePressure;

        return $this;
    }

    public function getEngineOilLevel(): ?int
    {
        return $this->engineOilLevel;
    }

    public function setEngineOilLevel(?int $engineOilLevel): self
    {
        $this->engineOilLevel = $engineOilLevel;

        return $this;
    }

    public function getEngineOilPressure(): ?int
    {
        return $this->engineOilPressure;
    }

    public function setEngineOilPressure(?int $engineOilPressure): self
    {
        $this->engineOilPressure = $engineOilPressure;

        return $this;
    }

    public function getEngineCrankcasePressure(): ?int
    {
        return $this->engineCrankcasePressure;
    }

    public function setEngineCrankcasePressure(?int $engineCrankcasePressure): self
    {
        $this->engineCrankcasePressure = $engineCrankcasePressure;

        return $this;
    }

    public function getEngineCoolantPressure(): ?int
    {
        return $this->engineCoolantPressure;
    }

    public function setEngineCoolantPressure(?int $engineCoolantPressure): self
    {
        $this->engineCoolantPressure = $engineCoolantPressure;

        return $this;
    }

    public function getEngineCoolantLevel(): ?int
    {
        return $this->engineCoolantLevel;
    }

    public function setEngineCoolantLevel(?int $engineCoolantLevel): self
    {
        $this->engineCoolantLevel = $engineCoolantLevel;

        return $this;
    }

    public function getTwoSpeedAxleSwitch(): ?int
    {
        return $this->twoSpeedAxleSwitch;
    }

    public function setTwoSpeedAxleSwitch(?int $twoSpeedAxleSwitch): self
    {
        $this->twoSpeedAxleSwitch = $twoSpeedAxleSwitch;

        return $this;
    }

    public function getParkingBrakeSwitch(): ?int
    {
        return $this->parkingBrakeSwitch;
    }

    public function setParkingBrakeSwitch(?int $parkingBrakeSwitch): self
    {
        $this->parkingBrakeSwitch = $parkingBrakeSwitch;

        return $this;
    }

    public function getCruiseControlPauseSwitch(): ?int
    {
        return $this->cruiseControlPauseSwitch;
    }

    public function setCruiseControlPauseSwitch(?int $cruiseControlPauseSwitch): self
    {
        $this->cruiseControlPauseSwitch = $cruiseControlPauseSwitch;

        return $this;
    }

    public function getParkBrakeRelease(): ?int
    {
        return $this->parkBrakeRelease;
    }

    public function setParkBrakeRelease(?int $parkBrakeRelease): self
    {
        $this->parkBrakeRelease = $parkBrakeRelease;

        return $this;
    }

    public function getWheelVehicleSpeed(): ?float
    {
        return $this->wheelVehicleSpeed;
    }

    public function setWheelVehicleSpeed(?float $wheelVehicleSpeed): self
    {
        $this->wheelVehicleSpeed = $wheelVehicleSpeed;

        return $this;
    }

    public function getCruiseControlActive(): ?int
    {
        return $this->cruiseControlActive;
    }

    public function setCruiseControlActive(?int $cruiseControlActive): self
    {
        $this->cruiseControlActive = $cruiseControlActive;

        return $this;
    }

    public function getCruiseControlEnableSwitch(): ?int
    {
        return $this->cruiseControlEnableSwitch;
    }

    public function setCruiseControlEnableSwitch(?int $cruiseControlEnableSwitch): self
    {
        $this->cruiseControlEnableSwitch = $cruiseControlEnableSwitch;

        return $this;
    }

    public function getBrakeSwitch(): ?int
    {
        return $this->brakeSwitch;
    }

    public function setBrakeSwitch(?int $brakeSwitch): self
    {
        $this->brakeSwitch = $brakeSwitch;

        return $this;
    }

    public function getClutchSwitch(): ?int
    {
        return $this->clutchSwitch;
    }

    public function setClutchSwitch(?int $clutchSwitch): self
    {
        $this->clutchSwitch = $clutchSwitch;

        return $this;
    }

    public function getCruiseControlSetSwitch(): ?int
    {
        return $this->cruiseControlSetSwitch;
    }

    public function setCruiseControlSetSwitch(?int $cruiseControlSetSwitch): self
    {
        $this->cruiseControlSetSwitch = $cruiseControlSetSwitch;

        return $this;
    }

    public function getCruiseControlCoastSwitch(): ?int
    {
        return $this->cruiseControlCoastSwitch;
    }

    public function setCruiseControlCoastSwitch(?int $cruiseControlCoastSwitch): self
    {
        $this->cruiseControlCoastSwitch = $cruiseControlCoastSwitch;

        return $this;
    }

    public function getCruiseControlResumeSwitch(): ?int
    {
        return $this->cruiseControlResumeSwitch;
    }

    public function setCruiseControlResumeSwitch(?int $cruiseControlResumeSwitch): self
    {
        $this->cruiseControlResumeSwitch = $cruiseControlResumeSwitch;

        return $this;
    }

    public function getCruiseControlAccelerateSwitch(): ?int
    {
        return $this->cruiseControlAccelerateSwitch;
    }

    public function setCruiseControlAccelerateSwitch(?int $cruiseControlAccelerateSwitch): self
    {
        $this->cruiseControlAccelerateSwitch = $cruiseControlAccelerateSwitch;

        return $this;
    }

    public function getCruiseControlSetSpeed(): ?float
    {
        return $this->cruiseControlSetSpeed;
    }

    public function setCruiseControlSetSpeed(?float $cruiseControlSetSpeed): self
    {
        $this->cruiseControlSetSpeed = $cruiseControlSetSpeed;

        return $this;
    }

    public function getPtoGovernorState(): ?int
    {
        return $this->ptoGovernorState;
    }

    public function setPtoGovernorState(?int $ptoGovernorState): self
    {
        $this->ptoGovernorState = $ptoGovernorState;

        return $this;
    }

    public function getCruiseControlStates(): ?int
    {
        return $this->cruiseControlStates;
    }

    public function setCruiseControlStates(?int $cruiseControlStates): self
    {
        $this->cruiseControlStates = $cruiseControlStates;

        return $this;
    }

    public function getEngineIdleIncrementSwitch(): ?int
    {
        return $this->engineIdleIncrementSwitch;
    }

    public function setEngineIdleIncrementSwitch(?int $engineIdleIncrementSwitch): self
    {
        $this->engineIdleIncrementSwitch = $engineIdleIncrementSwitch;

        return $this;
    }

    public function getEngineIdleDecrementSwitch(): ?int
    {
        return $this->engineIdleDecrementSwitch;
    }

    public function setEngineIdleDecrementSwitch(?int $engineIdleDecrementSwitch): self
    {
        $this->engineIdleDecrementSwitch = $engineIdleDecrementSwitch;

        return $this;
    }

    public function getEngineTestModeSwitch(): ?int
    {
        return $this->engineTestModeSwitch;
    }

    public function setEngineTestModeSwitch(?int $engineTestModeSwitch): self
    {
        $this->engineTestModeSwitch = $engineTestModeSwitch;

        return $this;
    }

    public function getEngineShutdownOverrideSwitch(): ?int
    {
        return $this->engineShutdownOverrideSwitch;
    }

    public function setEngineShutdownOverrideSwitch(?int $engineShutdownOverrideSwitch): self
    {
        $this->engineShutdownOverrideSwitch = $engineShutdownOverrideSwitch;

        return $this;
    }

    public function getEngineDieselParticulateFilter(): ?int
    {
        return $this->engineDieselParticulateFilter;
    }

    public function setEngineDieselParticulateFilter(?int $engineDieselParticulateFilter): self
    {
        $this->engineDieselParticulateFilter = $engineDieselParticulateFilter;

        return $this;
    }

    public function getEngineIntakeManifoldPressure(): ?int
    {
        return $this->engineIntakeManifoldPressure;
    }

    public function setEngineIntakeManifoldPressure(?int $engineIntakeManifoldPressure): self
    {
        $this->engineIntakeManifoldPressure = $engineIntakeManifoldPressure;

        return $this;
    }

    public function getEngineIntakeManifoldTemperature(): ?int
    {
        return $this->engineIntakeManifoldTemperature;
    }

    public function setEngineIntakeManifoldTemperature(?int $engineIntakeManifoldTemperature): self
    {
        $this->engineIntakeManifoldTemperature = $engineIntakeManifoldTemperature;

        return $this;
    }

    public function getEngineAirInletPressure(): ?int
    {
        return $this->engineAirInletPressure;
    }

    public function setEngineAirInletPressure(?int $engineAirInletPressure): self
    {
        $this->engineAirInletPressure = $engineAirInletPressure;

        return $this;
    }

    public function getEngineAirFilterDifferentialPressure(): ?int
    {
        return $this->engineAirFilterDifferentialPressure;
    }

    public function setEngineAirFilterDifferentialPressure(?int $engineAirFilterDifferentialPressure): self
    {
        $this->engineAirFilterDifferentialPressure = $engineAirFilterDifferentialPressure;

        return $this;
    }

    public function getEngineExhaustGasTemperature(): ?int
    {
        return $this->engineExhaustGasTemperature;
    }

    public function setEngineExhaustGasTemperature(?int $engineExhaustGasTemperature): self
    {
        $this->engineExhaustGasTemperature = $engineExhaustGasTemperature;

        return $this;
    }

    public function getEngineCoolantFilterDifferential(): ?int
    {
        return $this->engineCoolantFilterDifferential;
    }

    public function setEngineCoolantFilterDifferential(?int $engineCoolantFilterDifferential): self
    {
        $this->engineCoolantFilterDifferential = $engineCoolantFilterDifferential;

        return $this;
    }

    public function getNetBatteryCurrent(): ?int
    {
        return $this->netBatteryCurrent;
    }

    public function setNetBatteryCurrent(?int $netBatteryCurrent): self
    {
        $this->netBatteryCurrent = $netBatteryCurrent;

        return $this;
    }

    public function getAlternatorCurrent(): ?int
    {
        return $this->alternatorCurrent;
    }

    public function setAlternatorCurrent(?int $alternatorCurrent): self
    {
        $this->alternatorCurrent = $alternatorCurrent;

        return $this;
    }

    public function getChargingSystempotential(): ?int
    {
        return $this->chargingSystempotential;
    }

    public function setChargingSystempotential(?int $chargingSystempotential): self
    {
        $this->chargingSystempotential = $chargingSystempotential;

        return $this;
    }

    public function getBatteryPotentialInput(): ?int
    {
        return $this->batteryPotentialInput;
    }

    public function setBatteryPotentialInput(?int $batteryPotentialInput): self
    {
        $this->batteryPotentialInput = $batteryPotentialInput;

        return $this;
    }

    public function getKeyswitchBatteryPotential(): ?int
    {
        return $this->keyswitchBatteryPotential;
    }

    public function setKeyswitchBatteryPotential(?int $keyswitchBatteryPotential): self
    {
        $this->keyswitchBatteryPotential = $keyswitchBatteryPotential;

        return $this;
    }

    public function getWasherFluidLevel(): ?int
    {
        return $this->washerFluidLevel;
    }

    public function setWasherFluidLevel(?int $washerFluidLevel): self
    {
        $this->washerFluidLevel = $washerFluidLevel;

        return $this;
    }

    public function getFuelLevelOne(): ?int
    {
        return $this->fuelLevelOne;
    }

    public function setFuelLevelOne(?int $fuelLevelOne): self
    {
        $this->fuelLevelOne = $fuelLevelOne;

        return $this;
    }

    public function getEngineFuelFilterPressure(): ?int
    {
        return $this->engineFuelFilterPressure;
    }

    public function setEngineFuelFilterPressure(?int $engineFuelFilterPressure): self
    {
        $this->engineFuelFilterPressure = $engineFuelFilterPressure;

        return $this;
    }

    public function getEngineOilFilterPressure(): ?int
    {
        return $this->engineOilFilterPressure;
    }

    public function setEngineOilFilterPressure(?int $engineOilFilterPressure): self
    {
        $this->engineOilFilterPressure = $engineOilFilterPressure;

        return $this;
    }

    public function getCargoAmbientTemperature(): ?int
    {
        return $this->cargoAmbientTemperature;
    }

    public function setCargoAmbientTemperature(?int $cargoAmbientTemperature): self
    {
        $this->cargoAmbientTemperature = $cargoAmbientTemperature;

        return $this;
    }

    public function getFuelLevelTwo(): ?int
    {
        return $this->fuelLevelTwo;
    }

    public function setFuelLevelTwo(?int $fuelLevelTwo): self
    {
        $this->fuelLevelTwo = $fuelLevelTwo;

        return $this;
    }

    public function getHighResolutionTripDistance(): ?float
    {
        return $this->highResolutionTripDistance;
    }

    public function setHighResolutionTripDistance(?float $highResolutionTripDistance): self
    {
        $this->highResolutionTripDistance = $highResolutionTripDistance;

        return $this;
    }

    public function getEngineFuelTemperatureOne(): ?float
    {
        return $this->engineFuelTemperatureOne;
    }

    public function setEngineFuelTemperatureOne(?float $engineFuelTemperatureOne): self
    {
        $this->engineFuelTemperatureOne = $engineFuelTemperatureOne;

        return $this;
    }

    public function getEngineOilTemperatureOne(): ?float
    {
        return $this->engineOilTemperatureOne;
    }

    public function setEngineOilTemperatureOne(?float $engineOilTemperatureOne): self
    {
        $this->engineOilTemperatureOne = $engineOilTemperatureOne;

        return $this;
    }

    public function getEngineTurbochargerOilTemperature(): ?float
    {
        return $this->engineTurbochargerOilTemperature;
    }

    public function setEngineTurbochargerOilTemperature(?float $engineTurbochargerOilTemperature): self
    {
        $this->engineTurbochargerOilTemperature = $engineTurbochargerOilTemperature;

        return $this;
    }

    public function getEngineIntercoolerTemperature(): ?float
    {
        return $this->engineIntercoolerTemperature;
    }

    public function setEngineIntercoolerTemperature(?float $engineIntercoolerTemperature): self
    {
        $this->engineIntercoolerTemperature = $engineIntercoolerTemperature;

        return $this;
    }

    public function getEngineIntercoolerThermostatOpening(): ?int
    {
        return $this->engineIntercoolerThermostatOpening;
    }

    public function setEngineIntercoolerThermostatOpening(?int $engineIntercoolerThermostatOpening): self
    {
        $this->engineIntercoolerThermostatOpening = $engineIntercoolerThermostatOpening;

        return $this;
    }

    public function getAcceleratorPedalOneLowSwitch(): ?int
    {
        return $this->acceleratorPedalOneLowSwitch;
    }

    public function setAcceleratorPedalOneLowSwitch(?int $acceleratorPedalOneLowSwitch): self
    {
        $this->acceleratorPedalOneLowSwitch = $acceleratorPedalOneLowSwitch;

        return $this;
    }

    public function getAcceleratorPedalKickdownSwitch(): ?int
    {
        return $this->acceleratorPedalKickdownSwitch;
    }

    public function setAcceleratorPedalKickdownSwitch(?int $acceleratorPedalKickdownSwitch): self
    {
        $this->acceleratorPedalKickdownSwitch = $acceleratorPedalKickdownSwitch;

        return $this;
    }

    public function getRoadSpeedLimitStatus(): ?int
    {
        return $this->roadSpeedLimitStatus;
    }

    public function setRoadSpeedLimitStatus(?int $roadSpeedLimitStatus): self
    {
        $this->roadSpeedLimitStatus = $roadSpeedLimitStatus;

        return $this;
    }

    public function getAcceleratorPedalTwoLowSwitch(): ?int
    {
        return $this->acceleratorPedalTwoLowSwitch;
    }

    public function setAcceleratorPedalTwoLowSwitch(?int $acceleratorPedalTwoLowSwitch): self
    {
        $this->acceleratorPedalTwoLowSwitch = $acceleratorPedalTwoLowSwitch;

        return $this;
    }

    public function getMalfunctionIndicatorLampStatusOne(): ?int
    {
        return $this->malfunctionIndicatorLampStatusOne;
    }

    public function setMalfunctionIndicatorLampStatusOne(?int $malfunctionIndicatorLampStatusOne): self
    {
        $this->malfunctionIndicatorLampStatusOne = $malfunctionIndicatorLampStatusOne;

        return $this;
    }

    public function getRedStopLampStatusOne(): ?int
    {
        return $this->redStopLampStatusOne;
    }

    public function setRedStopLampStatusOne(?int $redStopLampStatusOne): self
    {
        $this->redStopLampStatusOne = $redStopLampStatusOne;

        return $this;
    }

    public function getAmberWarningLampStatusOne(): ?int
    {
        return $this->amberWarningLampStatusOne;
    }

    public function setAmberWarningLampStatusOne(?int $amberWarningLampStatusOne): self
    {
        $this->amberWarningLampStatusOne = $amberWarningLampStatusOne;

        return $this;
    }

    public function getProtectLampStatusOne(): ?int
    {
        return $this->protectLampStatusOne;
    }

    public function setProtectLampStatusOne(?int $protectLampStatusOne): self
    {
        $this->protectLampStatusOne = $protectLampStatusOne;

        return $this;
    }

    public function getSpnOne(): ?int
    {
        return $this->spnOne;
    }

    public function setSpnOne(?int $spnOne): self
    {
        $this->spnOne = $spnOne;

        return $this;
    }

    public function getFmiOne(): ?int
    {
        return $this->fmiOne;
    }

    public function setFmiOne(?int $fmiOne): self
    {
        $this->fmiOne = $fmiOne;

        return $this;
    }

    public function getOccurrenceCountOne(): ?int
    {
        return $this->occurrenceCountOne;
    }

    public function setOccurrenceCountOne(?int $occurrenceCountOne): self
    {
        $this->occurrenceCountOne = $occurrenceCountOne;

        return $this;
    }

    public function getMalfunctionIndicatorLampStatusTwo(): ?int
    {
        return $this->malfunctionIndicatorLampStatusTwo;
    }

    public function setMalfunctionIndicatorLampStatusTwo(?int $malfunctionIndicatorLampStatusTwo): self
    {
        $this->malfunctionIndicatorLampStatusTwo = $malfunctionIndicatorLampStatusTwo;

        return $this;
    }

    public function getRedStopLampStatusTwo(): ?int
    {
        return $this->redStopLampStatusTwo;
    }

    public function setRedStopLampStatusTwo(?int $redStopLampStatusTwo): self
    {
        $this->redStopLampStatusTwo = $redStopLampStatusTwo;

        return $this;
    }

    public function getAmberWarningLampStatusTwo(): ?int
    {
        return $this->amberWarningLampStatusTwo;
    }

    public function setAmberWarningLampStatusTwo(?int $amberWarningLampStatusTwo): self
    {
        $this->amberWarningLampStatusTwo = $amberWarningLampStatusTwo;

        return $this;
    }

    public function getProtectLampStatusTwo(): ?int
    {
        return $this->protectLampStatusTwo;
    }

    public function setProtectLampStatusTwo(?int $protectLampStatusTwo): self
    {
        $this->protectLampStatusTwo = $protectLampStatusTwo;

        return $this;
    }

    public function getSpnTwo(): ?int
    {
        return $this->spnTwo;
    }

    public function setSpnTwo(?int $spnTwo): self
    {
        $this->spnTwo = $spnTwo;

        return $this;
    }

    public function getFmiTwo(): ?int
    {
        return $this->fmiTwo;
    }

    public function setFmiTwo(?int $fmiTwo): self
    {
        $this->fmiTwo = $fmiTwo;

        return $this;
    }

    public function getOccurrenceCountTwo(): ?int
    {
        return $this->occurrenceCountTwo;
    }

    public function setOccurrenceCountTwo(?int $occurrenceCountTwo): self
    {
        $this->occurrenceCountTwo = $occurrenceCountTwo;

        return $this;
    }

    public function getActuelEngineHighResolution(): ?float
    {
        return $this->ActuelEngineHighResolution;
    }

    public function setActuelEngineHighResolution(?float $ActuelEngineHighResolution): self
    {
        $this->ActuelEngineHighResolution = $ActuelEngineHighResolution;

        return $this;
    }

    public function getEngineDemand(): ?int
    {
        return $this->EngineDemand;
    }

    public function setEngineDemand(?int $EngineDemand): self
    {
        $this->EngineDemand = $EngineDemand;

        return $this;
    }
}

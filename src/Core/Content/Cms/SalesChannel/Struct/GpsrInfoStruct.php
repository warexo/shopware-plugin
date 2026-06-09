<?php

namespace Warexo\Core\Content\Cms\SalesChannel\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Struct\Struct;

class GpsrInfoStruct extends Struct
{
    /**
     * @var string|null
     */
    protected $company;

    /**
     * @var string|null
     */
    protected $address;

    /**
     * @var string|null
     */
    protected $country;

    /**
     * @var string|null
     */
    protected $zip;

    /**
     * @var string|null
     */
    protected $city;

    /**
     * @var string|null
     */
    protected $email;

    /**
     * @var string|null
     */
    protected $description;

     /**
     * @var string|null
     */
    protected $url;

    /**
     * @var string|null
     */
    protected $importerCompany;

    /**
    * @var string|null
    */
    protected $importerAddress;

    /**
     * @var string|null
     */
    protected $importerUrl;

    /**
     * @var string|null
     */
    protected $importerEmail;

    /**
     * @var string|null
     */
    protected $responsiblePersonCompany;

    /**
     * @var string|null
     */
    protected $responsiblePersonAddress;

    /**
     * @var string|null
     */
    protected $responsiblePersonEmail;

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): void
    {
        $this->company = $company;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getImporterCompany(): ?string
    {
        return $this->importerCompany;
    }

    public function setImporterCompany(?string $importerCompany): void
    {
        $this->importerCompany = $importerCompany;
    }

    public function getImporterAddress(): ?string
    {
        return $this->importerAddress;
    }

    public function setImporterAddress(?string $importerAddress): void
    {
        $this->importerAddress = $importerAddress;
    }

    public function getImporterUrl(): ?string
    {
        return $this->importerUrl;
    }

    public function setImporterUrl(?string $importerUrl): void
    {
        $this->importerUrl = $importerUrl;
    }

    public function getImporterEmail(): ?string
    {
        return $this->importerEmail;
    }

    public function setImporterEmail(?string $importerEmail): void
    {
        $this->importerEmail = $importerEmail;
    }

    public function getResponsiblePersonCompany(): ?string
    {
        return $this->responsiblePersonCompany;
    }

    public function setResponsiblePersonCompany(?string $responsiblePersonCompany): void
    {
        $this->responsiblePersonCompany = $responsiblePersonCompany;
    }

    public function getResponsiblePersonAddress(): ?string
    {
        return $this->responsiblePersonAddress;
    }

    public function setResponsiblePersonAddress(?string $responsiblePersonAddress): void
    {
        $this->responsiblePersonAddress = $responsiblePersonAddress;
    }

    public function getResponsiblePersonEmail(): ?string
    {
        return $this->responsiblePersonEmail;
    }

    public function setResponsiblePersonEmail(?string $responsiblePersonEmail): void
    {
        $this->responsiblePersonEmail = $responsiblePersonEmail;
    }

}
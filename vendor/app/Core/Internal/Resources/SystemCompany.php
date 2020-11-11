<?php

namespace App\Core\Internal\Resources;

class SystemCompany
{
    protected $listApp = [];

    public function __construct()
    {
        $this->setListApp();
    }

    protected function setListApp () {
        $temp = new \stdClass();
        $temp->app_id = '967a82e3-085b-44dc-a47c-ced2e058a462';
        $temp->token = 'MDYxMTUwNjQtMTNkYy00YzNlLTgyY2QtMzdiNDRhMjM0NWQ5';
        $temp->system = '5afd651b-06bc-11ea-9849-d2118390b116';
        $temp->name = 'ezbooking';
        $this->listApp[] = $temp;

        // atgtours
        $temp = new \stdClass();
        $temp->app_id = '8fe9e3e9-2bbf-4503-9f67-32c363672fae';
        $temp->token = 'MDljMGU1OTYtOGZmMi00ZjFlLThjM2YtMjk3NjcyMWY5MTBj';
        $temp->system = 'dbb1f435-7a14-4fa0-8535-beceed9cd477';
        $temp->name = 'atgtours';
        $this->listApp[] = $temp;

        // acetour
        $temp = new \stdClass();
        $temp->app_id = '7fbaa9c1-7b6a-47e3-aa8a-1d734c41af42';
        $temp->token = 'YjQxNjUxN2QtYzY5NC00MmE4LWI4ZWUtNDUzM2EyN2UzM2I0';
        $temp->system = '3b27e578-0cd7-11ea-a825-d2118390b116';
        $temp->name = 'acetour';
        $this->listApp[] = $temp;

        // acetour-ios
        $temp = new \stdClass();
        $temp->app_id = 'f19e1e6b-7888-4332-b6e8-f63bf2aa673f';
        $temp->token = 'N2E2MjJkYTUtYzVkYi00ODkxLTk3NmMtMGNkMGIxNDBkM2I0';
        $temp->system = '3b27e578-0cd7-11ea-a825-d2118390b116';
        $temp->name = 'acetour-ios';
        $this->listApp[] = $temp;

        // administrator - app
        $temp = new \stdClass();
        $temp->app_id = 'e368be6d-1d88-4ef7-8bd7-8c5d503e7804';
        $temp->token = 'NzUzMTZlNmQtYWU0NS00MzA4LWFkZWItYWRiYmI2ODZjMmZl';
        $temp->name = 'administrator';
        $temp->system = 'administrator';
        $temp->platform = 'mobile';
        $this->listApp[] = $temp;
        
        // administrator - website
        $temp = new \stdClass();
        $temp->app_id = '9f27dd63-f6d4-469a-9dd0-2a5d8cc4d61b';
        $temp->token = 'MzI4NTMyYzUtZDU2Yi00MzZhLWJhYmMtM2IxMmY4NmQzOWQ4';
        $temp->name = 'administrator';
        $temp->system = 'administrator';
        $temp->platform = 'website';
        $this->listApp[] = $temp;

    }

    public function getList()
    {
        return $this->listApp;
    }
}
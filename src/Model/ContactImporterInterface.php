<?php
namespace App\Model;

use App\Entity\Team;

interface ContactImporterInterface {

    function support(string $mimetype): bool;
    /**
     * @return \App\Entity\Contact[]
     */
    function load(string $path): array;
}
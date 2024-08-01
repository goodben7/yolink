<?php

namespace App\Manager;

use App\Entity\Team;
use App\Entity\Contact;
use App\Exception\ContactImportationException;
use App\Model\ContactImporterInterface;
use Doctrine\ORM\EntityManagerInterface;

class ContactManager
{
    /** @var array<\App\Model\ContactImporterInterface> */
    public array $importers;

    public function __construct(
        private EntityManagerInterface $em,
        iterable $importers
    ) {
        $this->importers = iterator_to_array($importers);

    }

    public function import(Team $team, string $path, string $mimetype) {

        $importer = $this->findImporter($mimetype);

        if (null === $importer) {
            throw new ContactImportationException('this file type is not supported');
        }

        $contacts = $importer->load($path);
        foreach ($contacts as $c) {
            $c->setTeam($team);
            $this->em->persist($c);
        }

        $this->em->flush();
    }

    private function findImporter(string $mimetype): ?ContactImporterInterface {
        $processor = null;
        foreach ($this->importers as $p) {
            if ($p->support($mimetype)) {
                return $p;
            }
        }

        return $processor;
    }

}
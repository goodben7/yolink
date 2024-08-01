<?php
namespace App\Importer;

use League\Csv\Reader;
use App\Entity\Contact;
use App\Exception\ContactImportationException;
use App\Model\ContactImporterInterface;

class CsvContactImporter implements ContactImporterInterface {

    public function support(string $mimetype): bool
    {
        return $mimetype == 'text/csv' || $mimetype == 'text/plain';
    }

    public function load(string $path): array
    {
        $contacts = [];

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(';');
        $records = $csv->getRecords();

        foreach ($records as $r) {
            if (!isset($r['fullname']) || !isset($r['phone'])) {
                throw new ContactImportationException('invalid CSV file: fullname or phone column missing');
            }
            
            $contact = new Contact;
            $contact->setFullname($r['fullname']);
            $contact->setPhone($r['phone']);

            $contacts[] = $contact;
        }

        return $contacts;
    }
}
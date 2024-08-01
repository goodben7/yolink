<?php
namespace App\Importer;

use App\Entity\Contact;
use App\Model\ContactImporterInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelContactImporter implements ContactImporterInterface {

    public function support(string $mimetype): bool
    {
        return $mimetype == 'application/vnd.ms-excel' || $mimetype == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function load(string $path): array
    {
        $contacts = [];
        $sheets = IOFactory::load($path);
        $worksheet = $sheets->getSheet(0);
        $rows = $worksheet->toArray();
        
        foreach ($rows as $i => $data) {
            if ($i == 0) {
                continue;
            }

            $contact = new Contact;
            $contact->setPhone($data[0]);
            $contact->setFullname($data[1]);

            $contacts[] = $contact;
        }

        return $contacts;
    }
}
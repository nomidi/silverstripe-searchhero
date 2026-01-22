<?php

namespace App\Extensions;

use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\DataExtension;

class SiteConfigSearchExtension extends DataExtension
{
    private static $db = [
        'SearchNoResultsText' => 'HTMLText',
    ];

    public function updateCMSFields($fields)
    {
        // Feld unter Root.Search anlegen (Tab wird automatisch erzeugt)
        $fields->addFieldToTab(
            'Root.Search',
            HTMLEditorField::create(
                'SearchNoResultsText',
                'Text bei keinen Suchergebnissen'
            )->setDescription('Wird angezeigt, wenn eine Suche keine Treffer liefert.')
        );
    }
}
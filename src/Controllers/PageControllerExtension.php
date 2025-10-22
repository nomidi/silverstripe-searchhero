<?php

namespace kw\searchhero;


use Page;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\Model\List\PaginatedList;

class PageControllerExtension extends Extension
{
    private static $allowed_actions = [
        'SearchHeroForm',
    ];

    public function SearchHeroForm()
    {
        $searchvalue = isset($_GET['Title']) ? $_GET['Title'] : "";

        $fields = new FieldList(
            new TextField('Title', _t("SearchHero.SearchField", "Search"), $searchvalue)
        );

        $actions = new FieldList(
            new FormAction('doSearch', _t("SearchHero.Submit", "Submit"))
        );

        // Formular mit GET-Methode erstellen
        $form = new Form($this->owner, 'SearchHeroForm', $fields, $actions);
        $form->setFormMethod('GET');
        return $form;
    }

    public function doSearch($data, $form)
    {

        $request = $this->owner->getRequest();
        $query = $data['Title'] ?? '';

        $results = SearchHeroEntry::getData($query);

        // Hole Limit aus Konfiguration
        $defaultLimit = (int)Config::inst()->get(self::class, 'default_results_limit');

        $paginatedResults = PaginatedList::create($results, $request);
        $paginatedResults->setPageLength($defaultLimit);

        return $this->owner->customise([
            'Layout' => $this->owner
                ->customise([
                    'Query' => $data['Title'],
                    'Results' => $results,
                    'Limit' => $defaultLimit,
                ])
                ->renderWith(['kw\searchhero\SearchHeroForm_results']),
        ])->renderWith(['Page']);
    }
}
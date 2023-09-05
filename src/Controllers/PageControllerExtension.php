<?php

namespace kw\searchhero;


use Page;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;

class PageControllerExtension extends Extension
{
    private static $allowed_actions = [
        'SearchHeroForm',
    ];

    public function SearchHeroForm()
    {
        if (isset($_POST['action_doSearch'])) {
            $searchvalue = $_POST['Title'];
        } else {
            $searchvalue = "";
        }
        $fields = new FieldList(
            new TextField('Title', _t("SearchHero.SearchField", "Search"), $searchvalue)
        );
        $actions = new FieldList(
            new FormAction('doSearch', _t("SearchHero.Submit", "Submit"))
        );

        return new Form($this->owner, 'SearchHeroForm', $fields, $actions);
    }

    public function doSearch($data, $form)
    {
        $results = SearchHeroEntry::getData($data['Title']);

        return $this->owner->customise([
            'Layout' => $this->owner
                ->customise([
                    'Query' => $data['Title'],
                    'Results' => $results,
                ])
                ->renderWith(['kw\searchhero\SearchHeroForm_results']),
        ])->renderWith(['Page']);
    }
}

<?php

namespace kw\searchhero;


use Page;
use SilverStripe\Core\Extension;
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
        $fields = new FieldList(
            new TextField('Title',_t("SearchHero.SearchField","Search"))
        );
        $actions = new FieldList(
            new FormAction('doSearch', _t("SearchHero.Submit","Submit"))
        );

        return new Form($this->owner, 'SearchHeroForm', $fields, $actions);
    }

    public function doSearch($data, $form)
    {
        $results = SearchHeroEntry::getData($data['Title']);

        return $this->owner->customise([
            'Layout' => $this->owner
                ->customise([
                    'Results' => $results,
                ])
                ->renderWith(['kw\searchhero\SearchHeroForm_results']),
        ])->renderWith(['Page']);
    }
}




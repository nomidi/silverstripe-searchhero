<?php

namespace kw\searchhero;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Model\List\PaginatedList;

class PageControllerExtension extends Extension
{
    private static $allowed_actions = [
        'SearchHeroForm',
        'doSearch', // <-- nötig, sonst 403 beim Submit
    ];

    public function SearchHeroForm()
    {
        $searchvalue = isset($_GET['Title']) ? (string)$_GET['Title'] : '';

        // SR-only Legend als erstes Element im Fieldset
        $legend = LiteralField::create(
            'SRLegend',
            '<legend class="screen-reader-text">Die Website durchsuchen</legend>'
        );

        $search = TextField::create('Title', _t('SearchHero.SearchField', 'Suche'), $searchvalue)
            ->setAttribute('type', 'search')
            ->setAttribute('placeholder', 'Wonach suchst du?')
            ->setAttribute('autocomplete', 'off')
            ->addExtraClass('text');

        $fields = FieldList::create(
            $legend,
            $search
        );

        $submit = FormAction::create('doSearch', _t('SearchHero.Submit', 'Suchen'))
            ->addExtraClass('action')
            ->setAttribute('aria-label', 'Suche starten');

        // sichtbarer Close-Button für modales Overlay (per JS gebunden)
        $close = LiteralField::create(
            'CloseButton',
            '<button type="button" class="search-close">Schließen</button>'
        );

        $actions = FieldList::create($submit, $close);

        $form = Form::create($this->owner, 'SearchHeroForm', $fields, $actions);

        // IDs/ARIA/Rolle
        $form->setHTMLID('Form_SearchHeroForm');
        $form->addExtraClass('search-form');
        $form->setAttribute('role', 'search');
        // Im Overlay-Template: <h2 id="search-heading" class="screen-reader-text">Suche</h2>
        $form->setAttribute('aria-labelledby', 'search-heading');

        // GET + kein CSRF-Token für Suche
        $form->setFormMethod('GET', true);
        $form->disableSecurityToken();

        // saubere Action-URL
        $form->setFormAction($this->owner->Link('SearchHeroForm'));

        return $form;
    }

    public function doSearch($data, $form)
    {
        $request = $this->owner->getRequest();
        $query = isset($data['Title']) ? (string)$data['Title'] : '';

        $results = SearchHeroEntry::getData($query);

        // Limit aus Config
        $defaultLimit = (int) Config::inst()->get(self::class, 'default_results_limit');

        // Pagination korrekt anwenden
        $paginatedResults = PaginatedList::create($results, $request);
        $paginatedResults->setPageLength($defaultLimit);

        return $this->owner->customise([
            'Layout' => $this->owner
                ->customise([
                    'Query'   => $query,
                    'Results' => $paginatedResults, // <-- wichtig: PaginatedList ins Template
                    'Limit'   => $defaultLimit,
                ])
                ->renderWith(['kw\searchhero\SearchHeroForm_results']),
        ])->renderWith(['Page']);
    }
}
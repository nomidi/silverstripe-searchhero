<?php


namespace kw\searchhero;

use DateTime;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\Queries\SQLSelect;

class SearchHeroEntry extends DataObject
{
    private static $singular_name = 'Such Eintrag';
    private static $plural_name = 'Such Einträge';
    private static $table_name = 'SearchHeroEntry';
    private static $db = [
        'Content' => 'Text',
        'RelationClassName' => 'Varchar',
        'RelationID' => 'Int',
        'LinkToDataObject' => 'Varchar',
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'SiteTree' => SiteTree::class,
    ];

    private static $searchable_fields = [
        'Content',
    ];

    private static $seachHeroClasses;


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main',
            $TitleField = new TextareaField('Content', _t('SearchHeroEntry.Content', 'Inhalt')));

        return $fields;
    }

    public static function getData($search)
    {
// Sicherstellen, dass die Suche nicht leer ist
        if (empty($search)) {
            return DataList::create(SearchHeroEntry::class)->filter('ID', 0); // Gibt eine leere Liste zurück
        }

        // Sucheingabe sicher für SQL-Query machen
        $safeSearch = Convert::raw2sql($search);


        $query = new SQLSelect();
        $query->setSelect(['ID'])
            ->setFrom('`SearchHeroEntry`')
            ->setWhere([
                "`Content` LIKE ?" => "%$safeSearch%",
                "`SiteTreeID` <> 0"
            ])
            ->addWhere("ID IN (
        SELECT MIN(`ID`)
        FROM `SearchHeroEntry`
        WHERE `SiteTreeID` IN (SELECT `ID` FROM `SiteTree_Live`)
        GROUP BY `SiteTreeID`
    )");

        // SQL-Abfrage ausführen
        $result = $query->execute();
        $ids = $result->column('ID');

        // Falls keine IDs gefunden wurden, eine leere Liste zurückgeben
        if (empty($ids)) {
            return DataList::create(SearchHeroEntry::class)->filter('ID', 0);
        }

        return SearchHeroEntry::get()->filter(['ID' => $ids]);
    }
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $this->getHeroClasses();
        $this->testHeroClasses();
        $this->testHeroConfig();
        $this->CreateOrUpdate();
    }

    private function getHeroClasses()
    {
        $this->seachHeroClasses = Config::inst()->get('kw\searchhero\CreateSearchIndex', 'Classes');
        if (!is_array($this->seachHeroClasses)) {
            #  trigger_error('No CreateSearchIndex in yml.', E_USER_ERROR);
        }
    }

    private function testHeroClasses()
    {
        foreach ($this->seachHeroClasses as $class) {
            if (!class_exists($class)) {
                trigger_error('Class ' . $class . ' not found.', E_USER_ERROR);
            }
        }
    }

    private function testHeroConfig()
    {
        foreach ($this->seachHeroClasses as $class) {
            $seachHeroConfig = Config::inst()->get($class, 'searchHero');
            if (!is_array($seachHeroConfig)) {
                trigger_error($class . ' no searchHeroFields in yml.', E_USER_ERROR);
            }
            $object = new $class;
            if (!$object instanceof BaseElement) {
                if (!array_key_exists('OutputTitle', $seachHeroConfig)) {
                    trigger_error($class . ' no OutputTitle in yml.', E_USER_ERROR);
                }
                if (!method_exists($class, 'Link')) {
                    trigger_error($class . ' no Link() function found.', E_USER_ERROR);
                }
            }
        }
    }


    public function CreateOrUpdate()
    {

        foreach ($this->seachHeroClasses as $class) {
            $object = new $class;
            if ($object->hasExtension(Versioned::class)) {
                $ClassEntries = Versioned::get_by_stage($class, Versioned::LIVE);
            } else {
                $ClassEntries = $class::get();
            }

            $seachHeroConfig = Config::inst()->get($class, 'searchHero');

            foreach ($ClassEntries as $entry) {

                $FindEntry = SearchHeroEntry::get()->filter(
                    ['RelationID' => $entry->ID, 'RelationClassName' => $entry->ClassName]
                )->First();

                if ($FindEntry) {
                    $FindEntryLastEdited = new DateTime($FindEntry->LastEdited);
                    $DataObjectLastEdited = new DateTime($entry->LastEdited);

                    if ($DataObjectLastEdited > $FindEntryLastEdited) {
                        $FindEntry->Content = $this->getAllContentFields($seachHeroConfig, $entry);
                        if ($entry instanceof BaseElement) {
                            $FindEntry->SiteTree = $entry->getPage()->ID;
                        } else {
                            $OutputTitle = $seachHeroConfig['OutputTitle'];
                            $FindEntry->Title = $entry->$OutputTitle;
                            $FindEntry->LinkToDataObject = $entry->Link();
                        }
                        $FindEntry->write();
                    }
                } else {
                    $newEntry = new SearchHeroEntry();
                    $newEntry->Content = $this->getAllContentFields($seachHeroConfig, $entry);
                    $newEntry->RelationClassName = $entry->ClassName;
                    $newEntry->RelationID = $entry->ID;

                    if ($entry instanceof BaseElement) {
                        $newEntry->SiteTree = $entry->getPage()->ID;
                    } else {

                        $OutputTitle = $seachHeroConfig['OutputTitle'];
                        $newEntry->Title = $entry->$OutputTitle;
                        $newEntry->LinkToDataObject = $entry->Link();
                    }
                    $newEntry->write();
                }
            }
        }

    }

    public function getAllContentFields($saveFields, $entry)
    {
        $return = "";
        foreach ($saveFields['Fields'] as $fieldkey => $fieldvar) {
            $return .= strip_tags($entry->$fieldvar) . ' ';
        }

        return $return;
    }


}
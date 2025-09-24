<?php

use DNADesign\Elemental\Models\BaseElement;
use kw\searchhero\SearchHeroEntry;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

class CreateSearchIndex extends BuildTask
{
    private static $segment = 'CreateSearchIndex';
    protected $title = 'Reset and create Search-Hero Index.';

    public function init()
    {
        parent::init();

        if (!Director::is_cli() && !Director::isDev() && !Permission::check('ADMIN')) {
            return Security::permissionFailure();
        }


    }

    public function run($request)
    {
        DB::query('TRUNCATE TABLE `SearchHeroEntry`');
        $searchHeroClasses = Config::inst()->get('kw\searchhero\CreateSearchIndex', 'Classes');

        $countEntries = [];
        foreach ($searchHeroClasses as $class) {
            $object = new $class;
            if ($object->hasExtension(Versioned::class)) {
                $ClassEntries = Versioned::get_by_stage($class, Versioned::LIVE);
            } else {
                $ClassEntries = $class::get();
            }
            $seachHeroConfig = Config::inst()->get($class, 'searchHero');


            $countEntries[$class] = 0;


            foreach ($ClassEntries as $entry) {
                $newEntry = new SearchHeroEntry();
                $newEntry->Content = $this->getAllContentFields($seachHeroConfig, $entry);
                $newEntry->RelationClassName = $entry->ClassName;
                $newEntry->RelationID = $entry->ID;

                if ($entry instanceof BaseElement) {
                    $page = $entry->getPage();
                    if ($page && $page->exists()) {
                        $newEntry->SiteTree = $page->ID;
                        $newEntry->Title    = $page->Title;
                    }
                } else {
                    $OutputTitle = $seachHeroConfig['OutputTitle'];
                    Versioned::set_reading_mode(Versioned::LIVE);
                    $newEntry->Title = $entry->$OutputTitle;

                    $newEntry->LinkToDataObject = $entry->Link();
                }
                $newEntry->write();
                $countEntries[$class] = $countEntries[$class] + 1;
            }
        }
        $out = "";
        foreach ($countEntries as $classname => $entries) {
            $out .= $classname.' hat '.$entries.' Einträge geschrieben.<br>';
        }
        echo $out;
    }

    public function getAllContentFields($saveFields, $entry)
    {
        $return = "";
        foreach ($saveFields['Fields'] as $fieldkey => $fieldvar) {
            if (!is_null($entry->$fieldvar)) {
                $return .= strip_tags($entry->$fieldvar).' ';
            }

        }

        return $return;
    }

}

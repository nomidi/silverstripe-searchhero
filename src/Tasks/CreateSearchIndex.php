<?php

use DNADesign\Elemental\Models\BaseElement;
use kw\searchhero\SearchHeroEntry;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Input\InputInterface;

class CreateSearchIndex extends BuildTask
{
    private static $segment = 'CreateSearchIndex';
    protected string $title = 'Reset and create Search-Hero Index.';

    public function init()
    {
        parent::init();

        if (!Director::is_cli() && !Director::isDev() && !Permission::check('ADMIN')) {
            return Security::permissionFailure();
        }


    }

    public function execute(InputInterface $input, PolyOutput $output): int
    {
        // Zugriffsschutz wie vorher: nur CLI, Dev, oder ADMIN
        if (!Director::is_cli() && !Director::isDev() && !Permission::check('ADMIN')) {
            Security::permissionFailure();
            $output->writeln('<error>Permission denied</error>');
            return 1;
        }

        DB::query('TRUNCATE TABLE `SearchHeroEntry`');
        // Welche Klassen indiziert werden?
        $searchHeroClasses = Config::inst()->get('kw\searchhero\CreateSearchIndex', 'Classes') ?? [];
        if (empty($searchHeroClasses)) {
            $output->writeln('<comment>No classes configured at kw\searchhero\CreateSearchIndex.Classes</comment>');
            return 0;
        }

        $countEntries = [];
        foreach ($searchHeroClasses as $class) {
            if (!class_exists($class)) {
                $output->writeln("<comment>Class {$class} not found, skipping.</comment>");
                continue;
            }

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
                        $newEntry->ParentClassName = $page->ClassName;
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
        foreach ($countEntries as $classname => $entries) {
            $output->writeln($classname . ' hat ' . $entries . ' Einträge geschrieben.');
        }

        return 0;
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

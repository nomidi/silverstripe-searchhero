<?php

namespace kw\searchhero;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\Versioned\Versioned;

/**
 * Class MenuItemExtension
 */
class SearchHeroExtension extends Extension
{
    public function onAfterWrite(): void
    {
        if (!$this->owner->hasExtension(Versioned::class)) {

            $this->UpdateOrSave();
        }
    }

    protected function onAfterPublish()
    {
        $this->UpdateOrSave();
    }

    public function onAfterUnpublish()
    {
        $this->DeleteSearchHeroEntry($this->owner->ID, $this->owner->ClassName);
    }

    public function onAfterDelete(): void
    {
        $this->DeleteSearchHeroEntry($this->owner->ID, $this->owner->ClassName);
    }

    private function DeleteSearchHeroEntry($ID, $RelationClassName)
    {
        $query = SQLDelete::create()
            ->setFrom('"SearchHeroEntry"')
            ->setWhere(array('"RelationID"' => $ID, 'RelationClassName' => $RelationClassName));
        $query->execute();
    }

    public function UpdateOrSave()
    {
        $seachHeroConfig = Config::inst()->get($this->owner->ClassName, 'searchHero');


        $FindEntry = SearchHeroEntry::get()->filter(
            ['RelationID' => $this->owner->ID, 'RelationClassName' => $this->owner->ClassName]
        )->First();

        if ($FindEntry) {
            $FindEntry->Content = $this->getAllContentFields($seachHeroConfig);
            if ($this->owner instanceof BaseElement) {
                $FindEntry->SiteTree = $this->owner->getPage()->ID;
            } else {
                $OutputTitle = $seachHeroConfig['OutputTitle'];
                $FindEntry->Title = $this->owner->$OutputTitle;
                Versioned::set_reading_mode(Versioned::LIVE);
                $FindEntry->LinkToDataObject = $this->owner->Link();
            }

            $FindEntry->write();
        } else {
            $newEntry = new SearchHeroEntry();
            $newEntry->Content = $this->getAllContentFields($seachHeroConfig);
            $newEntry->RelationClassName = $this->owner->ClassName;
            $newEntry->RelationID = $this->owner->ID;

            if ($this->owner->ID != 0) {

                if ($this->owner instanceof BaseElement) {
                    $newEntry->SiteTree = $this->owner->getPage()->ID;
                } else {
                    $OutputTitle = $seachHeroConfig['OutputTitle'];
                    $newEntry->Title = $this->owner->$OutputTitle;
                    Versioned::set_reading_mode(Versioned::LIVE);
                    $newEntry->LinkToDataObject = $this->owner->Link();
                }
                $newEntry->write();
            }
        }
    }

    public function getAllContentFields($saveFields)
    {
        $return = "";
        foreach ($saveFields['Fields'] as $fieldkey => $fieldvar) {
            if (!is_null($this->owner->$fieldvar)) {
                $return .= strip_tags($this->owner->$fieldvar).' ';
            }
        }

        return $return;
    }

}

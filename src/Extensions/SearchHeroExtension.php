<?php

namespace kw\searchhero;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\Versioned\Versioned;

/**
 * Class MenuItemExtension
 */
class SearchHeroExtension extends DataExtension
{
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if (!$this->owner->hasExtension(Versioned::class)) {
            $this->UpdateOrSave();
        }
    }

    public function onAfterPublish(&$original)
    {
        $this->UpdateOrSave();
    }

    public function onAfterUnpublish()
    {
        $this->DeleteSearchHeroEntry($this->owner->ID, $this->owner->ClassName);
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();
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
            $return .= strip_tags($this->owner->$fieldvar).' ';
        }

        return $return;
    }


}

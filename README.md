# Silverstripe Search Hero
Simple search extension via DataObjects in Silverstripe

## Installation

Before dev/build:
app/_config/searchhero.yml

!!!You need an Link function on your own DataObjects!!!

To reset the search index (task) list all classes
kw\searchhero\CreateSearchIndex:
  Classes:
    - my\data\MyClass


extend your classes:

my\data\MyClass:
  extensions:
    - kw\searchhero\SearchHeroExtension
  searchHero:
    OutputTitle: My_Title_Issue_in_search
    Fields:
      - Searchable_field_1
      - Searchable_field_2

Call task to re-index all data:
dev/tasks/CreateSearchIndex


